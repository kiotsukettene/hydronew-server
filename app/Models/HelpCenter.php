<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpCenter extends Model
{
    use HasFactory;
    protected $fillable = [
        "question",
        "answer",
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function scopeFilter($query, array $filters) {
        if ($filters['search'] ?? false) {
            $query->where('question', 'like', '%' . $filters['search'] . '%')
                ->orWhere('answer', 'like', '%'. $filters['search'] . '%');
        }
        return $query;
    }
}
