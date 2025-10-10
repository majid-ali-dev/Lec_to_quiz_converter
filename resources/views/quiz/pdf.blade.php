<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz - {{ $quiz->topic_name }}</title>
    <style>
        /* Professional PDF styling */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            line-height: 1.8;
            color: #222;
            margin: 30px 40px;
            font-size: 13px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
        }
        
        .header h1 {
            margin: 0 0 8px 0;
            color: #1e40af;
            font-size: 26px;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 0;
            color: #64748b;
            font-size: 16px;
            font-weight: normal;
        }
        
        .info-section {
            background-color: #f8fafc;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
            font-size: 12px;
        }
        
        .info-section p {
            margin: 4px 0;
            color: #475569;
        }
        
        .info-section strong {
            color: #1e293b;
        }
        
        .instructions {
            background-color: #fef3c7;
            padding: 12px 15px;
            margin-bottom: 25px;
            border-left: 4px solid #f59e0b;
            font-size: 12px;
        }
        
        .instructions p {
            margin: 0 0 8px 0;
            font-weight: bold;
            color: #92400e;
        }
        
        .instructions ul {
            margin: 5px 0;
            padding-left: 20px;
            color: #78350f;
        }
        
        .instructions li {
            margin: 3px 0;
        }
        
        /* Question styling - IMPROVED */
        .question-block {
            margin-bottom: 22px;
            page-break-inside: avoid;
        }
        
        .question-header {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .question-number {
            color: #2563eb;
            margin-right: 6px;
        }
        
        .options-container {
            margin-left: 8px;
            margin-top: 8px;
        }
        
        .option-item {
            margin: 6px 0;
            padding: 8px 10px;
            background-color: #f8fafc;
            border-left: 3px solid #cbd5e1;
            line-height: 1.6;
        }
        
        .option-letter {
            font-weight: bold;
            color: #475569;
            margin-right: 8px;
            display: inline-block;
            min-width: 20px;
        }
        
        .option-text {
            color: #334155;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        /* Answer space */
        .answer-space {
            margin-top: 8px;
            padding: 8px;
            background-color: #ffffff;
            border: 1px dashed #cbd5e1;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
    
    <!-- Header -->
    <div class="header">
        <h1>{{ $quiz->topic_name }}</h1>
        <h2>Multiple Choice Questions (MCQs)</h2>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <p><strong>University:</strong> {{ $quiz->university_name }}</p>
        <p><strong>Topic:</strong> {{ $quiz->topic_name }}</p>
        <p><strong>Total Questions:</strong> {{ $quiz->num_questions }}</p>
        <p><strong>Date:</strong> {{ now()->format('F d, Y') }}</p>
        <p><strong>Time Allowed:</strong> {{ $quiz->num_questions * 2 }} minutes</p>
    </div>

    <!-- Instructions -->
    <div class="instructions">
        <p>Instructions:</p>
        <ul>
            <li>Read each question carefully before answering</li>
            <li>Each question has only ONE correct answer</li>
            <li>Choose the option that best answers the question</li>
            <li>Mark your answer clearly on the answer sheet</li>
        </ul>
    </div>

    <!-- Questions -->
    @foreach($quiz->questions as $index => $question)
        <div class="question-block">
            <!-- Question -->
            <div class="question-header">
                <span class="question-number">{{ $index + 1 }}.</span>
                {{ $question['question'] }}
            </div>
            
            <!-- Options -->
            <div class="options-container">
                @foreach(['a', 'b', 'c', 'd'] as $option)
                    @if(isset($question['options'][$option]))
                    <div class="option-item">
                        <span class="option-letter">{{ $option }})</span>
                        <span class="option-text">{{ $question['options'][$option] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
            
            <!-- Answer Space -->
            <div class="answer-space">
                <strong>Your Answer:</strong> __________
            </div>
        </div>
        
        <!-- Page break after every 4 questions -->
        @if(($index + 1) % 4 == 0 && $index + 1 < count($quiz->questions))
            <div class="page-break"></div>
        @endif
    @endforeach

    <!-- Footer -->
    <div class="footer">
        <p><strong>Generated by LecToQuiz Converter</strong></p>
        <p>&copy; {{ date('Y') }} All Rights Reserved</p>
    </div>

</body>
</html>