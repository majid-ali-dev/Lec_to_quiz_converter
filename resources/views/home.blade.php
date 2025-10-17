@extends('layouts.app')

@section('title', 'LecToQuiz - AI Powered Generator')

@section('extra-css')
    <style>
        /* Smooth animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Card hover effects */
        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Button pulse effect */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .8;
            }
        }

        .btn-pulse:hover {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-12">

        <!-- Hero Section -->
        <div class="text-center mb-10 md:mb-16 animate-fade-in-up">
            <div class="inline-block mb-4">
                <span class="bg-blue-100 text-blue-800 text-xs md:text-sm font-semibold px-4 py-2 rounded-full">
                    🚀 AI-Powered Quiz Generation
                </span>
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-4 leading-tight">
                Welcome to <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">LecToQuiz</span>
            </h1>
            <p class="text-base md:text-lg lg:text-xl text-gray-600 max-w-3xl mx-auto px-4">
                Transform your lectures into professional quizzes instantly with advanced AI technology
            </p>
        </div>

        <!-- Feature Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 mb-12 md:mb-16">

            <!-- MCQ Card -->
            <div class="feature-card bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 md:p-8">
                    <!-- Icon -->
                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 w-14 h-14 md:w-16 md:h-16 rounded-xl flex items-center justify-center mb-4 md:mb-6 shadow-lg">
                        <svg class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                            </path>
                        </svg>
                    </div>

                    <!-- Content -->
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-2 md:mb-3">MCQ Generator</h3>
                    <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6 leading-relaxed">
                        Generate professional multiple choice questions with 4 intelligent options and correct answers
                    </p>

                    <!-- Features List -->
                    <ul class="space-y-2 mb-6 md:mb-8">
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">AI-powered question generation</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">Professional formatting</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">Topic-specific questions</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">PDF & DOCX export</span>
                        </li>
                    </ul>

                    <!-- Button -->
                    <a href="{{ route('quiz.index') }}"
                        class="block w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center font-bold py-3 md:py-4 px-6 rounded-xl hover:from-blue-700 hover:to-blue-800 shadow-lg transform hover:scale-105 transition duration-200 btn-pulse text-sm md:text-base">
                        Create MCQ Quiz →
                    </a>
                </div>
            </div>

            <!-- Fill in the Blanks Card -->
            <div class="feature-card bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 md:p-8">
                    <!-- Icon -->
                    <div
                        class="bg-gradient-to-br from-green-500 to-green-600 w-14 h-14 md:w-16 md:h-16 rounded-xl flex items-center justify-center mb-4 md:mb-6 shadow-lg">
                        <svg class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </div>

                    <!-- Content -->
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-2 md:mb-3">Fill in the Blanks</h3>
                    <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6 leading-relaxed">
                        Create intelligent fill-in-the-blank questions with logical and meaningful blanks
                    </p>

                    <!-- Features List -->
                    <ul class="space-y-2 mb-6 md:mb-8">
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">Smart keyword extraction</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">Contextual blanks only</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">No generic "the" blanks</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">PDF & DOCX export</span>
                        </li>
                    </ul>

                    <!-- Button -->
                    <a href="{{ route('fillblanks.index') }}"
                        class="block w-full bg-gradient-to-r from-green-600 to-green-700 text-white text-center font-bold py-3 md:py-4 px-6 rounded-xl hover:from-green-700 hover:to-green-800 shadow-lg transform hover:scale-105 transition duration-200 btn-pulse text-sm md:text-base">
                        Create Fill Blanks →
                    </a>
                </div>
            </div>

            <!-- True/False Card -->
            <div class="feature-card bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 md:p-8">
                    <!-- Icon -->
                    <div
                        class="bg-gradient-to-br from-purple-500 to-purple-600 w-14 h-14 md:w-16 md:h-16 rounded-xl flex items-center justify-center mb-4 md:mb-6 shadow-lg">
                        <svg class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <!-- Content -->
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-2 md:mb-3">True/False</h3>
                    <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6 leading-relaxed">
                        Generate logical true/false statements that make sense and test real knowledge
                    </p>

                    <!-- Features List -->
                    <ul class="space-y-2 mb-6 md:mb-8">
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">Factual statements</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">Clear reasoning</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">Professional quality</span>
                        </li>
                        <li class="flex items-start text-xs md:text-sm">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-gray-700">PDF & DOCX export</span>
                        </li>
                    </ul>

                    <!-- Button -->
                    <a href="{{ route('truefalse.index') }}"
                        class="block w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white text-center font-bold py-3 md:py-4 px-6 rounded-xl hover:from-purple-700 hover:to-purple-800 shadow-lg transform hover:scale-105 transition duration-200 btn-pulse text-sm md:text-base">
                        Create True/False →
                    </a>
                </div>
            </div>

        </div>

        <!-- Why Choose Section -->
        <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-2xl shadow-xl p-6 md:p-12 mb-12 md:mb-16">
            <h2 class="text-2xl md:text-3xl font-bold text-center text-gray-900 mb-8 md:mb-12">
                Why Choose LecToQuiz?
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8">

                <!-- Feature 1 -->
                <div class="text-center">
                    <div
                        class="bg-blue-100 w-12 h-12 md:w-16 md:h-16 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                        <svg class="w-6 h-6 md:w-8 md:h-8 text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1 md:mb-2 text-sm md:text-base">AI Powered</h3>
                    <p class="text-xs md:text-sm text-gray-600">Advanced AI technology</p>
                </div>

                <!-- Feature 2 -->
                <div class="text-center">
                    <div
                        class="bg-green-100 w-12 h-12 md:w-16 md:h-16 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                        <svg class="w-6 h-6 md:w-8 md:h-8 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1 md:mb-2 text-sm md:text-base">Mobile Ready</h3>
                    <p class="text-xs md:text-sm text-gray-600">Works on all devices</p>
                </div>

                <!-- Feature 3 -->
                <div class="text-center">
                    <div
                        class="bg-purple-100 w-12 h-12 md:w-16 md:h-16 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                        <svg class="w-6 h-6 md:w-8 md:h-8 text-purple-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1 md:mb-2 text-sm md:text-base">Multi-Format</h3>
                    <p class="text-xs md:text-sm text-gray-600">PDF & DOCX support</p>
                </div>

                <!-- Feature 4 -->
                <div class="text-center">
                    <div
                        class="bg-red-100 w-12 h-12 md:w-16 md:h-16 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                        <svg class="w-6 h-6 md:w-8 md:h-8 text-red-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1 md:mb-2 text-sm md:text-base">Answer Keys</h3>
                    <p class="text-xs md:text-sm text-gray-600">Separate downloads</p>
                </div>

            </div>
        </div>

        <!-- CTA Section -->
        <div
            class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-2xl p-6 md:p-12 text-center text-white">
            <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold mb-3 md:mb-4">Ready to Create Your First Quiz?</h2>
            <p class="text-base md:text-lg mb-6 md:mb-8 opacity-90">
                Choose any format above and start generating professional quizzes in seconds
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('quiz.index') }}"
                    class="inline-block bg-white text-blue-600 font-bold py-3 md:py-4 px-6 md:px-8 rounded-xl hover:bg-gray-100 shadow-lg transform hover:scale-105 transition duration-200 text-sm md:text-base">
                    Start with MCQs
                </a>
                <a href="{{ route('fillblanks.index') }}"
                    class="inline-block bg-transparent border-2 border-white text-white font-bold py-3 md:py-4 px-6 md:px-8 rounded-xl hover:bg-white hover:text-purple-600 shadow-lg transform hover:scale-105 transition duration-200 text-sm md:text-base">
                    Try Fill Blanks
                </a>
            </div>
        </div>

    </div>
@endsection
