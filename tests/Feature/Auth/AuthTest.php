<?php

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;


beforeEach(function () {
    Notification::fake();
});

describe('Register', function () {
    it('user can register with valid details', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User registered successfully. Please check your email for the verification code.',
                'needs_verification' => true,
            ])
            ->assertJsonStructure([
                'user',
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        expect($user->email_verified_at)->toBeNull()
            ->and($user->verification_code)->not->toBeNull()
            ->and($user->verification_expires_at)->not->toBeNull();

        Notification::assertSentTo($user, \App\Notifications\VerificationCodeNotification::class);
    });

    it('registration fails with invalid input', function () {
        $invalidData = [
            'first_name' => '', // Empty first name
            'last_name' => '', // Empty last name
            'email' => 'invalid-email', // Invalid email format
            'password' => '123', // Too short, missing requirements
            'password_confirmation' => '456', // Doesn't match
        ];

        $response = $this->postJson('/api/v1/register', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
    });

    it('registration fails with duplicate email', function () {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

describe('Login', function () {
    it('user can login with verified email', function () {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'verified@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Login successfully!',
            ])
            ->assertJsonStructure([
                'user',
                'token',
            ]);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
        ]);

        expect($user->loginHistories()->count())->toBe(1);
    });

    it('login fails with invalid credentials', function () {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'verified@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);

        $this->assertDatabaseMissing('login_histories', [
            'user_id' => $user->id,
        ]);
    });

    it('login fails with non-existent email', function () {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('login returns verification flow when email is unverified', function () {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'unverified@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Your email is not verified. Please check your email for the verification code.',
                'needs_verification' => true,
            ])
            ->assertJsonStructure(['token']);

        $user->refresh();
        expect($user->verification_code)->not->toBeNull()
            ->and($user->verification_expires_at)->not->toBeNull();

        Notification::assertSentTo($user, \App\Notifications\VerificationCodeNotification::class);
    });
});

describe('Verify OTP', function () {
    it('user can verify email with valid OTP', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'verification_code' => Hash::make('123456'),
            'verification_expires_at' => now()->addMinutes(10),
        ]);

        $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $verificationToken)
            ->postJson('/api/v1/verify-otp', [
                'otp' => '123456',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email verified successfully.',
            ])
            ->assertJsonStructure(['token']);

        $user->refresh();
        expect($user->email_verified_at)->not->toBeNull()
            ->and($user->verification_code)->toBeNull()
            ->and($user->verification_expires_at)->toBeNull();

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
        ]);
    });

    it('verification fails with invalid OTP', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'verification_code' => Hash::make('123456'),
            'verification_expires_at' => now()->addMinutes(10),
        ]);

        $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $verificationToken)
            ->postJson('/api/v1/verify-otp', [
                'otp' => '999999',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code.',
            ]);

        $user->refresh();
        expect($user->email_verified_at)->toBeNull();
    });

    it('verification fails with expired OTP', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'verification_code' => Hash::make('123456'),
            'verification_expires_at' => now()->subMinutes(1),
        ]);

        $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $verificationToken)
            ->postJson('/api/v1/verify-otp', [
                'otp' => '123456',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Verification code expired.',
            ]);

        $user->refresh();
        expect($user->email_verified_at)->toBeNull();
    });

    it('verification fails without verify token', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'verification_code' => Hash::make('123456'),
            'verification_expires_at' => now()->addMinutes(10),
        ]);

        // Create a token without 'verify' ability
        $authToken = $user->createToken('auth_token', [])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $authToken)
            ->postJson('/api/v1/verify-otp', [
                'otp' => '123456',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid token for verification.',
            ]);
    });

    it('verification returns success when email is already verified', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $verificationToken)
            ->postJson('/api/v1/verify-otp', [
                'otp' => '123456',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email already verified.',
            ])
            ->assertJsonStructure(['token']);
    });
});

describe('Resend OTP', function () {
    it('user can resend OTP with valid verification token', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'last_otp_sent_at' => now()->subMinutes(2),
        ]);

        $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $verificationToken)
            ->postJson('/api/v1/resend-otp');

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'A new verification code has been sent.',
            ]);

        $user->refresh();
        expect($user->verification_code)->not->toBeNull()
            ->and($user->verification_expires_at)->not->toBeNull()
            ->and($user->last_otp_sent_at)->not->toBeNull();

        Notification::assertSentTo($user, \App\Notifications\VerificationCodeNotification::class);
    });

    it('resend OTP fails when requested too soon', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'last_otp_sent_at' => now()->subSeconds(15),
        ]);

        $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $verificationToken)
            ->postJson('/api/v1/resend-otp');

        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Please wait before requesting another code.',
            ]);
    });

    it('resend OTP fails for already verified email', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $verificationToken)
            ->postJson('/api/v1/resend-otp');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Email already verified.',
            ]);
    });

    it('resend OTP fails without verify token', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Create a token without 'verify' ability
        $authToken = $user->createToken('auth_token', [])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $authToken)
            ->postJson('/api/v1/resend-otp');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid token.',
            ]);
    });
});

describe('Logout', function () {
    it('authenticated user can logout', function () {
        $user = User::factory()->create([
            'first_time_login' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'You are logged out',
            ]);

        $user->refresh();
        expect($user->first_time_login)->toBeFalse()
            ->and($user->tokens)->toHaveCount(0);
    });

    it('unauthenticated user cannot logout', function () {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(401);
    });
});
