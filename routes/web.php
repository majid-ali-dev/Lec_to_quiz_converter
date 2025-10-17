<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\FillBlanksController;
use App\Http\Controllers\TrueFalseController;

/*
|--------------------------------------------------------------------------
| Home Route - Main Selection Page
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| MCQ Quiz Routes
|--------------------------------------------------------------------------
*/
Route::prefix('quiz')->name('quiz.')->group(function() {
    Route::get('/index', [QuizController::class, 'index'])->name('index');
    Route::post('/generate', [QuizController::class, 'generateQuiz'])->name('generate');
    Route::get('/{id}', [QuizController::class, 'showResult'])->name('result');
    Route::get('/{id}/download-pdf', [QuizController::class, 'downloadPDF'])->name('download.pdf');
    Route::get('/{id}/download-docx', [QuizController::class, 'downloadDOCX'])->name('download.docx');
    Route::get('/{id}/download-answer-key-pdf', [QuizController::class, 'downloadAnswerKeyPDF'])->name('download.key.pdf');
    Route::get('/{id}/download-answer-key-docx', [QuizController::class, 'downloadAnswerKeyDOCX'])->name('download.key.docx');
});

/*
|--------------------------------------------------------------------------
| Fill in the Blanks Routes
|--------------------------------------------------------------------------
*/
Route::prefix('fill-blanks')->name('fillblanks.')->group(function() {
    Route::get('/create', [FillBlanksController::class, 'index'])->name('index');
    Route::post('/generate', [FillBlanksController::class, 'generate'])->name('generate');
    Route::get('/{id}', [FillBlanksController::class, 'showResult'])->name('result');
    Route::get('/{id}/download-pdf', [FillBlanksController::class, 'downloadPDF'])->name('download.pdf');
    Route::get('/{id}/download-docx', [FillBlanksController::class, 'downloadDOCX'])->name('download.docx');
    Route::get('/{id}/download-answer-key-pdf', [FillBlanksController::class, 'downloadAnswerKeyPDF'])->name('download.key.pdf');
    Route::get('/{id}/download-answer-key-docx', [FillBlanksController::class, 'downloadAnswerKeyDOCX'])->name('download.key.docx');
});

/*
|--------------------------------------------------------------------------
| True/False Routes
|--------------------------------------------------------------------------
*/
Route::prefix('true-false')->name('truefalse.')->group(function() {
    Route::get('/create', [TrueFalseController::class, 'index'])->name('index');
    Route::post('/generate', [TrueFalseController::class, 'generate'])->name('generate');
    Route::get('/{id}', [TrueFalseController::class, 'showResult'])->name('result');
    Route::get('/{id}/download-pdf', [TrueFalseController::class, 'downloadPDF'])->name('download.pdf');
    Route::get('/{id}/download-docx', [TrueFalseController::class, 'downloadDOCX'])->name('download.docx');
    Route::get('/{id}/download-answer-key-pdf', [TrueFalseController::class, 'downloadAnswerKeyPDF'])->name('download.key.pdf');
    Route::get('/{id}/download-answer-key-docx', [TrueFalseController::class, 'downloadAnswerKeyDOCX'])->name('download.key.docx');
});