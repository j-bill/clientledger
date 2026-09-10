{{-- Chrome print footer template: stylesheets are not applied here, all styles must be inline --}}
@php
    $footerColumns = [
        $company['invoice_footer_col1'] ?? 'company_info',
        $company['invoice_footer_col2'] ?? 'bank_info',
        $company['invoice_footer_col3'] ?? 'page_info',
    ];
    $columnAlignments = ['left', 'center', 'right'];
@endphp
<div style="width: 100%; margin: 0 20mm; font-family: Arial, sans-serif; font-size: 8px; color: #666; border-top: 1px solid #ddd; padding-top: 8px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            @foreach($footerColumns as $index => $column)
            <td style="width: 33.33%; padding: 0; vertical-align: top; line-height: 1.4; text-align: {{ $columnAlignments[$index] }};">
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
                @elseif($column === 'bank_info' || $column === 'bankInfo')
                    @if($company['company_bank_info'] ?? false)
                        {!! nl2br(e($company['company_bank_info'])) !!}
                    @endif
                @elseif($column === 'page_info')
                    {{ __('notifications.invoice.page') }} @pageNumber {{ __('notifications.invoice.of') }} @totalPages
                @elseif($column === 'empty')
                    &nbsp;
                @endif
            </td>
            @endforeach
        </tr>
    </table>
</div>
