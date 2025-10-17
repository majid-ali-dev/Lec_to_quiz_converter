<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>True/False - {{ $data->topic_name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; margin: 30px 40px; color:#222; font-size: 13px; }
        .header { text-align:center; margin-bottom:20px; padding-bottom:12px; border-bottom:3px solid #2563eb; }
        .header h1 { margin:0 0 6px 0; color:#1e40af; font-size:24px; }
        .info { background:#f8fafc; padding:10px 15px; margin-bottom:18px; border-left:4px solid #2563eb; }
        .item { margin-bottom:16px; page-break-inside: avoid; }
        .q { font-weight:bold; margin-bottom:4px; }
        .note { font-size:12px; color:#475569; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $data->university_name }} - True / False</h1>
        <div>{{ $data->topic_name }}</div>
    </div>
    <div class="info">
        <div><strong>Total:</strong> {{ $data->num_questions }} statements</div>
        <div><strong>Date:</strong> {{ now()->format('F d, Y') }}</div>
    </div>

    @foreach($data->statements as $i => $s)
        <div class="item">
            <div class="q">{{ $i + 1 }}. {{ $s['statement'] ?? '' }}</div>
            <div class="note">Answer: True / False</div>
        </div>
        @if(($i + 1) % 8 == 0 && $i + 1 < count($data->statements))
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>


