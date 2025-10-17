@extends('layouts.app')

@section('title', 'Quiz Result - ' . $quiz->topic_name)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Header Section -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ $quiz->topic_name }}
                </h1>
                <p class="text-gray-600">
                    <span class="font-semibold">University:</span> {{ $quiz->university_name }}
                </p>
                <p class="text-gray-600">
                    <span class="font-semibold">Total Questions:</span> {{ $quiz->num_questions }}
                </p>
                <p class="text-gray-600 text-sm mt-1">
                    <span class="font-semibold">Generated on:</span> {{ $quiz->created_at->format('M d, Y h:i A') }}
                </p>
            </div>
            
            <!-- Download Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('quiz.download.pdf', $quiz->id) }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 shadow-md transform hover:scale-105 transition duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download PDF
                </a>
                <a href="{{ route('quiz.download.docx', $quiz->id) }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 shadow-md transform hover:scale-105 transition duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download DOCX
                </a>
                
                <a href="{{ route('quiz.download.key.pdf', $quiz->id) }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 shadow-md transform hover:scale-105 transition duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Answer Key (PDF)
                </a>
                <a href="{{ route('quiz.download.key.docx', $quiz->id) }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 shadow-md transform hover:scale-105 transition duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Answer Key (DOCX)
                </a>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 rounded-lg">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-green-700 font-semibold">
                Quiz successfully generated! You can now download it as PDF or view the answer key.
            </p>
        </div>
    </div>

    <!-- Questions Section -->
    <div class="space-y-6">
        @foreach($quiz->questions as $index => $question)
        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition duration-300">
            <!-- Question Number & Text -->
            <div class="mb-4">
                <div class="flex items-start">
                    <span class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg mr-4 shadow-md">
                        {{ $index + 1 }}
                    </span>
                    <h3 class="text-lg font-semibold text-gray-800 flex-1 pt-1">
                        {{ $question['question'] }}
                    </h3>
                </div>
            </div>

            <!-- Options -->
            <div class="ml-14 space-y-3">
                @foreach(['a', 'b', 'c', 'd'] as $option)
                    @if(isset($question['options'][$option]))
                    <div class="flex items-start p-4 rounded-lg border-2 border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition duration-200 cursor-pointer">
                        <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 rounded-full flex items-center justify-center font-bold mr-3 shadow-sm">
                            {{ strtoupper($option) }}
                        </span>
                        <span class="text-gray-700 flex-1 pt-1">
                            {{ $question['options'][$option] }}
                        </span>
                    </div>
                    @endif
                @endforeach
            </div>

            <!-- Question Footer (Optional Info) -->
            <div class="ml-14 mt-4 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-500 italic">
                    Select the most appropriate answer from the options above.
                </p>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Action Buttons Section -->
    <div class="mt-10 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-8 text-center">
        <h3 class="text-xl font-bold text-gray-800 mb-4">What would you like to do next?</h3>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('quiz.download.pdf', $quiz->id) }}" 
               class="inline-flex items-center justify-center px-8 py-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-lg transform hover:scale-105 transition duration-200">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3 3m0 0l-3-3m3 3V8"></path>
                </svg>
                Save as PDF
            </a>
            <a href="{{ route('quiz.download.docx', $quiz->id) }}" 
               class="inline-flex items-center justify-center px-8 py-4 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-lg transform hover:scale-105 transition duration-200">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3 3m0 0l-3-3m3 3V8"></path>
                </svg>
                Save as DOCX
            </a>
            
            <a href="{{ route('quiz.download.key.pdf', $quiz->id) }}" 
               class="inline-flex items-center justify-center px-8 py-4 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 shadow-lg transform hover:scale-105 transition duration-200">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                Get Answer Key (PDF)
            </a>
            <a href="{{ route('quiz.download.key.docx', $quiz->id) }}" 
               class="inline-flex items-center justify-center px-8 py-4 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 shadow-lg transform hover:scale-105 transition duration-200">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                Get Answer Key (DOCX)
            </a>
        </div>
    </div>

    <!-- Back to Home Button -->
    <div class="mt-8 text-center">
        <a href="{{ route('quiz.index') }}" 
           class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-gray-600 to-gray-700 text-white font-bold rounded-lg hover:from-gray-700 hover:to-gray-800 shadow-lg transform hover:scale-105 transition duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Generate Another Quiz
        </a>
    </div>

    <!-- Stats Section -->
    <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-blue-600 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h4 class="text-2xl font-bold text-gray-800">{{ $quiz->num_questions }}</h4>
            <p class="text-gray-600">Total Questions</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-green-600 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
            <h4 class="text-2xl font-bold text-gray-800">{{ $quiz->num_questions * 4 }}</h4>
            <p class="text-gray-600">Total Options</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-purple-600 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h4 class="text-2xl font-bold text-gray-800">AI</h4>
            <p class="text-gray-600">Powered Generation</p>
        </div>
    </div>

</div>
@endsection