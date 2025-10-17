<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fill in the Blanks - {{ $data->topic_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 30px 40px;
            color: #222;
            font-size: 13px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 3px solid #2563eb;
        }

        .header h1 {
            margin: 0 0 6px 0;
            color: #1e40af;
            font-size: 24px;
        }

        .info {
            background: #f8fafc;
            padding: 10px 15px;
            margin-bottom: 18px;
            border-left: 4px solid #2563eb;
        }

        .item {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .q {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .hint {
            font-size: 12px;
            color: #475569;
            background: #fff;
            border: 1px dashed #cbd5e1;
            padding: 6px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $data->university_name }} - Fill in the Blanks</h1>
        <div>{{ $data->topic_name }}</div>
    </div>
    <div class="info">
        <div><strong>Total Items:</strong> {{ $data->num_questions }}</div>
        <div><strong>Date:</strong> {{ now()->format('F d, Y') }}</div>
    </div>

    @foreach ($data->questions as $i => $q)
        <div class="item">
            <div class="q">{{ $i + 1 }}. {{ $q['sentence'] ?? '' }}</div>
            {{-- <div class="hint"><strong>Hint:</strong> {{ $q['hint'] ?? '-' }}</div> --}}
        </div>
        @if (($i + 1) % 6 == 0 && $i + 1 < count($data->questions))
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
