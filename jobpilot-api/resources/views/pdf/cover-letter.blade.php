<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $coverLetter->title }}</title>

    <style>
        @page {
            size: A4;
            margin: 18mm 22mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
        }

        .letter-content {
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="letter-content">{{ $coverLetter->content }}</div>
</body>
</html>