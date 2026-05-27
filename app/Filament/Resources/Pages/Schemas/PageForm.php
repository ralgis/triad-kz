<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Page;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Страница')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Заголовок')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, ?Page $record) {
                            if ($record === null && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(Page::class, 'slug', ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('Будет доступно по адресу /{slug}/. Например: «о-компании» = /о-компании/. При смене URL отредиректится на новый (301).'),

                    TextInput::make('template')
                        ->label('Шаблон (необязательно)')
                        ->maxLength(60)
                        ->placeholder('default')
                        ->helperText('Имя кастомного Blade-шаблона. Пусто = универсальный. Используется для специальных страниц (например, «contacts» с картой).')
                        ->columnSpanFull(),

                    RichEditor::make('content')
                        ->label('Содержимое')
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList',
                            'blockquote', 'codeBlock', 'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            SeoSection::make(),
        ]);
    }
}
