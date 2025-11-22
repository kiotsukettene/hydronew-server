<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    \Log::info('Channel authorization attempt', [
        'user_id' => $user->id,
        'requested_id' => $id, 
        'match' => (int) $user->id === (int) $id
    ]);

    return (int) $user->id === (int) $id;
});
