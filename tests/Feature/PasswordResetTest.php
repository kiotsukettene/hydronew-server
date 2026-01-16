<?php

use function Pest\Laravel\postJson;

test('example', function () {
    $response = postJson('/api/v1/forgot-password', [
        'email' => 'test@example.com'
    ]);

    $response->assertStatus(200);
});
