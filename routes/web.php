<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuizController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Yahan saare routes define karte hain
*/

// Home page - Form dikhata hai
Route::get('/', [QuizController::class, 'index'])->name('quiz.index');

// Quiz generate karta hai (form submit hone par)
Route::post('/generate-quiz', [QuizController::class, 'generateQuiz'])->name('quiz.generate');

// Result page - Generated MCQs dikhata hai
Route::get('/quiz/{id}', [QuizController::class, 'showResult'])->name('quiz.result');

// PDF download karta hai
Route::get('/quiz/{id}/download-pdf', [QuizController::class, 'downloadPDF'])->name('quiz.download.pdf');

// Answer key download karta hai
Route::get('/quiz/{id}/download-answer-key', [QuizController::class, 'downloadAnswerKey'])->name('quiz.download.key');
