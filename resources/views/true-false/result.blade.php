@extends('layouts.app')

@section('title', 'True/False - Result')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $data->topic_name }}</h1>
                <p class="text-gray-600"><span class="font-semibold">University:</span> {{ $data->university_name }}</p>
                <p class="text-gray-600"><span class="font-semibold">Total Statements:</span> {{ $data->num_questions }}</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('truefalse.download.pdf', $data->id) }}" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Download PDF</a>
                <a href="{{ route('truefalse.download.docx', $data->id) }}" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Download DOCX</a>
                <a href="{{ route('truefalse.download.key.pdf', $data->id) }}" class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700">Answer Key PDF</a>
                <a href="{{ route('truefalse.download.key.docx', $data->id) }}" class="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700">Answer Key DOCX</a>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @foreach($data->statements as $index => $s)
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="mb-2 text-gray-800 font-semibold">{{ $index + 1 }}. {{ $s['statement'] ?? '' }}</div>
            <div class="text-sm text-gray-600 italic">Choose True or False</div>
        </div>
        @endforeach
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('truefalse.index') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-gray-600 to-gray-700 text-white font-bold rounded-lg hover:from-gray-700 hover:to-gray-800">Generate Another</a>
    </div>
</div>
@endsection


