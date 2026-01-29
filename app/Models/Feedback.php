<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'device_id',
        'category',
        'subject',
        'message',
    ];

    /**
     * Get the user that submitted the feedback
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device the feedback is about
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
