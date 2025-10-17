<?php

// Migration 1: create_quizzes_table.php (MCQ)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('true_falses', function (Blueprint $table) {
            $table->id();
            $table->string('university_name');
            $table->string('topic_name');
            $table->integer('num_questions');
            $table->json('statements'); // [{statement: "", answer: true/false, explanation: ""}]
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('true_falses');
    }
};