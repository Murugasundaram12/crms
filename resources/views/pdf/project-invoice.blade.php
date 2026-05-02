<!DOCTYPE html>
<html>

<head>
    <title>Project Invoice - {{ $project->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .total {
            font-weight: bold;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Project Invoice</h1>
        <p>Project: {{ $project->name }} ({{ $project->project_code }})</p>
        <p>Client: {{ $project->client->name }}</p>
        <p>Date: {{ now()->format('d M Y') }}</p>
    </div>

    <h3>Quotation Items</h3>
    @if($project->quotations->count())
        @foreach($project->quotations->latest()->first()->items as $item)
            <p>{{ $item->description }} - Qty: {{ $item->quantity }} @ Rate: ${{ $item->rate }} = ${{ $item->amount }}</p>
        @endforeach
        <p><strong>Quotation Total:
                ${{ number_format($project->quotations->latest()->first()->total_amount ?? 0, 2) }}</strong></p>
    @else
        <p>No quotation available.</p>
    @endif

    <h3>Payment Stages</h3>
    <table class="table">
        <tr>
            <th>Stage</th>
            <th>Percentage</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
        @foreach ($project->paymentStages as $stage)
            <tr>
                <td>{{ $stage->stage_name }}</td>
                <td>{{ $stage->percentage }}%</td>
                <td>${{ number_format($stage->amount ?? 0, 2) }}</td>
                <td>{{ ucfirst($stage->status) }}</td>
            </tr>
        @endforeach
    </table>

    <h3>Variations</h3>
    <table class="table">
        <tr>
            <th>Type</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
        @foreach ($project->variations as $variation)
            <tr>
                <td>{{ ucfirst($variation->type) }}</td>
                <td>{{ Str::limit($variation->description, 50) }}</td>
                <td>${{ number_format($variation->amount, 2) }}</td>
                <td>{{ ucfirst($variation->status) }}</td>
            </tr>
        @endforeach
    </table>

    <h3>Summary</h3>
    <table class="table">
        <tr>
            <th>Quotation Total</th>
            <td>${{ number_format($project->quotations->latest()->first()?->total_amount ?? 0, 2) }}</td>
        </tr>
        <tr>
            <th>Variations Net</th>
            <td>${{ number_format($project->variations->where('status', 'approved')->sum(fn($v) => $v->type === 'additional' ? $v->amount : -$v->amount), 2) }}
            </td>
        </tr>
        <tr>
            <th>Payments Total</th>
            <td>${{ number_format($project->payments->where('status', 'paid')->sum('amount'), 2) }}</td>
        </tr>
        <tr class="total">
            <th>Final Bill</th>
            <td>${{ number_format($project->final_bill, 2) }}</td>
        </tr>
    </table>

    <p>Generated on {{ now() }}</p>
</body>

</html>
