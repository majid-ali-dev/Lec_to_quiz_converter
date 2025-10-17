<?php

// Migration 1: create_quizzes_table.php (MCQ)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up()
    {
        Schema::create('fill_blanks', function (Blueprint $table) {
            $table->id();
            $table->string('university_name');
            $table->string('topic_name');
            $table->integer('num_questions');
            $table->json('questions'); // [{sentence: "", blank_word: "", hint: ""}]
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fill_blanks');
    }
};

