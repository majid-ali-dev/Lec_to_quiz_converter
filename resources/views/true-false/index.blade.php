@extends('layouts.app')

@section('title', 'Generate True/False - LecToQuiz')

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">True / False</h1>
            <p class="text-lg text-gray-600">Generate factual statements with explanations</p>
        </div>

        @if (session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <strong>Error!</strong> {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <form id="tfForm" action="{{ route('truefalse.generate') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label for="university_name" class="block text-sm font-semibold text-gray-700 mb-2">University Name</label>
                    <input type="text" id="university_name" name="university_name" required value="{{ old('university_name') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., University of Karachi">
                    @error('university_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-6">
                    <label for="topic_name" class="block text-sm font-semibold text-gray-700 mb-2">Topic Name</label>
                    <input type="text" id="topic_name" name="topic_name" required value="{{ old('topic_name') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., Operating Systems, Chemistry">
                    @error('topic_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-8">
                    <label for="num_questions" class="block text-sm font-semibold text-gray-700 mb-2">Number of Statements</label>
                    <select id="num_questions" name="num_questions" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select number</option>
                        @foreach([5,10,15,20,25,30] as $n)
                            <option value="{{ $n }}" {{ old('num_questions') == $n ? 'selected' : '' }}>{{ $n }} Statements</option>
                        @endforeach
                    </select>
                    @error('num_questions')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-4 px-6 rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-lg">
                    Generate
                </button>
            </form>
        </div>

        <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-8 text-center">
                <div class="loader mx-auto mb-4"></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Generating...</h3>
                <p class="text-gray-600">Please wait</p>
            </div>
        </div>
    </div>
@endsection

@section('extra-js')
    <script>
        document.getElementById('tfForm').addEventListener('submit', function() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        });
    </script>
@endsection


