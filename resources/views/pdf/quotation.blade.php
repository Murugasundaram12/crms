<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotation_number ?? $quotation->id }}</title>
    <style>
        @page {
            margin: 10px 12px 18px 12px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .top {
            width: 100%;
            margin-bottom: 8px;
        }

        .top td {
            vertical-align: top;
        }

        .brand {
            text-align: right;
            font-size: 40px;
            letter-spacing: 6px;
            color: #3c7fc0;
            font-weight: 700;
            line-height: 0.85;
        }

        .brand-sub {
            text-align: right;
            font-size: 10px;
            letter-spacing: 3px;
            color: #3c7fc0;
        }

        .date {
            text-align: right;
            font-size: 16px;
            font-weight: 700;
            margin-top: 2px;
        }

        .logo-wrap {
            text-align: right;
            margin-bottom: 2px;
        }

        .logo-wrap img {
            max-width: 180px;
            max-height: 54px;
        }

        .to-block {
            margin: 6px 0 8px;
            line-height: 1.35;
        }

        .title {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            text-decoration: underline;
            margin: 6px 0 8px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.items th,
        table.items td {
            border: 1px solid #222;
            padding: 2px 4px;
        }

        table.items th {
            text-align: center;
            font-weight: 700;
        }

        .c {
            text-align: center;
        }

        .r {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .blue-line {
            width: 100%;
            margin: 8px 0 6px;
            color: #0068bf;
            font-size: 10px;
        }

        .blue-line td {
            width: 50%;
        }

        .account-title,
        .terms-title {
            color: #0068bf;
            font-weight: 700;
            margin: 6px 0 3px;
        }

        .terms {
            line-height: 1.4;
        }

        .page-break {
            page-break-before: always;
        }

        .cont-head {
            width: 100%;
            margin-bottom: 6px;
        }

        .cont-head td {
            vertical-align: top;
        }

        .cont-logo {
            text-align: right;
        }

        .cont-logo img {
            max-width: 170px;
            max-height: 50px;
        }

        .footer {
            position: fixed;
            left: 12px;
            right: 12px;
            bottom: 10px;
            font-size: 11px;
        }

        .footer td {
            vertical-align: bottom;
        }

        .footer .mid {
            text-align: center;
            color: #0068bf;
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
        $clientName = $quotation->client_name ?: ($quotation->client?->name ?? '-');
        $clientAddress = $quotation->client_address ?: ($quotation->client?->address ?? '-');
        $location = $quotation->project?->location ?: ($quotation->client?->city ?? '-');
        $grandTotal = (float) ($quotation->total_amount ?? $quotation->amount ?? 0);
        $sNo = 1;
        $toRoman = function (int $n): string {
            $map = [1000 => 'm', 900 => 'cm', 500 => 'd', 400 => 'cd', 100 => 'c', 90 => 'xc', 50 => 'l', 40 => 'xl', 10 => 'x', 9 => 'ix', 5 => 'v', 4 => 'iv', 1 => 'i'];
            $out = '';
            foreach ($map as $v => $sym) {
                while ($n >= $v) {
                    $out .= $sym;
                    $n -= $v;
                }
            }
            return $out;
        };
        $termsFirst = collect($pdfTerms)->take(4)->values();
        $termsRest = collect($pdfTerms)->slice(4)->values();
    @endphp

    <table class="top">
        <tr>
            <td style="width:65%"></td>
            <td style="width:35%">
                @if($hasLogo)
                    <div class="logo-wrap">
                        <img src="{{ $logoPath }}" alt="Housefix Logo">
                    </div>
                @else
                    <div class="brand">HOUSEFIX</div>
                    <div class="brand-sub">A DOCTOR FOR YOUR HOUSE</div>
                @endif
                <div class="date">Date : {{ optional($quotation->quotation_date)->format('d/m/Y') ?? '-' }}.</div>
            </td>
        </tr>
    </table>

    <div class="to-block">
        <div><strong>To,</strong></div>
        <div style="margin-left: 42px;">
            {{ $clientName }},<br>
            {{ str_replace(',', ",\n", $clientAddress) }}
        </div>
    </div>

    <div style="margin-bottom: 4px;">Location : {{ $location }}.</div>
    <div class="title">{{ $quotation->quotation_title ?: 'Quotation for work' }}</div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%">S.no</th>
                <th style="width:46%">Description</th>
                <th style="width:5%">Nos</th>
                <th style="width:5%">L</th>
                <th style="width:5%">B</th>
                <th style="width:5%">D</th>
                <th style="width:6%">Qty</th>
                <th style="width:5%">Unit</th>
                <th style="width:8%">Price</th>
                <th style="width:10%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedItems as $group)
                <tr>
                    <td class="c bold">{{ $sNo++ }}</td>
                    <td class="bold">{{ $group['main_title'] }}</td>
                    <td colspan="8"></td>
                </tr>
                @foreach($group['rows'] as $idx => $row)
                    <tr>
                        <td class="c"><em>{{ $toRoman($idx + 1) }}</em></td>
                        <td>{{ $row['description'] }}</td>
                        <td class="c">{{ rtrim(rtrim(number_format((float) ($row['nos'] ?? 0), 2), '0'), '.') }}</td>
                        <td class="c">{{ rtrim(rtrim(number_format((float) ($row['length'] ?? 0), 2), '0'), '.') }}</td>
                        <td class="c">{{ rtrim(rtrim(number_format((float) ($row['breadth'] ?? 0), 2), '0'), '.') }}</td>
                        <td class="c">{{ rtrim(rtrim(number_format((float) ($row['depth'] ?? 0), 2), '0'), '.') }}</td>
                        <td class="c">{{ number_format((float) ($row['quantity'] ?? 0), 2) }}</td>
                        <td class="c">{{ $row['unit'] ?: '-' }}</td>
                        <td class="r">{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                        <td class="r bold">&#8377; {{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr>
                <td colspan="9" class="c bold">Total* (excluding GST)</td>
                <td class="r bold">&#8377; {{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="blue-line">
        <tr>
            <td>Duration:
                {{ $quotation->duration_days ? $quotation->duration_days . ' working days' : '10 - 15 working days' }}.
            </td>
            <td class="r">Validity: This estimate is valid for {{ $quotation->validity_days ?: 30 }} days.</td>
        </tr>
    </table>

    <div class="account-title">Account details:</div>
    <div>
        <strong>Name:</strong> {{ $bankDetails['account_name'] ?? '-' }}.<br>
        <strong>Acc no:</strong> {{ $bankDetails['account_number'] ?? '-' }}.<br>
        <strong>IFS Code:</strong> {{ $bankDetails['ifsc'] ?? '-' }}.
    </div>

    <div class="terms-title">Terms and Conditions:</div>
    <div class="terms">
        @php $i = 1; @endphp
        @foreach($termsFirst as $term)
            <div><strong>{{ $i++ }}.</strong> {!! nl2br(e($term)) !!}</div>
        @endforeach
    </div>

    <table class="footer">
        <tr>
            <td>
                <strong>Office:</strong> 20 A, Nerhu Street,Sathyomoorthy Nagar,<br>
                Madurai - 625010.<br>
                <strong>Contact us:</strong> +91-452 796 9211.
            </td>
            <td class="mid">www.housefix360.com</td>
            <td class="right">Q no: {{ $quotation->quotation_number ?? ('#' . $quotation->id) }}.</td>
        </tr>
    </table>

    @if($termsRest->isNotEmpty())
        <div class="page-break"></div>

        <table class="cont-head">
            <tr>
                <td style="width:70%"></td>
                <td style="width:30%" class="cont-logo">
                    @if($hasLogo)
                        <img src="{{ $logoPath }}" alt="Housefix Logo">
                    @endif
                </td>
            </tr>
        </table>

        <div class="terms">
            @foreach($termsRest as $term)
                <div><strong>{{ $i++ }}.</strong> {!! nl2br(e($term)) !!}</div>
            @endforeach
        </div>

        <table class="footer">
            <tr>
                <td>
                    <strong>Office:</strong> 20 A, Nerhu Street,Sathyomoorthy Nagar,<br>
                    Madurai - 625010.<br>
                    <strong>Contact us:</strong> +91-452 796 9211.
                </td>
                <td class="mid">www.housefix360.com</td>
                <td class="right">Q no: {{ $quotation->quotation_number ?? ('#' . $quotation->id) }}.</td>
            </tr>
        </table>
    @endif
</body>

</html>

