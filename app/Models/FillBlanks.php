<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FillBlanks extends Model
{
    use HasFactory;

    protected $fillable = [
        'university_name',
        'topic_name',
        'num_questions',
        'questions'
    ];

    protected $casts = [
        'questions' => 'array'
    ];
}
