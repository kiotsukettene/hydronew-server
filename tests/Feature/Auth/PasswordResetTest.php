<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

describe('Forgot Password', function () {
    it('user can request password reset code with registered email', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset code sent to your email.',
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'user@example.com',
        ]);

        Notification::assertSentTo($user, \App\Notifications\ForgotPasswordCodeNotification::class);
    });

    it('request fails with unregistered email', function () {
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('request is rate-limited', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Create a recent password reset token
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now()->subSeconds(30), // 30 seconds ago
        ]);

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Please wait before requesting another code.',
            ]);
    });
});

describe('Verify Reset Code', function () {
    it('user can verify valid reset code', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $code = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/verify-reset-code', [
            'email' => 'user@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Code verified. Use the returned reset_token to set a new password.',
            ])
            ->assertJsonStructure([
                'reset_token',
                'expires_in',
            ]);

        $record = DB::table('password_reset_tokens')->where('email', 'user@example.com')->first();
        expect($record)->not->toBeNull();
        expect($response->json('reset_token'))->toBeString();
    });

    it('verification fails with invalid code', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $code = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/verify-reset-code', [
            'email' => 'user@example.com',
            'code' => '999999', // Wrong code
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid reset code.',
            ]);
    });

    it('verification fails with expired code', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $code = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make($code),
            'created_at' => now()->subMinutes(16), // 16 minutes ago (expired)
        ]);

        $response = $this->postJson('/api/v1/verify-reset-code', [
            'email' => 'user@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Reset code expired.',
            ]);
    });
});

describe('Resend Reset Code', function () {
    it('user can resend reset code', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Create an old token (more than 60 seconds ago)
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(2),
        ]);

        $response = $this->postJson('/api/v1/resend-reset-code', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'A new reset code has been sent to your email.',
            ]);

        Notification::assertSentTo($user, \App\Notifications\ForgotPasswordCodeNotification::class);
    });

    it('resend fails when requested too soon', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Create a recent token (less than 60 seconds ago)
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now()->subSeconds(30), // 30 seconds ago
        ]);

        $response = $this->postJson('/api/v1/resend-reset-code', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Please wait before requesting another reset code.',
            ]);
    });
});

describe('Reset Password', function () {
    it('user can reset password using valid reset token', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $resetToken = 'valid-reset-token-12345';
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make($resetToken),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'user@example.com',
            'reset_token' => $resetToken,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset successfully.',
            ]);

        $user->refresh();
        expect(Hash::check('NewPassword123!', $user->password))->toBeTrue();

        // Token should be deleted after use
        $record = DB::table('password_reset_tokens')->where('email', 'user@example.com')->first();
        expect($record)->toBeNull();
    });

    it('reset fails with invalid reset token', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $resetToken = 'valid-reset-token-12345';
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make($resetToken),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'user@example.com',
            'reset_token' => 'invalid-token',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid reset token.',
            ]);

        // Token should still exist
        $record = DB::table('password_reset_tokens')->where('email', 'user@example.com')->first();
        expect($record)->not->toBeNull();
    });

    it('reset fails with expired reset token', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $resetToken = 'valid-reset-token-12345';
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => Hash::make($resetToken),
            'created_at' => now()->subMinutes(16), // 16 minutes ago (expired)
        ]);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'user@example.com',
            'reset_token' => $resetToken,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Reset token expired.',
            ]);
    });
});
