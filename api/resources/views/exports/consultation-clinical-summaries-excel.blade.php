<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Summaries Export</title>
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; white-space: pre-wrap; }
        th { background: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
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

