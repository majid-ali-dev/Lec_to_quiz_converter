<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Quiz data store karne ke liye table banate hain
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('university_name'); // University ka naam
            $table->string('topic_name'); // Topic/Subject ka naam
            $table->integer('num_questions'); // Kitne questions chahiye
            $table->json('questions'); // MCQs JSON format mein store honge
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};