{{--
    Счёт на оплату (формат для Казахстана).
    Используется в InvoiceGenerator → DomPDF. Шаблон должен быть совместим
    с DomPDF: только базовый CSS, табличный лэйаут, шрифт DejaVu Sans для
    кириллицы.

    Layout — A4 портрет, поля 15мм.
--}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Счёт {{ $order->order_number }}</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #1f2937; }
        h1 { font-size: 18pt; margin: 0 0 6pt 0; }
        .muted { color: #6b7280; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6pt 8pt; vertical-align: top; }
        .header { margin-bottom: 18pt; }
        .header td { border: none; }
        .header td.brand { font-size: 13pt; font-weight: bold; }
        .meta { border-top: 2px solid #1f2937; padding-top: 10pt; margin-bottom: 18pt; }
        .meta th { text-align: left; width: 35%; color: #6b7280; font-weight: normal; }
        .requisites { background: #f3f4f6; padding: 10pt; margin-bottom: 18pt; }
        .requisites th { width: 35%; text-align: left; font-weight: normal; color: #6b7280; }
        .items { border: 1px solid #d1d5db; margin-bottom: 18pt; }
        .items th { background: #1f2937; color: white; text-align: left; font-weight: normal; font-size: 10pt; }
        .items td { border-top: 1px solid #e5e7eb; }
        .items td.num { text-align: right; }
        .totals { margin-bottom: 18pt; }
        .totals td { font-size: 12pt; }
        .totals td.label { text-align: right; color: #6b7280; }
        .totals td.value { text-align: right; font-weight: bold; }
        .footer { color: #6b7280; font-size: 9pt; border-top: 1px solid #e5e7eb; padding-top: 8pt; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td class="brand">{{ $settings->company_legal_name ?: $settings->site_name }}</td>
        <td style="text-align:right">
            <h1>Счёт № {{ $order->order_number }}</h1>
            <div class="muted">от {{ $order->created_at->format('d.m.Y') }}</div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <th>Поставщик</th>
        <td>
            {{ $settings->company_legal_name ?: $settings->site_name }}<br>
            @if ($settings->company_bin)
                БИН: {{ $settings->company_bin }}<br>
            @endif
            @if ($settings->company_legal_address)
                {{ $settings->company_legal_address }}<br>
            @endif
        </td>
    </tr>
    <tr>
        <th>Покупатель</th>
        <td>
            @if ($order->customer_company_name)
                {{ $order->customer_company_name }}<br>
            @endif
            {{ $order->customer_name }}<br>
            @if ($order->customer_bin)
                БИН: {{ $order->customer_bin }}<br>
            @endif
            @if ($order->customer_address)
                {{ $order->customer_address }}<br>
            @endif
            {{ $order->customer_phone }} · {{ $order->customer_email }}
        </td>
    </tr>
</table>

<table class="requisites">
    <tr>
        <th colspan="2"><strong>Платёжные реквизиты:</strong></th>
    </tr>
    @if ($settings->company_bank)
        <tr><th>Банк</th><td>{{ $settings->company_bank }}</td></tr>
    @endif
    @if ($settings->company_iik)
        <tr><th>ИИК (расчётный счёт)</th><td>{{ $settings->company_iik }}</td></tr>
    @endif
    @if ($settings->company_bik)
        <tr><th>БИК</th><td>{{ $settings->company_bik }}</td></tr>
    @endif
    @if ($settings->company_kbe)
        <tr><th>КБЕ</th><td>{{ $settings->company_kbe }}</td></tr>
    @endif
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:5%">№</th>
            <th>Наименование</th>
            <th style="width:10%">Артикул</th>
            <th style="width:10%; text-align:right">Кол-во</th>
            <th style="width:8%">Ед.</th>
            <th style="width:13%; text-align:right">Цена</th>
            <th style="width:14%; text-align:right">Сумма</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $i => $item)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->product_sku }}</td>
                <td class="num">{{ $item->qty }}</td>
                <td>{{ $item->unit }}</td>
                <td class="num">{{ number_format((float) $item->unit_price, 2, '.', ' ') }} ₸</td>
                <td class="num">{{ number_format((float) $item->line_total, 2, '.', ' ') }} ₸</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="label" style="width:70%">Итого к оплате:</td>
        <td class="value">{{ number_format((float) $order->total, 2, '.', ' ') }} ₸</td>
    </tr>
</table>

@if ($order->comment)
    <div class="muted" style="margin-bottom: 14pt;">
        <strong>Комментарий покупателя:</strong><br>
        {{ $order->comment }}
    </div>
@endif

<div class="footer">
    Счёт сформирован автоматически на сайте {{ url('/') }}.
    Оплата по этому счёту означает согласие с условиями поставки.
    По вопросам: {{ $settings->phone }} · {{ $settings->public_email }}.
</div>

</body>
</html>
