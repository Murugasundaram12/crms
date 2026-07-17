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

        @page {
            margin: 22px 24px 54px;
        }

        body {
            font-family: 'PdfCalibri', 'Arial', sans-serif;
            font-size: 13.5px;
            color: #172033;
            line-height: 1.42;
        }

        .footer {
            position: fixed;
            left: 24px;
            right: 24px;
            bottom: 18px;
            border-top: 1px solid #d8e0ee;
            padding-top: 8px;
            color: #516073;
            font-size: 11px;
        }

        .footer td {
            vertical-align: top;
        }

        .top-band {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .brand-cell {
            width: 56%;
            background: #fff;
            color: #102a43;
            padding: 12px 16px;
            border: 1px solid #d9e2ef;
            border-radius: 8px;
        }

        .brand-name {
            font-size: 25px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 3px;
        }

        .brand-sub {
            font-size: 10px;
            letter-spacing: 1.5px;
            color: #1261a6;
        }

        .logo {
            width: 285px;
            max-height: 70px;
            object-fit: contain;
        }

        .quote-cell {
            width: 44%;
            padding: 13px 16px;
            background: #102a43;
            border: 1px solid #102a43;
            text-align: right;
            color: #fff;
        }

        .quote-title {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }

        .quote-no {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 10px;
            background: #fff;
            color: #102a43;
            border: 1px solid #fff;
            border-radius: 14px;
            font-weight: 700;
            font-size: 12.5px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .info-card {
            border: 1px solid #d9e2ef;
            border-radius: 8px;
            padding: 12px 14px;
            vertical-align: top;
            background: #fbfdff;
        }

        .spacer {
            width: 12px;
        }

        .label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .client-name,
        .project-title {
            font-size: 16px;
            font-weight: 700;
            color: #102a43;
            margin-bottom: 4px;
        }

        .muted {
            color: #64748b;
        }

        .subject {
            border-left: 4px solid #1261a6;
            background: #f2f7fd;
            padding: 9px 12px;
            margin-bottom: 12px;
            font-size: 15.5px;
            font-weight: 700;
            color: #102a43;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
            font-size: 12.5px;
            page-break-inside: auto;
        }

        table.items thead {
            display: table-header-group;
        }

        table.items tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table.items th {
            background: #102a43;
            color: #fff;
            border: 1px solid #102a43;
            padding: 7px 5px;
            text-align: center;
            font-weight: 700;
        }

        table.items td {
            border: 1px solid #d8e0ee;
            padding: 6px 5px;
            vertical-align: middle;
        }

        table.items tbody tr:nth-child(even) td {
            background: #fbfdff;
        }

        .section-row td {
            background: #eaf3fb !important;
            color: #102a43;
            font-weight: 700;
        }

        .total-row td {
            background: #102a43 !important;
            color: #fff;
            border-color: #102a43;
            font-size: 14px;
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

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 10px;
        }

        .summary-box {
            border: 1px solid #d9e2ef;
            background: #fbfdff;
            padding: 10px 12px;
            vertical-align: top;
        }

        .grand-total {
            background: #1261a6;
            color: #fff;
            padding: 10px 12px;
            text-align: right;
            font-size: 18px;
            font-weight: 700;
        }

        .section-title {
            color: #1261a6;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin: 10px 0 5px;
        }

        .terms {
            border: 1px solid #d9e2ef;
            padding: 9px 12px;
            line-height: 1.45;
            page-break-inside: avoid;
        }

        .terms div {
            margin-bottom: 4px;
        }

        .bank-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d9e2ef;
        }

        .bank-table td {
            padding: 7px 9px;
            border-bottom: 1px solid #edf2f7;
        }

        .bank-table tr:last-child td {
            border-bottom: 0;
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
                $logoDirectory . '/logo12.png',
                $logoDirectory . '/logo.png',
                $logoDirectory . '/logo.jpg',
                $logoDirectory . '/logo.jpeg',
                $logoDirectory . '/logo.webp',
            ];
            $logoPath = collect($logoCandidates)->first(fn($path) => file_exists($path));
            if (!$logoPath && is_dir($logoDirectory)) {
                $foundLogos = glob($logoDirectory . '/*.{png,jpg,jpeg,webp}', GLOB_BRACE);
                $logoPath = $foundLogos[0] ?? null;
            }
            if ($logoPath) {
                break;
            }
        }
        $hasLogo = !empty($logoPath);

        $clientName = $quotation->client_name ?: ($quotation->client?->company_name ?: ($quotation->client?->name ?? '-'));
        $clientAddress = $quotation->client_address ?: '';
        if (!$clientAddress && $quotation->client) {
            $clientAddress = collect([
                $quotation->client->address,
                $quotation->client->city,
                $quotation->client->state,
                $quotation->client->country,
            ])->map(fn($val) => trim((string) $val))->filter()->implode(', ');
        }
        $clientAddress = trim($clientAddress);
        $clientPhone = $quotation->client?->phone ?? null;
        $clientEmail = $quotation->client?->email ?? null;
        $location = $quotation->project?->location ?: ($quotation->client?->city ?? '');
        $title = $quotation->quotation_title ?: 'Project Works';
        $subject = stripos($title, 'Quotation for') === 0 ? $title : 'Quotation for ' . $title;
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

        $formatIndianCurrency = function ($num) {
            $parts = explode('.', number_format((float) $num, 2, '.', ''));
            $amount = $parts[0];
            $decimal = '.' . ($parts[1] ?? '00');
            $lastThree = substr($amount, -3);
            $remaining = substr($amount, 0, -3);
            if ($remaining !== '') {
                $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining) . ',';
            }
            return '&#8377; ' . $remaining . $lastThree . $decimal;
        };

        $formatDim = function ($val) {
            if ($val === null || $val === '') {
                return '';
            }
            $num = (float) $val;
            if ($num == 0) {
                return '';
            }
            return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
        };

        $formatPrice = function ($val) {
            $num = (float) $val;
            return $num == (int) $num ? (string) (int) $num : number_format($num, 2, '.', '');
        };

        $getFiscalYear = function ($date) {
            $date = $date ?: now();
            $year = (int) $date->format('Y');
            $month = (int) $date->format('n');
            $fyStart = $month >= 4 ? $year : $year - 1;
            return substr((string) $fyStart, -2) . '-' . substr((string) ($fyStart + 1), -2);
        };
        $fiscalYear = $getFiscalYear($quotation->quotation_date);

        $renderTerm = function ($term, $index = null) {
            $termStr = trim((string) $term);
            if ($termStr === '') {
                return '';
            }
            if (stripos($termStr, 'Please note:') === 0) {
                $rest = trim(substr($termStr, 12));
                return '<div><strong>Please note:</strong> ' . nl2br(e($rest)) . '</div>';
            }
            $cleaned = preg_replace('/^\d+\.\s*/', '', $termStr);
            $prefix = $index !== null ? '<strong>' . $index . '.</strong> ' : '';
            return '<div>' . $prefix . nl2br(e($cleaned)) . '</div>';
        };
    @endphp

    <table class="footer" style="width: 100%;">
        <tr>
            <td style="width: 45%;">
                <strong>Office:</strong> 20 A, Nerhu Street, Sathyomoorthy Nagar, Madurai - 625010.
            </td>
            <td style="width: 25%; text-align: center; color: #1261a6; font-weight: 700;">
                www.housefix360.com
            </td>
            <td style="width: 30%; text-align: right;">
                <strong>Contact:</strong> +91-452 796 9211<br>
                Q no: {{ $quotation->id }} - FY {{ $fiscalYear }}
            </td>
        </tr>
    </table>

    <table class="top-band">
        <tr>
            <td class="brand-cell">
                @if($hasLogo)
                    <img class="logo" src="{{ $logoPath }}" alt="Housefix">
                @else
                    <div class="brand-name">HOUSEFIX</div>
                    <div class="brand-sub">A DOCTOR FOR YOUR HOUSE</div>
                @endif
            </td>
            <td class="quote-cell">
                <div class="quote-title">QUOTATION</div>
                <div class="quote-no">{{ $quotation->quotation_number ?? ('QTN-' . $quotation->id) }}</div>
                <div style="margin-top: 8px;" class="muted">
                    Create Date: {{ optional($quotation->quotation_date)->format('d M Y') ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="info-card" style="width: 50%;">
                <div class="label">Prepared For</div>
                <div class="client-name">{{ $clientName }}</div>
                @if($clientAddress)
                    <div>{!! nl2br(e($clientAddress)) !!}</div>
                @endif
                @if($clientPhone || $clientEmail)
                    <div class="muted" style="margin-top: 5px;">
                        {{ collect([$clientPhone, $clientEmail])->filter()->implode(' | ') }}
                    </div>
                @endif
            </td>
            <td class="spacer"></td>
            <td class="info-card" style="width: 50%;">
                <div class="label">Project Details</div>
                <div class="project-title">{{ $quotation->project?->name ?? $quotation->quotation_title ?? '-' }}</div>
                @if($location)
                    <div><strong>Location:</strong> {{ $location }}</div>
                @endif
                <div class="muted" style="margin-top: 5px;">
                    Work Duration: {{ $quotation->duration_days ? $quotation->duration_days . ' working days' : '10 - 15 working days' }}
                    &nbsp; | &nbsp;
                    Quote Validity: {{ $quotation->validity_days ?: 30 }} days
                </div>
            </td>
        </tr>
    </table>

    <div class="subject">{{ $subject }}</div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%">S.No</th>
                <th style="width:43%">Description</th>
                <th style="width:5%">Nos</th>
                <th style="width:5%">L</th>
                <th style="width:5%">B</th>
                <th style="width:5%">D</th>
                <th style="width:7%">Qty</th>
                <th style="width:6%">Unit</th>
                <th style="width:8%">Rate</th>
                <th style="width:11%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedItems as $group)
                <tr class="section-row">
                    <td class="c">{{ $sNo++ }}</td>
                    <td colspan="9">{{ $group['main_title'] }}</td>
                </tr>
                @foreach($group['rows'] as $idx => $row)
                    @php
                        $hasDimensions = !empty($row['length']) || !empty($row['breadth']) || !empty($row['depth']);
                        $isLumpsum = !$hasDimensions;
                    @endphp
                    <tr>
                        <td class="c"><em>{{ $toRoman($idx + 1) }}</em></td>
                        <td>{{ $row['description'] }}</td>
                        <td class="c">{{ ($row['nos'] !== null && (float) $row['nos'] > 0) ? $formatDim($row['nos']) : '' }}</td>
                        <td class="c">{{ $formatDim($row['length']) }}</td>
                        <td class="c">{{ $isLumpsum ? 'Lumpsum' : $formatDim($row['breadth']) }}</td>
                        <td class="c">{{ $formatDim($row['depth']) }}</td>
                        <td class="c">{{ $isLumpsum ? '' : number_format((float) ($row['quantity'] ?? 0), 2) }}</td>
                        <td class="c">{{ $isLumpsum ? '' : ($row['unit'] ?: '') }}</td>
                        <td class="r">{{ $formatPrice($row['price']) }}</td>
                        <td class="r bold">{!! $formatIndianCurrency($row['amount']) !!}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="9" class="r">Total excluding GST</td>
                <td class="r">{!! $formatIndianCurrency($grandTotal) !!}</td>
            </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-box" style="width: 55%;">
                <div class="section-title" style="margin-top: 0;">Bank Details</div>
                <table class="bank-table">
                    <tr>
                        <td style="width: 24%;" class="muted">Account Name</td>
                        <td class="bold">{{ $bankDetails['account_name'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Bank</td>
                        <td>{{ $bankDetails['bank_name'] ?? '-' }}{{ !empty($bankDetails['branch']) ? ' - ' . $bankDetails['branch'] : '' }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Account No</td>
                        <td>{{ $bankDetails['account_number'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="muted">IFSC</td>
                        <td>{{ $bankDetails['ifsc'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td class="spacer"></td>
            <td style="width: 45%; vertical-align: bottom;">
                <div class="grand-total">
                    Grand Total: {!! $formatIndianCurrency($grandTotal) !!}
                </div>
                <div class="muted" style="text-align: right; margin-top: 7px;">
                    *GST not applicable / excluded unless mentioned separately.
                </div>
            </td>
        </tr>
    </table>

    @if(!empty($pdfTerms))
        <div class="section-title">Terms and Conditions</div>
        <div class="terms">
            @php $i = 1; @endphp
            @foreach($pdfTerms as $term)
                {!! $renderTerm($term, $i++) !!}
            @endforeach
        </div>
    @endif
</body>

</html>
