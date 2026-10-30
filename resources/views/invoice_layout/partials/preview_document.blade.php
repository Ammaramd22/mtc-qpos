<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sample invoice preview</title>
    <style>
        html, body { margin: 0; padding: 0; background: #e5e7eb; color: #111; color-scheme: light; }
        .sample-banner {
            margin: 0;
            padding: 6px 10px;
            background: #fff7ed;
            color: #9a3412;
            font: 700 11px/1.3 Arial, Helvetica, sans-serif;
            text-align: center;
            border-bottom: 1px solid #fdba74;
        }
        .paper {
            margin: 10px auto;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,.12);
        }
        .paper.is-80 { width: 80mm; }
        .paper.is-58 { width: 58mm; }
        .paper.is-a4 { width: 210mm; }
        .paper.is-a5 { width: 210mm; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .pull-left { float: left; }
        .pull-right { float: right; }
        .center-block { display: block; margin-left: auto; margin-right: auto; }
        .word-wrap { word-wrap: break-word; }
        .row { width: 100%; }
        .row:after { content: ""; display: table; clear: both; }
        .col-xs-12 { width: 100%; float: left; }
        .col-xs-8 { width: 66.666%; float: left; }
        .col-xs-6 { width: 50%; float: left; }
        .col-xs-4 { width: 33.333%; float: left; }
        img { max-width: 100%; height: auto; }
        table { border-collapse: collapse; }
    </style>
</head>
<body>
    <p class="sample-banner">Sample data only. This is not a saved invoice.</p>
    <div class="paper is-{{ $paper }}">
        {!! $receipt_html !!}
    </div>
</body>
</html>
