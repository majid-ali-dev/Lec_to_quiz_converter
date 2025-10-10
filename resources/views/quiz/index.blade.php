@extends('layouts.app')

@section('title', 'Generate Quiz - LecToQuiz')

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Hero Section -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">
                Generate Quiz with AI
            </h1>
            <p class="text-lg text-gray-600">
                Create custom MCQs instantly for any topic
            </p>
        </div>

        <!-- Error Message -->
        @if (session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <strong>Error!</strong> {{ session('error') }}
            </div>
        @endif

        <!-- Main Form Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <form id="quizForm" action="{{ route('quiz.generate') }}" method="POST">
                @csrf

                <!-- University Name -->
                <div class="mb-6">
                    <label for="university_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        University Name
                    </label>
                    <input type="text" id="university_name" name="university_name" required
                        value="{{ old('university_name') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., University of Karachi">
                    @error('university_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Topic Name -->
                <div class="mb-6">
                    <label for="topic_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Topic Name
                    </label>
                    <input type="text" id="topic_name" name="topic_name" required value="{{ old('topic_name') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., Computer Networks, Physics, Mathematics">
                    @error('topic_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Number of Questions -->
                <div class="mb-8">
                    <label for="num_questions" class="block text-sm font-semibold text-gray-700 mb-2">
                        Number of MCQs
                    </label>
                    <select id="num_questions" name="num_questions" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select number of questions</option>
                        <option value="5" {{ old('num_questions') == 5 ? 'selected' : '' }}>5 Questions</option>
                        <option value="10" {{ old('num_questions') == 10 ? 'selected' : '' }}>10 Questions</option>
                        <option value="15" {{ old('num_questions') == 15 ? 'selected' : '' }}>15 Questions</option>
                        <option value="20" {{ old('num_questions') == 20 ? 'selected' : '' }}>20 Questions</option>
                        <option value="25" {{ old('num_questions') == 25 ? 'selected' : '' }}>25 Questions</option>
                        <option value="30" {{ old('num_questions') == 30 ? 'selected' : '' }}>30 Questions</option>
                    </select>
                    @error('num_questions')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-4 px-6 rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-lg">
                    <span id="buttonText">Generate Quiz</span>
                </button>
            </form>
        </div>

        <!-- Features Section -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-white rounded-lg shadow">
                <div class="text-blue-600 mb-3">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">AI Powered</h3>
                <p class="text-sm text-gray-600">Generate smart MCQs instantly</p>
            </div>

            <div class="text-center p-6 bg-white rounded-lg shadow">
                <div class="text-blue-600 mb-3">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Download PDF</h3>
                <p class="text-sm text-gray-600">Save quizzes as PDF files</p>
            </div>

            <div class="text-center p-6 bg-white rounded-lg shadow">
                <div class="text-blue-600 mb-3">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Answer Keys</h3>
                <p class="text-sm text-gray-600">Get correct answers separately</p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 text-center">
            <div class="loader mx-auto mb-4"></div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Generating Quiz...</h3>
            <p class="text-gray-600">Please wait, AI is creating your MCQs</p>
        </div>
    </div>
@endsection

@section('extra-js')
    <script>
        // Form submit hone par loader dikhata hai
        document.getElementById('quizForm').addEventListener('submit', function() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        });
    </script>
@endsection
