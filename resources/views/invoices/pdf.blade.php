<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 40px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 40px;
            padding-bottom: 150px;
        }

        .logo-top-right {
            position: absolute;
            top: 20px;
            right: 20px;
            max-width: 250px;
            max-height: 150px;
        }

        .page {
            position: relative;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: white;
            border-top: 1px solid #ddd;
            padding: 15px 40px;
            font-size: 10px;
            color: #666;
        }

        .footer-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            padding: 0;
            vertical-align: top;
            line-height: 1.4;
        }

        .footer-left {
            width: 33.33%;
            padding-right: 10px;
        }

        .footer-center {
            width: 33.33%;
            padding: 0 10px;
        }

        .footer-right {
            width: 33.33%;
            padding-left: 10px;
            text-align: right;
        }

        .invoice-header {
            margin-bottom: 40px;
            margin-top: 60px;
            border-bottom: 2px solid #333;
            padding-bottom: 30px;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 40px;
            margin-top: 0;
        }

        .invoice-meta {
            display: table;
            width: 100%;
        }

        .invoice-meta-item {
            display: table-cell;
            width: 50%;
        }

        .meta-label {
            font-weight: bold;
            font-size: 11px;
            color: #666;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 14px;
            color: #333;
        }

        .customer-info {
            margin-top: 30px;
            margin-bottom: 40px;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }

        .customer-info-label {
            font-weight: bold;
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .invoice-message {
            margin-bottom: 40px;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }

        .worklogs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            font-size: 11px;
        }

        .worklogs-table thead {
            background-color: #333;
            color: white;
        }

        .worklogs-table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #333;
        }

        .worklogs-table td {
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .worklogs-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .worklogs-table .text-right {
            text-align: right;
        }

        .worklogs-table .text-center {
            text-align: center;
        }

        .project-section {
            margin-bottom: 40px;
        }

        .project-name {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 10px;
            color: #333;
        }

        .project-total {
            font-weight: bold;
            text-align: right;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .invoice-total {
            font-weight: bold;
            font-size: 14px;
            text-align: right;
            padding: 15px;
            background-color: #333;
            color: white;
            border: 1px solid #333;
            margin-top: 30px;
        }

        .payment-terms {
            margin-top: 40px;
            font-size: 11px;
            line-height: 1.6;
            color: #333;
        }

        .payment-terms-label {
            font-weight: bold;
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .page-number {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="page">
        @if($company['company_logo'] ?? false)
        <img src="{{ $company['company_logo'] }}" alt="Logo" class="logo-top-right">
        @endif

        <div class="invoice-header">
            <h1 class="invoice-title">INVOICE</h1>
            <div class="invoice-meta">
                <div class="invoice-meta-item">
                    <div class="meta-label">Date</div>
                    <div class="meta-value">
                        @php
                            // Convert Laravel date format to PHP format
                            $phpFormat = str_replace(
                                ['DD', 'MM', 'YYYY'],
                                ['d', 'm', 'Y'],
                                $date_format ?? 'DD/MM/YYYY'
                            );
                        @endphp
                        {{ $invoice->issue_date->format($phpFormat) }}
                    </div>
                </div>
                <div class="invoice-meta-item" style="text-align: right;">
                    <div class="meta-label">Invoice Number</div>
                    <div class="meta-value">{{ $invoice->invoice_number }}</div>
                </div>
            </div>
        </div>

        <div class="customer-info">
            <div class="customer-info-label">Bill To</div>
            <div>
                <strong>{{ $invoice->customer->name ?? 'Customer' }}</strong><br>
                @if($invoice->customer->address_line_1 ?? false)
                    {{ $invoice->customer->address_line_1 }}
                    @if($invoice->customer->address_line_2 ?? false) {{ $invoice->customer->address_line_2 }} @endif
                    <br>
                @endif
                @if($invoice->customer->postcode ?? false)
                    {{ $invoice->customer->postcode }}
                    @if($invoice->customer->city ?? false) {{ $invoice->customer->city }} @endif
                    <br>
                @endif
                @if($invoice->customer->contact_phone ?? false){{ $invoice->customer->contact_phone }}<br>@endif
                @if($invoice->customer->contact_email ?? false){{ $invoice->customer->contact_email }}@endif
            </div>
        </div>

        @if($company['invoice_default_message'] ?? false)
        <div class="invoice-message">
            {{ $company['invoice_default_message'] }}
        </div>
        @endif

        @php
            // Group work logs by project
            $workLogsByProject = $invoice->workLogs->groupBy(function($workLog) {
                return $workLog->project->name ?? 'Ungrouped';
            });
            $totalAmount = 0;
        @endphp

        @forelse($workLogsByProject as $projectName => $projectWorkLogs)
        <div class="project-section">
            <div class="project-name">{{ $projectName }}</div>
            
            <table class="worklogs-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Worker</th>
                        <th>Description</th>
                        <th class="text-right">Rate/Unit</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $projectTotal = 0; @endphp
                    @foreach($projectWorkLogs as $workLog)
                    @php 
                        $lineTotal = ($workLog->hourly_rate ?? 0) * ($workLog->hours_worked ?? 0);
                        $projectTotal += $lineTotal;
                        $totalAmount += $lineTotal;
                    @endphp
                    <tr>
                        <td>
                            @php
                                $phpFormat = str_replace(['DD', 'MM', 'YYYY'], ['d', 'm', 'Y'], $date_format ?? 'DD/MM/YYYY');
                                $dateObj = is_string($workLog->date) ? \Carbon\Carbon::parse($workLog->date) : $workLog->date;
                            @endphp
                            {{ $dateObj->format($phpFormat) }}
                        </td>
                        <td>{{ $workLog->user->name ?? 'N/A' }}</td>
                        <td>{{ $workLog->description ?? '-' }}</td>
                        <td class="text-right">{{ $currency_symbol }}{{ number_format($workLog->hourly_rate ?? 0, 2) }}</td>
                        <td class="text-right">{{ number_format($workLog->hours_worked ?? 0, 2) }}</td>
                        <td class="text-right">{{ $currency_symbol }}{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="project-total">
                Project Total: {{ $currency_symbol }}{{ number_format($projectTotal, 2) }}
            </div>
        </div>
        @empty
        <p>No work logs found</p>
        @endforelse

        <div style="margin-top: 30px;">
            <div style="text-align: right; margin-bottom: 10px;">
                <strong>Subtotal: {{ $currency_symbol }}{{ number_format($totalAmount, 2) }}</strong>
            </div>
            @if($tax_rate > 0)
            <div style="text-align: right; margin-bottom: 10px; font-size: 11px;">
                Tax ({{ number_format($tax_rate, 2) }}%): {{ $currency_symbol }}{{ number_format($totalAmount * ($tax_rate / 100), 2) }}
            </div>
            @endif
        </div>

        <div class="invoice-total">
            Total: {{ $currency_symbol }}{{ number_format($totalAmount + ($totalAmount * ($tax_rate / 100)), 2) }}
        </div>

        @if($company['invoice_payment_terms'] ?? false)
        <div class="payment-terms">
            <div class="payment-terms-label">Payment Terms</div>
            {!! nl2br(e($company['invoice_payment_terms'])) !!}
        </div>
        @endif
    </div>

    <footer class="footer">
        <table class="footer-table">
            <tr>
                @php
                    $footerColumns = [
                        $company['invoice_footer_col1'] ?? 'company_info',
                        $company['invoice_footer_col2'] ?? 'bank_info',
                        $company['invoice_footer_col3'] ?? 'page_info'
                    ];
                    $columnClasses = ['footer-left', 'footer-center', 'footer-right'];
                @endphp

                @foreach($footerColumns as $index => $column)
                <td class="{{ $columnClasses[$index] }}">
                    @if($column === 'company_info')
                        {{ $company['company_name'] ?? 'Company' }}<br>
                        @if($company['company_address_street'] ?? false)
                            {{ $company['company_address_street'] }}
                            @if($company['company_address_number'] ?? false) {{ $company['company_address_number'] }} @endif
                            <br>
                        @endif
                        @if($company['company_address_zipcode'] ?? false)
                            {{ $company['company_address_zipcode'] }}
                            @if($company['company_address_city'] ?? false) {{ $company['company_address_city'] }} @endif
                            <br>
                        @endif
                        @if($company['company_phone'] ?? false){{ $company['company_phone'] }}<br>@endif
                        @if($company['company_email'] ?? false){{ $company['company_email'] }}@endif
                    @elseif($column === 'bank_info')
                        @if($company['company_bank_info'] ?? false)
                            {!! nl2br(e($company['company_bank_info'])) !!}
                        @endif
                    @elseif($column === 'page_info')
                        <!-- Page numbering handled by PDF callback -->
                        &nbsp;
                    @elseif($column === 'empty')
                        <!-- Empty column -->
                    @endif
                </td>
                @endforeach
            </tr>
        </table>
    </footer>
</body>

</html>