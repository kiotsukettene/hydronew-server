<?php

/**
 * Auth Controller Tests
 * 
 * IMPORTANT: Make sure your routes/api.php file includes these routes:
 * 
 * Route::post('/register', [AuthController::class, 'register']);
 * Route::post('/login', [AuthController::class, 'login']);
 * 
 * Route::middleware('auth:sanctum')->group(function () {
 *     Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
 *     Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
 *     Route::post('/logout', [AuthController::class, 'logout']);
 * });
 */

use App\Models\User;
use App\Models\LoginHistory;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

// ==================== REGISTER METHOD TESTS ====================

test('it can register a user with valid data', function () {
    $userData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!'
    ];

    $response = postJson('/api/v1/register', $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'first_name', 'last_name', 'email'],
            'token',
            'needs_verification'
        ])
        ->assertJson([
            'needs_verification' => true
        ]);

    assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe'
    ]);
});

test('it generates and stores OTP on registration', function () {
    $userData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!'
    ];

    postJson('/api/v1/register', $userData);

    $user = User::where('email', 'john@example.com')->first();

    expect($user->verification_code)->not->toBeNull()
        ->and($user->verification_expires_at)->not->toBeNull()
        ->and($user->last_otp_sent_at)->not->toBeNull()
        ->and($user->verification_expires_at->isFuture())->toBeTrue();
});

test('it sends verification email on registration', function () {
    $userData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!'
    ];

    postJson('/api/v1/register', $userData);

    $user = User::where('email', 'john@example.com')->first();

    Notification::assertSentTo($user, VerificationCodeNotification::class);
});

test('it returns verification token with limited abilities', function () {
    $userData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!'
    ];

    $response = postJson('/api/v1/register', $userData);

    $token = $response->json('token');
    expect($token)->not->toBeNull();

    $user = User::where('email', 'john@example.com')->first();
    $userToken = $user->tokens()->first();
    
    expect($userToken->can('verify'))->toBeTrue();
});

test('it validates required fields on registration', function () {
    $response = postJson('/api/v1/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
});

test('it prevents duplicate email registration', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'existing@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!'
    ];

    $response = postJson('/api/v1/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('it hashes the password correctly', function () {
    $userData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!'
    ];

    postJson('/api/v1/register', $userData);

    $user = User::where('email', 'john@example.com')->first();

    expect(Hash::check('Password123!', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('Password123!');
});

// ==================== LOGIN METHOD TESTS ====================

test('it can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified_at' => now()
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'Password123!'
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user',
            'token'
        ])
        ->assertJson([
            'message' => 'Login successfully!'
        ]);
});

test('it returns error for invalid email', function () {
    $response = postJson('/api/v1/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'Password123!'
    ]);

    // The API returns "User doesn't exist." instead of "Invalid credentials"
    $response->assertStatus(422)
        ->assertJsonFragment([
            'message' => "User doesn't exist."
        ])
        ->assertJsonValidationErrors(['email']);
});

test('it returns error for invalid password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('CorrectPassword123!'),
        'email_verified_at' => now()
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'WrongPassword123!'
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Invalid credentials'
        ]);
});

test('it requires email verification before login', function () {
    User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified_at' => null
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'unverified@example.com',
        'password' => 'Password123!'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'needs_verification' => true
        ])
        ->assertJsonStructure(['token', 'message']);
});

test('it regenerates OTP for unverified users on login attempt', function () {
    $user = User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified_at' => null
    ]);

    postJson('/api/v1/login', [
        'email' => 'unverified@example.com',
        'password' => 'Password123!'
    ]);

    $user->refresh();

    expect($user->verification_code)->not->toBeNull()
        ->and($user->verification_expires_at)->not->toBeNull()
        ->and($user->last_otp_sent_at)->not->toBeNull();

    Notification::assertSentTo($user, VerificationCodeNotification::class);
});

test('it creates login history on successful login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified_at' => now()
    ]);

    postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'Password123!'
    ]);

    assertDatabaseHas('login_histories', [
        'user_id' => $user->id
    ]);

    $loginHistory = LoginHistory::where('user_id', $user->id)->first();
    expect($loginHistory->ip_address)->not->toBeNull()
        ->and($loginHistory->user_agent)->not->toBeNull();
});

