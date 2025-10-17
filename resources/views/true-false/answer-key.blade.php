<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>True/False Answer Key - {{ $data->topic_name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; margin: 30px 40px; color:#222; }
        .header { text-align:center; margin-bottom:18px; padding-bottom:12px; border-bottom:3px solid #10b981; }
        .header h1 { margin:0 0 6px 0; color:#059669; letter-spacing:2px; }
        .conf { background:#fee2e2; border:2px solid #dc2626; padding:12px; text-align:center; margin-bottom:16px; }
        .ans { background:#ffffff; border:2px solid #10b981; padding:16px; }
        .line { margin:6px 0; font-family: 'Courier New', monospace; }
        .num { font-weight:bold; color:#059669; display:inline-block; min-width:28px; }
        .letter { font-weight:bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ANSWER KEY</h1>
        <div>{{ $data->topic_name }}</div>
    </div>
    <div class="conf">CONFIDENTIAL - FOR INSTRUCTOR USE ONLY</div>
    <div class="ans">
        @foreach($data->statements as $i => $s)
            <div class="line">
                <span class="num">{{ $i + 1 }}.</span>
                <span class="letter">{{ !empty($s['answer']) ? 'True' : 'False' }}</span>
                <span> - {{ $s['explanation'] ?? '' }}</span>
            </div>
        @endforeach
    </div>
</body>
</html>


