<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotation_number ?? $quotation->id }}</title>
    <style>
        @font-face {
            font-family: 'PdfCalibri';
            font-style: normal;
            font-weight: 400;
            src: url("{{ public_path('assets/fonts/pdf/calibri.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: 'PdfCalibri';
            font-style: normal;
            font-weight: 700;
            src: url("{{ public_path('assets/fonts/pdf/calibrib.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: 'PdfArial';
            font-style: normal;
            font-weight: 400;
            src: url("{{ public_path('assets/fonts/pdf/arial.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: 'PdfArial';
            font-style: normal;
            font-weight: 700;
            src: url("{{ public_path('assets/fonts/pdf/arialbd.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: 'PdfArial';
            font-style: italic;
            font-weight: 400;
            src: url("{{ public_path('assets/fonts/pdf/ariali.ttf') }}") format("truetype");
        }

        @page {
            margin: 60px 20px 75px 20px;
        }

        body {
            font-family: 'PdfCalibri', 'Calibri', 'PdfArial', 'Arial', sans-serif;
            font-size: 14px;
            color: #000;
            line-height: 1.42;
        }

        .header {
            position: fixed;
            top: -45px;
            right: 20px;
            left: 20px;
            height: 40px;
            text-align: right;
        }

        .brand {
            font-family: 'PdfArial', 'Arial', sans-serif;
            font-size: 32px;
            letter-spacing: 5px;
            color: #3c7fc0;
            font-weight: 700;
            line-height: 0.85;
        }

        .brand-sub {
            font-family: 'PdfArial', 'Arial', sans-serif;
            font-size: 9px;
            letter-spacing: 2px;
            color: #3c7fc0;
            margin-top: 2px;
        }

        .logo-wrap {
            margin-bottom: 2px;
        }

        .logo-wrap img {
            max-width: 180px;
            max-height: 54px;
        }

        .date-container {
            text-align: right;
            font-size: 15px;
            font-weight: 700;
            margin-top: 0px;
            margin-bottom: 10px;
        }

        .to-block {
            margin: 8px 0;
            line-height: 1.48;
        }

        .location-block {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .title {
            font-family: 'PdfArial', 'Arial', sans-serif;
            text-align: center;
            font-size: 19px;
            font-weight: 700;
            text-decoration: underline;
            margin: 8px 0 10px;
        }

        table.items {
            font-family: 'PdfCalibri', 'Calibri', 'PdfArial', 'Arial', sans-serif;
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            line-height: 1.35;
            page-break-inside: auto;
        }

        table.items tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table.items thead {
            display: table-header-group;
        }

        table.items th,
        table.items td {
            border: 1.4px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            color: #000;
        }

        table.items th {
            font-family: 'PdfArial', 'Arial', sans-serif;
            text-align: center;
            font-weight: 700;
            font-size: 14px;
            background: #fff;
        }

        table.items .section-row td {
            font-family: 'PdfArial', 'Arial', sans-serif;
            font-weight: 700;
        }

        table.items .total-row td {
            font-family: 'PdfArial', 'Arial', sans-serif;
            font-weight: 700;
            border-top: 1.8px solid #000;
        }

        .c {
            text-align: center;
        }

        .r {
            text-align: right;
        }

        .bold {
            font-weight: 700;
            font-family: 'PdfArial', 'Arial', sans-serif;
        }

        .blue-line {
            width: 100%;
            margin: 8px 0;
            color: #0068bf;
            font-weight: 700;
            font-size: 14px;
            page-break-inside: avoid;
        }

        .blue-line td {
            width: 50%;
        }

        .account-block {
            page-break-inside: avoid;
        }

        .account-title,
        .terms-title {
            color: #0068bf;
            font-weight: 700;
            margin-top: 10px;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .account-details {
            line-height: 1.48;
        }

        .terms {
            line-height: 1.48;
        }

        .terms>div {
            page-break-inside: avoid;
        }

        .footer {
            position: fixed;
            left: 20px;
            right: 20px;
            bottom: 15px;
            font-size: 13px;
            border-collapse: collapse;
        }

        .footer td {
            vertical-align: bottom;
            font-size: 13px;
            line-height: 1.42;
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
        $logoDirectories = [
            public_path('assets/pdf'),
            public_path('assets/img/pdf'),
        ];
        $logoPath = null;
        foreach ($logoDirectories as $logoDirectory) {
            $logoCandidates = [
                $logoDirectory . '/logo.png',
                $logoDirectory . '/logo.jpg',
                $logoDirectory . '/logo.jpeg',
                $logoDirectory . '/logo.webp',
            ];
            $logoPath = collect($logoCandidates)->first(fn($path) => file_exists($path));
            if (!$logoPath) {
                $foundLogos = glob($logoDirectory . '/*.{png,jpg,jpeg,webp}', GLOB_BRACE);
                $logoPath = $foundLogos[0] ?? null;
            }
            if ($logoPath) {
                break;
            }
        }
        $hasLogo = !empty($logoPath);

        // Client info formatting
        $clientName = $quotation->client_name ?: ($quotation->client?->company_name ?: ($quotation->client?->name ?? '-'));

        $clientAddress = $quotation->client_address ?: '';
        if (!$clientAddress && $quotation->client) {
            $parts = collect([
                $quotation->client->address,
                $quotation->client->city,
                $quotation->client->state,
                $quotation->client->country
            ])->map(fn($val) => trim((string) $val))->filter()->values();
            $clientAddress = $parts->implode(", ");
        }

        $clientAddressCleaned = rtrim(trim($clientAddress), " \t\n\r\0\x0B.,");
        $clientAddressLines = array_map('trim', explode(',', $clientAddressCleaned));
        $clientAddressFormatted = implode(",\n", $clientAddressLines) . '.';

        // Location formatting
        $location = $quotation->project?->location ?: ($quotation->client?->city ?? '');
        $locationFormatted = '';
        if ($location) {
            $locationFormatted = rtrim(trim($location), " \t\n\r\0\x0B.,") . '.';
        }

        // Title formatting
        $title = $quotation->quotation_title ?: 'PCC works and flooring works';
        if ($title && stripos($title, 'Quotation for') !== 0) {
            $title = 'Quotation for ' . $title;
        }

        // Grand total formatting
        $grandTotal = (float) ($quotation->total_amount ?? $quotation->amount ?? 0);
        $sNo = 1;

        // Roman numeral helper
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

        // Indian Currency Formatter helper
        $formatIndianCurrency = function ($num) {
            $parts = explode('.', number_format((float) $num, 2, '.', ''));
            $amount = $parts[0];
            $decimal = isset($parts[1]) ? '.' . $parts[1] : '.00';

            $lastThree = substr($amount, -3);
            $remaining = substr($amount, 0, -3);
            if ($remaining != '') {
                $remaining = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $remaining) . ',';
            }
            return '&#8377; ' . $remaining . $lastThree . $decimal;
        };

        // Dimension Formatter helper
        $formatDim = function ($val) {
            if ($val === null || $val === '')
                return '';
            $num = (float) $val;
            if ($num == 0)
                return '';
            return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
        };

        // Price Formatter helper
        $formatPrice = function ($val) {
            $num = (float) $val;
            if ($num == (int) $num) {
                return (int) $num;
            }
            return number_format($num, 2, '.', '');
        };

        // Fiscal Year Helper
        $getFiscalYear = function ($date) {
            if (!$date)
                $date = now();
            $year = (int) $date->format('Y');
            $month = (int) $date->format('n');
            if ($month >= 4) {
                $fyStart = $year;
                $fyEnd = $year + 1;
            } else {
                $fyStart = $year - 1;
                $fyEnd = $year;
            }
            return substr((string) $fyStart, -2) . '-' . substr((string) $fyEnd, -2);
        };
        $fiscalYear = $getFiscalYear($quotation->quotation_date);

        // Terms Renderer Helper
        $renderTerm = function ($term, $index = null) {
            $termStr = trim((string) $term);
            if ($termStr === '')
                return '';

            if (stripos($termStr, 'Please note:') === 0) {
                $rest = trim(substr($termStr, 12));
                return '<div style="margin-top: 4px;"><strong>Please note:</strong> ' . nl2br(e($rest)) . '</div>';
            }

            $cleaned = preg_replace('/^\d+\.\s*/', '', $termStr);
            $prefix = $index !== null ? '<strong>' . $index . '.</strong> ' : '';
            return '<div>' . $prefix . nl2br(e($cleaned)) . '</div>';
        };
    @endphp

    <!-- Fixed Header repeating on every page -->
    <div class="header">
        @if($hasLogo)
            <div class="logo-wrap">
                <img src="{{ $logoPath }}" alt="Housefix Logo">
            </div>
        @else
            <div class="brand">HOUSEFIX</div>
            <div class="brand-sub">A DOCTOR FOR YOUR HOUSE</div>
        @endif
    </div>

    <!-- Fixed Footer repeating on every page -->
    <table class="footer" style="width: 100%;">
        <tr>
            <td colspan="3" style="border-bottom: 1px solid #ddd; padding-bottom: 2px; margin-bottom: 2px;">
                <strong>Office:</strong> 20 A, Nerhu Street,Sathyomoorthy Nagar, Madurai - 625010.
            </td>
        </tr>
        <tr>
            <td style="width: 40%; padding-top: 2px;">
                <strong>Contact us:</strong> +91-452 796 9211.
            </td>
            <td class="mid" style="width: 30%; padding-top: 2px;">
                www.housefix360.com
            </td>
            <td class="right" style="width: 30%; padding-top: 2px;">
                Q no: {{ $quotation->id }} - FY {{ $fiscalYear }}.
            </td>
        </tr>
    </table>

    <!-- Body contents -->
    <div class="date-container">
        Date : {{ optional($quotation->quotation_date)->format('d/m/Y') ?? '-' }}.
    </div>

    <div class="to-block">
        <div><strong>To,</strong></div>
        <div style="margin-left: 42px;">
            {{ $clientName }},<br>
            {!! nl2br(e($clientAddressFormatted)) !!}
        </div>
    </div>

    @if($locationFormatted)
        <div class="location-block">Location : {{ $locationFormatted }}</div>
    @endif
    <div class="title">{{ $title }}</div>

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
                <tr class="section-row">
                    <td class="c bold">{{ $sNo++ }}</td>
                    <td class="bold">{{ $group['main_title'] }}</td>
                    <td colspan="8"></td>
                </tr>
                @foreach($group['rows'] as $idx => $row)
                    @php
                        $hasDimensions = !empty($row['length']) || !empty($row['breadth']) || !empty($row['depth']);
                        $isLumpsum = !$hasDimensions;
                    @endphp
                    <tr>
                        <td class="c"><em>{{ $toRoman($idx + 1) }}</em></td>
                        <td>{{ $row['description'] }}</td>

                        <!-- Nos -->
                        <td class="c">
                            {{ ($row['nos'] !== null && (float) $row['nos'] > 0) ? $formatDim($row['nos']) : '' }}
                        </td>

                        <!-- L -->
                        <td class="c">
                            {{ $formatDim($row['length']) }}
                        </td>

                        <!-- B -->
                        <td class="c">
                            @if($isLumpsum)
                                Lumsum
                            @else
                                {{ $formatDim($row['breadth']) }}
                            @endif
                        </td>

                        <!-- D -->
                        <td class="c">
                            {{ $formatDim($row['depth']) }}
                        </td>

                        <!-- Qty -->
                        <td class="c">
                            {{ $isLumpsum ? '' : number_format((float) ($row['quantity'] ?? 0), 2) }}
                        </td>

                        <!-- Unit -->
                        <td class="c">
                            {{ $isLumpsum ? '' : ($row['unit'] ?: '') }}
                        </td>

                        <!-- Price -->
                        <td class="r">
                            {{ $formatPrice($row['price']) }}
                        </td>

                        <!-- Amount -->
                        <td class="r bold">
                            {!! $formatIndianCurrency($row['amount']) !!}
                        </td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="9" class="c bold">Total* (excluding GST)</td>
                <td class="r bold">{!! $formatIndianCurrency($grandTotal) !!}</td>
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

    <div class="account-block">
        <div class="account-title">Account details:</div>
        <div class="account-details">
            <strong>Name:</strong> {{ $bankDetails['account_name'] ?? '-' }}.<br>
            <strong>Acc no:</strong> {{ $bankDetails['account_number'] ?? '-' }}.<br>
            <strong>IFS Code:</strong> {{ $bankDetails['ifsc'] ?? '-' }}.
        </div>
    </div>

    @if(!empty($pdfTerms))
        <div class="terms-block">
            <div class="terms-title">Terms and Conditions:</div>
            <div class="terms">
                @php $i = 1; @endphp
                @foreach($pdfTerms as $term)
                    <div>{!! $renderTerm($term, $i++) !!}</div>
                @endforeach
            </div>
        </div>
    @endif
</body>

</html>
