<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php
        // Appearance settings resolved and validated in InvoicePdfGenerator
        $accent = $appearance['accent'];
        $accentText = $appearance['accent_text'];
        $compact = $appearance['density'] === 'compact';
        $filledTables = $appearance['table_style'] === 'filled';
        $cellPadding = $compact ? '6px' : '10px';
        $tableFontSize = $compact ? '10px' : '11px';
        $bodyFontSize = $compact ? '11px' : '12px';
        $lineHeight = $compact ? '1.4' : '1.6';
    @endphp
    <style>
        {!! $appearance['font_faces'] !!}

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            {{-- Raw output: value comes from an allowlist in InvoicePdfGenerator, never user input --}}
            font-family: {!! $appearance['font_family'] !!};
            font-size: {{ $bodyFontSize }};
            line-height: {{ $lineHeight }};
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .logo-top-right {
            position: absolute;
            top: 0;
            right: 0;
            max-width: 250px;
            max-height: 150px;
        }

        .page {
            position: relative;
        }

        .invoice-header {
            margin-bottom: 40px;
            margin-top: 60px;
            border-bottom: 2px solid {{ $accent }};
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
            font-size: {{ $tableFontSize }};
        }

        .worklogs-table thead {
            display: table-header-group;
            @if($filledTables)
            background-color: {{ $accent }};
            color: {{ $accentText }};
            @endif
        }

        .worklogs-table th {
            padding: {{ $cellPadding }};
            text-align: left;
            font-weight: bold;
            @if($filledTables)
            border: 1px solid {{ $accent }};
            @else
            color: #333;
            border: none;
            border-bottom: 2px solid {{ $accent }};
            @endif
        }

        .worklogs-table td {
            padding: {{ $cellPadding }};
            vertical-align: top;
            @if($filledTables)
            border: 1px solid #ddd;
            @else
            border: none;
            border-bottom: 1px solid #eee;
            @endif
        }

        @if($filledTables)
        .worklogs-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        @endif

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
            padding: {{ $cellPadding }};
            margin-bottom: 20px;
            @if($filledTables)
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            @else
            border-top: 1px solid {{ $accent }};
            @endif
        }

        .invoice-total {
            font-weight: bold;
            font-size: 14px;
            text-align: right;
            padding: 15px;
            margin-top: 30px;
            @if($filledTables)
            background-color: {{ $accent }};
            color: {{ $accentText }};
            border: 1px solid {{ $accent }};
            @else
            color: #333;
            border-top: 2px solid {{ $accent }};
            @endif
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

    </style>
</head>

<body>
    <div class="page">
        @if($company['company_logo'] ?? false)
        <img src="{{ $company['company_logo'] }}" alt="Logo" class="logo-top-right">
        @endif

        <div class="invoice-header">
            <h1 class="invoice-title">{{ __('notifications.invoice.title') }}</h1>
            <div class="invoice-meta">
                <div class="invoice-meta-item">
                    <div class="meta-label">{{ __('notifications.invoice.date') }}</div>
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
                    <div class="meta-label">{{ __('notifications.invoice.invoice_number') }}</div>
                    <div class="meta-value">{{ $invoice->invoice_number }}</div>
                </div>
            </div>
        </div>

        <div class="customer-info">
            <div class="customer-info-label">{{ __('notifications.invoice.bill_to') }}</div>
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
                        <th width="10%">{{ __('notifications.invoice.date') }}</th>
                        @if($multiple_users)
                        <th width="12%">{{ __('notifications.invoice.worker') }}</th>
                        @endif
                        <th width="@if($multiple_users)48%@else60%@endif">{{ __('notifications.invoice.description') }}</th>
                        <th width="10%" style="text-align: right">{{ __('notifications.invoice.rate_unit') }}</th>
                        <th width="10%" style="text-align: right">{{ __('notifications.invoice.amount') }}</th>
                        <th width="10%" style="text-align: right">{{ __('notifications.invoice.total') }}</th>
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
                            @if($workLog->start_time || $workLog->end_time)
                                <br>
                                <span style="font-size: 9px; color: #666;">
                                    @if($workLog->start_time)
                                        @php
                                            $startTime = is_string($workLog->start_time) ? \Carbon\Carbon::parse($workLog->start_time) : $workLog->start_time;
                                        @endphp
                                        {{ $startTime->format('H:i') }}
                                    @endif
                                    @if($workLog->start_time && $workLog->end_time)
                                        -
                                    @endif
                                    @if($workLog->end_time)
                                        @php
                                            $endTime = is_string($workLog->end_time) ? \Carbon\Carbon::parse($workLog->end_time) : $workLog->end_time;
                                        @endphp
                                        {{ $endTime->format('H:i') }}
                                    @endif
                                </span>
                            @endif
                        </td>
                        @if($multiple_users)
                        <td>{{ $workLog->user->name ?? 'N/A' }}</td>
                        @endif
                        <td>{!! nl2br(e($workLog->description ?? '-')) !!}</td>
                        <td style="text-align: right">{{ $currency_symbol }}{{ number_format($workLog->hourly_rate ?? 0, 2) }}</td>
                        <td style="text-align: right">{{ number_format($workLog->hours_worked ?? 0, 2) }}</td>
                        <td style="text-align: right">{{ $currency_symbol }}{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="project-total">
                {{ __('notifications.invoice.project_total') }}: {{ $currency_symbol }}{{ number_format($projectTotal, 2) }}
            </div>
        </div>
        @empty
        @if($invoice->items->isEmpty())
        <p>{{ __('notifications.invoice.no_work_logs_found') }}</p>
        @endif
        @endforelse

        @if($invoice->items->isNotEmpty())
        <div class="project-section">
            <div class="project-name">{{ __('notifications.invoice.additional_items') }}</div>

            <table class="worklogs-table">
                <thead>
                    <tr>
                        <th width="70%">{{ __('notifications.invoice.description') }}</th>
                        <th width="10%" style="text-align: right">{{ __('notifications.invoice.rate_unit') }}</th>
                        <th width="10%" style="text-align: right">{{ __('notifications.invoice.amount') }}</th>
                        <th width="10%" style="text-align: right">{{ __('notifications.invoice.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $itemsTotal = 0; @endphp
                    @foreach($invoice->items as $item)
                    @php
                        $lineTotal = ($item->quantity ?? 0) * ($item->unit_price ?? 0);
                        $itemsTotal += $lineTotal;
                        $totalAmount += $lineTotal;
                    @endphp
                    <tr>
                        <td>{!! nl2br(e($item->description)) !!}</td>
                        <td style="text-align: right">{{ $currency_symbol }}{{ number_format($item->unit_price ?? 0, 2) }}</td>
                        <td style="text-align: right">{{ number_format($item->quantity ?? 0, 2) }}</td>
                        <td style="text-align: right">{{ $currency_symbol }}{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="project-total">
                {{ __('notifications.invoice.items_total') }}: {{ $currency_symbol }}{{ number_format($itemsTotal, 2) }}
            </div>
        </div>
        @endif

        <div style="margin-top: 30px;">
            <div style="text-align: right; margin-bottom: 10px;">
                <strong>{{ __('notifications.invoice.subtotal') }}: {{ $currency_symbol }}{{ number_format($totalAmount, 2) }}</strong>
            </div>
            <div style="text-align: right; margin-bottom: 10px; font-size: 11px;">
                {{ __('notifications.invoice.tax') }} ({{ number_format($tax_rate, 2) }}%): {{ $currency_symbol }}{{ number_format($totalAmount * ($tax_rate / 100), 2) }}
            </div>
        </div>

        <div class="invoice-total">
            {{ __('notifications.invoice.total') }}: {{ $currency_symbol }}{{ number_format($totalAmount + ($totalAmount * ($tax_rate / 100)), 2) }}
        </div>

        @if($company['invoice_payment_terms'] ?? false)
        <div class="payment-terms">
            <div class="payment-terms-label">{{ __('notifications.invoice.payment_terms') }}</div>
            {!! nl2br(e($company['invoice_payment_terms'])) !!}
        </div>
        @endif
    </div>

</body>

</html>