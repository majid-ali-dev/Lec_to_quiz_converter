<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrueFalse extends Model
{
    use HasFactory;

    protected $fillable = [
        'university_name',
        'topic_name',
        'num_questions',
        'statements'
    ];

    protected $casts = [
        'statements' => 'array'
    ];
}
