<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Project Invoice - {{ $project->name }}</title>
    <style>
        @page {
            margin: 14px 16px 28px 16px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .header {
            text-align: left;
            margin-bottom: 14px;
            position: relative;
            min-height: 70px;
        }

        .logo-right {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
        }

        .logo-right img {
            max-width: 165px;
            max-height: 54px;
        }

        .date-top {
            margin-top: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        h1 {
            margin: 0 0 8px 0;
            color: #3c7fc0;
            font-size: 24px;
        }

        h3 {
            margin: 12px 0 6px 0;
            font-size: 18px;
            color: #3c7fc0;
        }

        p {
            margin: 0 0 6px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 5px 6px;
            text-align: left;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .total {
            font-weight: bold;
            font-size: 12px;
        }

        .footer {
            position: fixed;
            left: 16px;
            right: 16px;
            bottom: 8px;
            font-size: 10px;
        }

        .footer td {
            vertical-align: bottom;
            width: 33.33%;
        }

        .footer .center {
            text-align: center;
            color: #3c7fc0;
            font-weight: 700;
        }

        .footer .right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $logoPath = public_path('assets/img/pdf/logo12.png');
        $hasLogo = file_exists($logoPath) && extension_loaded('gd');
        $latestQuotation = $project->quotations->sortByDesc('created_at')->first();
        $quotationNo = $latestQuotation?->quotation_number ?: ('#' . ($latestQuotation?->id ?? '-'));
    @endphp

    <div class="header">
        @if($hasLogo)
            <div class="logo-right">
                <img src="{{ $logoPath }}" alt="Logo">
                <div class="date-top">Date : {{ now()->format('d/m/Y') }}</div>
            </div>
        @endif

        <h1>Project Invoice</h1>
        <p>Project: {{ $project->name }} ({{ $project->project_code }})</p>
        <p>Client: {{ $project->client->name }}</p>
        <p>Date: {{ now()->format('d M Y') }}</p>
    </div>

    <h3>Quotation Items</h3>
    @if($latestQuotation && $latestQuotation->items->count())
        @foreach($latestQuotation->items as $item)
            <p>{{ $item->description }} - Qty: {{ $item->quantity }} @ Rate: &#8377;{{ number_format((float) $item->rate, 2) }} = &#8377;{{ number_format((float) $item->amount, 2) }}</p>
        @endforeach
        <p><strong>Quotation Total: &#8377;{{ number_format((float) ($latestQuotation->total_amount ?? 0), 2) }}</strong></p>
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
                <td>&#8377;{{ number_format((float) ($stage->amount ?? 0), 2) }}</td>
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
                <td>&#8377;{{ number_format((float) $variation->amount, 2) }}</td>
                <td>{{ ucfirst($variation->status) }}</td>
            </tr>
        @endforeach
    </table>

    <h3>Summary</h3>
    <table class="table">
        <tr>
            <th>Quotation Total</th>
            <td>&#8377;{{ number_format((float) ($latestQuotation?->total_amount ?? 0), 2) }}</td>
        </tr>
        <tr>
            <th>Variations Net</th>
            <td>&#8377;{{ number_format((float) $project->variations->where('status', 'approved')->sum(fn($v) => $v->type === 'additional' ? $v->amount : -$v->amount), 2) }}</td>
        </tr>
        <tr>
            <th>Payments Total</th>
            <td>&#8377;{{ number_format((float) $project->payments->where('status', 'paid')->sum('amount'), 2) }}</td>
        </tr>
        <tr class="total">
            <th>Final Bill</th>
            <td>&#8377;{{ number_format((float) $project->final_bill, 2) }}</td>
        </tr>
    </table>

    <table class="footer">
        <tr>
            <td>
                <strong>Office:</strong> 20 A, Nerhu Street,Sathyomoorthy Nagar,<br>
                Madurai - 625010.<br>
                <strong>Contact us:</strong> +91-452 796 9211.
            </td>
            <td class="center">www.housefix360.com</td>
            <td class="right">Q no: {{ $quotationNo }}</td>
        </tr>
    </table>
</body>

</html>
