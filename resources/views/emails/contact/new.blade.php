<!DOCTYPE html>
<html lang="ru">
<head><meta charset="utf-8"><title>Заявка с сайта</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">

<h2>Новая заявка с сайта</h2>

<p>
    <strong>Имя:</strong> {{ $submission->name }}<br>
    <strong>Телефон:</strong> {{ $submission->phone }}<br>
    @if ($submission->email)
        <strong>Email:</strong> {{ $submission->email }}<br>
    @endif
    <strong>Получена:</strong> {{ $submission->created_at->format('d.m.Y H:i') }}
</p>

@if ($submission->product)
    <p>
        <strong>По товару:</strong>
        <a href="{{ $submission->product->url() }}">{{ $submission->product->name }}</a>
        (артикул {{ $submission->product->sku }})
    </p>
@else
    <p><em>Заявка с общей формы /contacts/.</em></p>
@endif

@if ($submission->message)
    <h3>Сообщение</h3>
    <p style="white-space: pre-wrap;">{{ $submission->message }}</p>
@endif

@if ($submission->ip)
    <p style="color:#6b7280; font-size:90%;">IP: {{ $submission->ip }}</p>
@endif

</body>
</html>
