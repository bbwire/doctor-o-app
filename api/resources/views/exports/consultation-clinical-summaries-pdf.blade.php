<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Summaries Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; margin: 14px; }
        h1 { font-size: 12px; margin: 0 0 4px 0; }
        .meta { font-size: 8px; color: #555; margin-bottom: 8px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #f5f5f5; font-weight: 700; }
    </style>
</head>
<body>
<h1>Clinical Summaries Export</h1>
<div class="meta">Generated: {{ $generatedAt }} | Rows: {{ count($rows) }}</div>
<table>
    <thead>
    <tr>
        @foreach($columns as $key => $label)
            <th>{{ $label }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            @foreach($columns as $key => $label)
                <td>{{ $row[$key] ?? '' }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>