test('it deletes old tokens and creates verification token for unverified users', function () {
    $user = User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified_at' => null
    ]);

    // Create an old token
    $user->createToken('old_token');
    expect($user->tokens()->count())->toBe(1);

    $response = postJson('/api/v1/login', [
        'email' => 'unverified@example.com',
        'password' => 'Password123!'
    ]);

    $user->refresh();
    
    // Old tokens should be deleted, new verification token created
    expect($user->tokens()->count())->toBe(1);
    $token = $user->tokens()->first();
    expect($token->name)->toBe('verification_token')
        ->and($token->can('verify'))->toBeTrue();
});

test('it returns full auth token for verified users', function () {
    $user = User::factory()->create([
        'email' => 'verified@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified_at' => now()
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'verified@example.com',
        'password' => 'Password123!'
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token']);

    $user->refresh();
    $token = $user->tokens()->first();
    expect($token->name)->toBe('auth_token');
});

// ==================== VERIFY OTP METHOD TESTS ====================

test('it verifies email with valid OTP', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10),
        'last_otp_sent_at' => now()
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/verify-otp', [
        'otp' => '123456'
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Email verified successfully.'
        ])
        ->assertJsonStructure(['token']);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->verification_code)->toBeNull()
        ->and($user->verification_expires_at)->toBeNull()
        ->and($user->last_otp_sent_at)->toBeNull();
});

test('it returns error for invalid OTP', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10),
        'last_otp_sent_at' => now()
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/verify-otp', [
        'otp' => '999999'
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Invalid verification code.'
        ]);
});

test('it returns error for expired OTP', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('123456'),
        'verification_expires_at' => now()->subMinutes(1), // Expired
        'last_otp_sent_at' => now()->subMinutes(11)
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/verify-otp', [
        'otp' => '123456'
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Verification code expired.'
        ]);
});


test('it returns full auth token after successful verification', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10)
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/verify-otp', [
        'otp' => '123456'
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token']);

    $user->refresh();
    $newToken = $user->tokens()->first();
    expect($newToken->name)->toBe('auth_token');
});

test('it handles already verified email gracefully', function () {
    $user = User::factory()->create([
        'email_verified_at' => now() // Already verified
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/verify-otp', [
        'otp' => '123456'
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Email already verified.'
        ])
        ->assertJsonStructure(['token']);
});

test('it clears OTP data after successful verification', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10),
        'last_otp_sent_at' => now()
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    postJson('/api/v1/verify-otp', [
        'otp' => '123456'
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $user->refresh();

    expect($user->verification_code)->toBeNull()
        ->and($user->verification_expires_at)->toBeNull()
        ->and($user->last_otp_sent_at)->toBeNull();
});

test('it creates login history on successful verification', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10)
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    postJson('/api/v1/verify-otp', [
        'otp' => '123456'
    ], [
        'Authorization' => "Bearer $token"
    ]);

    assertDatabaseHas('login_histories', [
        'user_id' => $user->id
    ]);
});

test('it deletes verification tokens after verification', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10)
    ]);

    $verificationToken = $user->createToken('verification_token', ['verify']);
    $tokenId = $verificationToken->accessToken->id;

    postJson('/api/v1/verify-otp', [
        'otp' => '123456'
    ], [
        'Authorization' => "Bearer {$verificationToken->plainTextToken}"
    ]);

    // Old verification token should be deleted
    assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenId
    ]);

    // New auth token should exist
    $user->refresh();
    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->name)->toBe('auth_token');
});

// ==================== RESEND OTP METHOD TESTS ====================

test('it resends OTP with valid verification token', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'last_otp_sent_at' => now()->subMinutes(2)
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/resend-otp', [], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'A new verification code has been sent.'
        ]);

    $user->refresh();

    expect($user->verification_code)->not->toBeNull()
        ->and($user->verification_expires_at)->not->toBeNull();

    Notification::assertSentTo($user, VerificationCodeNotification::class);
});

