<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_no }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; }

        .header { width: 100%; border-bottom: 2px solid #1e293b; padding-bottom: 10px; margin-bottom: 14px; }
        .header td { vertical-align: top; }
        .company-name { font-size: 16px; font-weight: bold; color: #1e293b; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: right; color: #1e293b; }
        .doc-sub { text-align: right; font-size: 11px; color: #475569; }

        .info-table { width: 100%; margin-bottom: 14px; }
        .info-table td { vertical-align: top; padding: 2px 0; font-size: 11px; }
        .info-label { color: #64748b; width: 110px; }
        .info-colon { width: 10px; color: #64748b; }
        .info-value { font-weight: bold; color: #1e293b; }

        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin: 10px 0 4px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.items th { background: #1e293b; color: #fff; font-size: 9.5px; text-transform: uppercase; padding: 6px 5px; text-align: left; }
        table.items td { padding: 6px 5px; font-size: 10.5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.items td.num { text-align: right; }
        table.items td.center { text-align: center; }

        table.totals { width: 260px; margin-left: auto; margin-top: 8px; border-collapse: collapse; }
        table.totals td { padding: 4px 6px; font-size: 11px; }
        table.totals .t-label { color: #64748b; text-align: left; }
        table.totals .t-value { text-align: right; font-weight: bold; }
        table.totals .net-row td { border-top: 2px solid #1e293b; font-size: 12.5px; padding-top: 6px; }

        .terms-table { width: 100%; margin-top: 14px; }
        .terms-table td { vertical-align: top; padding: 3px 0; font-size: 10.5px; }
        .terms-label { color: #64748b; width: 130px; }

        /* Term & Conditions -- isinya HTML dari rich text editor (bold,
           list, paragraf, dll), jadi ditampilkan penuh 1 kolom (bukan
           nempel di tabel sempit terms-table) supaya list-nya kebaca rapi. */
        .term-content { font-size: 10.5px; color: #1e293b; }
        .term-content p { margin: 0 0 5px; }
        .term-content p:last-child { margin-bottom: 0; }
        .term-content ul, .term-content ol { margin: 4px 0 6px 16px; padding: 0; }
        .term-content li { margin-bottom: 2px; }
        .term-content strong, .term-content b { font-weight: bold; }
        .term-content em, .term-content i { font-style: italic; }
        .term-content u { text-decoration: underline; }

        .signature-block { margin-top: 40px; width: 220px; }
        .signature-line { border-top: 1px solid #1e293b; margin-top: 45px; padding-top: 4px; text-align: center; font-weight: bold; }

        .footer-note { margin-top: 24px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">PT. DUTA INDOMANDIRI</div>
            </td>
            <td style="width: 40%;">
                <div class="doc-title">QUOTATION</div>
                <div class="doc-sub">No. {{ $quotation->quotation_no }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <table style="width:100%;">
                    <tr><td class="info-label">Kepada</td><td class="info-colon">:</td><td class="info-value">{{ $quotation->customer_company_name }}</td></tr>
                    <tr><td class="info-label">Alamat</td><td class="info-colon">:</td><td>{{ $quotation->customer_address }}</td></tr>
                    <tr><td class="info-label">Attn</td><td class="info-colon">:</td><td>{{ $quotation->customer_pic_name }}</td></tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table style="width:100%;">
                    <tr><td class="info-label">Tanggal</td><td class="info-colon">:</td><td class="info-value">{{ \Carbon\Carbon::parse($quotation->quotation_date)->translatedFormat('d M Y') }}</td></tr>
                    <tr><td class="info-label">Customer Ref</td><td class="info-colon">:</td><td>{{ $quotation->customer_ref }}</td></tr>
                    <tr><td class="info-label">Payment Terms</td><td class="info-colon">:</td><td>{{ $quotation->payment_terms }}</td></tr>
                    @if($quotation->pages)
                    <tr><td class="info-label">Halaman</td><td class="info-colon">:</td><td>{{ $quotation->pages }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Rincian Penawaran</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th style="width: 44%;">Description</th>
                <th style="width: 10%;" class="center">Qty</th>
                <th style="width: 10%;" class="center">Satuan</th>
                <th style="width: 15%;" class="num">Unit Price</th>
                <th style="width: 15%;" class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{!! nl2br(e($item->description)) !!}</td>
                <td class="center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td class="center">{{ $item->unit }}</td>
                <td class="num">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="t-label">Sub Total</td>
            <td class="t-value">{{ number_format($quotation->sub_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="t-label">PPN</td>
            <td class="t-value">{{ number_format($quotation->ppn, 0, ',', '.') }}</td>
        </tr>
        <tr class="net-row">
            <td class="t-label">Net Amount</td>
            <td class="t-value">{{ number_format($quotation->net_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="terms-table">
        <tr><td class="terms-label">Validity</td><td>{{ $quotation->validity }}</td></tr>
        <tr><td class="terms-label">Delivery Time</td><td>{{ $quotation->delivery_time }}</td></tr>
    </table>

    @if($quotation->term)
    <div class="section-title" style="margin-top:12px">Term & Conditions</div>
    <div class="term-content">{!! $quotation->term !!}</div>
    @endif

    <div class="signature-block">
        <div class="signature-line">{{ $quotation->signature ?: '-' }}</div>
    </div>

    <div class="footer-note">
        Quotation ini dibuat oleh {{ $quotation->sales->fullname ?? '-' }} melalui sistem CRM PT. Duta Indomandiri.
    </div>

</body>
</html>