<?php

declare(strict_types=1);

namespace App\Filament\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Reusable Filament "SEO" section for any content model that uses the
 * HasSeo trait (Category, Product, Article, Page).
 *
 * Renders as a collapsed Section so it doesn't dominate the form — the
 * editor opens it explicitly when they're working on SEO.
 *
 * Usage in a Form schema:
 *
 *     return $schema->components([
 *         // ... business fields ...
 *         SeoSection::make(),
 *     ]);
 */
final class SeoSection
{
    public static function make(): Section
    {
        return Section::make('SEO и социальные сети')
            ->description('Мета-теги для Google / Яндекс / соцсетей. Можно оставить пустым — будет fallback на основное название.')
            ->collapsed()
            ->columns(2)
            ->schema([
                TextInput::make('meta_title')
                    ->label('Title (для <title> и SERP)')
                    ->maxLength(60)
                    ->helperText('Оптимально 50-60 символов. Google обрезает длиннее.')
                    ->columnSpanFull(),

                Textarea::make('meta_description')
                    ->label('Description (для SERP-сниппета и OG)')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Оптимально 150-160 символов. Google показывает первые ~160.')
                    ->columnSpanFull(),

                FileUpload::make('og_image_override')
                    ->label('OG-изображение (необязательно)')
                    ->image()
                    ->imageEditor()
                    ->directory('og-overrides')
                    ->helperText('1200×630 px. Пусто = используется главное фото записи.')
                    ->columnSpanFull(),

                TextInput::make('canonical_url')
                    ->label('Canonical URL (необязательно)')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Пусто = автоматически.'),

                Toggle::make('noindex')
                    ->label('Скрыть от поисковиков (noindex)')
                    ->inline(false)
                    ->helperText('Запретит Google/Яндекс индексировать эту запись.'),
            ]);
    }
}
