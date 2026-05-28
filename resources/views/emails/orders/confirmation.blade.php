@php
    $settings = \App\Models\Setting::current();
@endphp
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="utf-8"><title>Заказ {{ $order->order_number }}</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">

<p>Здравствуйте, {{ $order->customer_name }}!</p>

<p>
    Спасибо за заказ <strong>№ {{ $order->order_number }}</strong> от
    {{ $order->created_at->format('d.m.Y H:i') }}.
</p>

<h3>Состав заказа</h3>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse;">
    <thead>
        <tr style="background: #f3f4f6;">
            <th align="left">Товар</th>
            <th align="right">Кол-во</th>
            <th align="right">Цена</th>
            <th align="right">Сумма</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product_name }} <span style="color:#6b7280">({{ $item->product_sku }})</span></td>
                <td align="right">{{ $item->qty }} {{ $item->unit }}</td>
                <td align="right">{{ number_format((float) $item->unit_price, 2, '.', ' ') }} ₸</td>
                <td align="right">{{ number_format((float) $item->line_total, 2, '.', ' ') }} ₸</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" align="right"><strong>Итого:</strong></td>
            <td align="right"><strong>{{ number_format((float) $order->total, 2, '.', ' ') }} ₸</strong></td>
        </tr>
    </tfoot>
</table>

@if ($order->payment_method->generatesInvoice())
    <h3>Оплата</h3>
    <p>
        Способ оплаты: <strong>Безналичный расчёт</strong>.
        Счёт-фактура прикреплён к этому письму PDF-файлом.
    </p>
@else
    <h3>Оплата</h3>
    <p>Способ оплаты: <strong>Наличный расчёт</strong> (при получении или в офисе).</p>
@endif

<p>С вами свяжется менеджер для подтверждения деталей.</p>

<p style="color:#6b7280; font-size:90%;">
    {{ $settings->site_name }}<br>
    @if ($settings->phone){{ $settings->phone }}<br>@endif
    @if ($settings->public_email){{ $settings->public_email }}<br>@endif
</p>

</body>
</html>