test('it prevents resending OTP before rate limit expires', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'last_otp_sent_at' => now()->subSeconds(15) // Less than 30 seconds
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/resend-otp', [], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'message' => 'Please wait before requesting another code.'
        ]);
});

test('it returns error if email already verified for resend', function () {
    $user = User::factory()->create([
        'email_verified_at' => now() // Already verified
    ]);

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    $response = postJson('/api/v1/resend-otp', [], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Email already verified.'
        ]);
});

test('it generates new OTP and updates timestamps on resend', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'verification_code' => Hash::make('111111'),
        'verification_expires_at' => now()->addMinutes(5),
        'last_otp_sent_at' => now()->subMinutes(2)
    ]);

    $oldCode = $user->verification_code;
    $oldExpiry = $user->verification_expires_at;

    $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

    postJson('/api/v1/resend-otp', [], [
        'Authorization' => "Bearer $token"
    ]);

    $user->refresh();

    expect($user->verification_code)->not->toBe($oldCode)
        ->and($user->verification_expires_at->isAfter($oldExpiry))->toBeTrue()
        ->and($user->last_otp_sent_at->diffInSeconds(now()))->toBeLessThan(5);
});

// ==================== LOGOUT METHOD TESTS ====================

test('it logs out authenticated user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = postJson('/api/v1/logout', [], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'You are logged out'
        ]);
});

test('it deletes all user tokens on logout', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $token1 = $user->createToken('auth_token')->plainTextToken;
    $user->createToken('another_token');
    
    expect($user->tokens()->count())->toBe(2);

    postJson('/api/v1/logout', [], [
        'Authorization' => "Bearer $token1"
    ]);

    $user->refresh();
    expect($user->tokens()->count())->toBe(0);
});

test('it resets first_time_login flag on logout', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'first_time_login' => true // Use boolean instead of integer
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    postJson('/api/v1/logout', [], [
        'Authorization' => "Bearer $token"
    ]);

    $user->refresh();
    
    // Check for falsy value (0, false, or null)
    expect($user->first_time_login)->toBeFalsy();
});

// ==================== HELPER METHODS TESTS ====================

test('it generates 6-digit OTP', function () {
    $user = User::factory()->create();
    
    $controller = new \App\Http\Controllers\Auth\AuthController();
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('generateOtp');
    $method->setAccessible(true);
    
    $otp = $method->invoke($controller);
    
    expect($otp)->toBeString()
        ->and(strlen($otp))->toBe(6)
        ->and(is_numeric($otp))->toBeTrue()
        ->and((int)$otp)->toBeGreaterThanOrEqual(100000)
        ->and((int)$otp)->toBeLessThanOrEqual(999999);
});

test('it stores hashed OTP with correct expiration', function () {
    $user = User::factory()->create();
    
    $controller = new \App\Http\Controllers\Auth\AuthController();
    $reflection = new \ReflectionClass($controller);
    
    $generateMethod = $reflection->getMethod('generateOtp');
    $generateMethod->setAccessible(true);
    $otp = $generateMethod->invoke($controller);
    
    $storeMethod = $reflection->getMethod('storeOtpOnUser');
    $storeMethod->setAccessible(true);
    $storeMethod->invoke($controller, $user, $otp);
    
    $user->refresh();
    
    // Calculate time difference properly
    $minutesDiff = abs($user->verification_expires_at->diffInMinutes(now()));
    
    expect($user->verification_code)->not->toBeNull()
        ->and($user->verification_code)->not->toBe($otp) // Should be hashed
        ->and(Hash::check($otp, $user->verification_code))->toBeTrue()
        ->and($user->verification_expires_at)->not->toBeNull()
        ->and($user->verification_expires_at->isFuture())->toBeTrue()
        ->and($minutesDiff)->toBeGreaterThanOrEqual(9)
        ->and($minutesDiff)->toBeLessThanOrEqual(10)
        ->and($user->last_otp_sent_at)->not->toBeNull()
        ->and($user->last_otp_sent_at->diffInSeconds(now()))->toBeLessThan(2);
});