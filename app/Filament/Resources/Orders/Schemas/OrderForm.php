<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Заказ')
                ->columns(3)
                ->schema([
                    TextInput::make('order_number')
                        ->label('Номер заказа')
                        ->disabled()
                        ->dehydrated(false),

                    Select::make('status')
                        ->label('Статус')
                        ->options(OrderStatus::class)
                        ->required(),

                    Select::make('payment_method')
                        ->label('Способ оплаты')
                        ->options(PaymentMethod::class)
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('subtotal')
                        ->label('Подытог, ₸')
                        ->numeric()
                        ->prefix('₸')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('total')
                        ->label('Итого, ₸')
                        ->numeric()
                        ->prefix('₸')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('invoice_pdf_path')
                        ->label('Файл счёта')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('—'),
                ]),

            Section::make('Клиент')
                ->columns(2)
                ->schema([
                    Select::make('customer_type')
                        ->label('Тип клиента')
                        ->options(CustomerType::class)
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('customer_name')
                        ->label('Имя / контактное лицо')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('customer_email')
                        ->label('Email')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('customer_phone')
                        ->label('Телефон')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('customer_company_name')
                        ->label('Организация')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('—'),

                    TextInput::make('customer_bin')
                        ->label('БИН')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('—'),

                    TextInput::make('customer_address')
                        ->label('Адрес клиента')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Доставка')
                ->columns(2)
                ->schema([
                    Select::make('delivery_method')
                        ->label('Способ доставки')
                        ->options(DeliveryMethod::class)
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('delivery_address')
                        ->label('Адрес доставки')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('—'),
                ]),

            Section::make('Комментарий клиента')
                ->collapsible()
                ->schema([
                    Textarea::make('comment')
                        ->label('Комментарий')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('—')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('История статусов')
                ->collapsed()
                ->schema([
                    Textarea::make('status_history')
                        ->label('Лог изменений (JSON)')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(8)
                        ->formatStateUsing(fn ($state) => is_array($state)
                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            : (string) $state)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
