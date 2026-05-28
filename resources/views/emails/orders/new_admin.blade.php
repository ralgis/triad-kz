<!DOCTYPE html>
<html lang="ru">
<head><meta charset="utf-8"><title>Новый заказ {{ $order->order_number }}</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">

<h2>Новый заказ № {{ $order->order_number }}</h2>

<p><strong>Сумма:</strong> {{ number_format((float) $order->total, 2, '.', ' ') }} ₸<br>
<strong>Способ оплаты:</strong> {{ $order->payment_method->label() }}<br>
<strong>Способ доставки:</strong> {{ $order->delivery_method->label() }}</p>

<h3>Клиент</h3>
<p>
    {{ $order->customer_name }} ({{ $order->customer_type->label() }})<br>
    @if ($order->customer_company_name){{ $order->customer_company_name }} · БИН {{ $order->customer_bin }}<br>@endif
    {{ $order->customer_phone }} · {{ $order->customer_email }}<br>
    @if ($order->customer_address){{ $order->customer_address }}<br>@endif
    @if ($order->delivery_address)<em>Доставка:</em> {{ $order->delivery_address }}<br>@endif
</p>

@if ($order->comment)
    <h3>Комментарий клиента</h3>
    <p>{{ $order->comment }}</p>
@endif

<h3>Состав</h3>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse;">
    <thead>
        <tr style="background: #f3f4f6;">
            <th align="left">Товар</th>
            <th align="right">Кол-во</th>
            <th align="right">Сумма</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product_name }} <span style="color:#6b7280">({{ $item->product_sku }})</span></td>
                <td align="right">{{ $item->qty }} {{ $item->unit }}</td>
                <td align="right">{{ number_format((float) $item->line_total, 2, '.', ' ') }} ₸</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top: 18px;">
    <a href="{{ $adminUrl }}" style="background:#1f2937;color:white;padding:8px 14px;text-decoration:none;border-radius:4px;">
        Открыть в админке →
    </a>
</p>

</body>
</html>
