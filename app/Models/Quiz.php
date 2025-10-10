<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    /**
     * Mass assignment ke liye fields
     */
    protected $fillable = [
        'university_name',
        'topic_name',
        'num_questions',
        'questions'
    ];

    /**
     * JSON fields ko automatically array mein convert karta hai
     */
    protected $casts = [
        'questions' => 'array',
    ];
}