<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

/**
 * Header action for content Edit pages: searches across Category/Product/
 * Article/Page and returns the public URL of the picked entity. The user
 * copies the URL into TipTap's built-in link button.
 *
 * Full in-editor TipTap extension is deferred to Phase 2 (needs JS bundle
 * + extension registration); this gets us 90% of the workflow with zero
 * frontend work.
 */
class InternalLinkPickerAction
{
    private const LINKABLE_TYPES = [
        Category::class => 'Категория каталога',
        Product::class => 'Товар',
        Article::class => 'Статья',
        Page::class => 'Страница',
    ];

    public static function make(): Action
    {
        return Action::make('internalLinkPicker')
            ->label('Найти внутренний URL')
            ->icon('heroicon-o-link')
            ->color('gray')
            ->modalHeading('Внутренняя ссылка')
            ->modalDescription('Найди сущность сайта — получишь её публичный URL для вставки в редактор.')
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel('Готово')
            ->schema([
                Select::make('type')
                    ->label('Тип')
                    ->options(self::LINKABLE_TYPES)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('id', null))
                    ->afterStateUpdated(fn ($set) => $set('resolved_url', null)),

                Select::make('id')
                    ->label('Сущность')
                    ->options(fn ($get) => self::optionsForType((string) $get('type')))
                    ->searchable()
                    ->required()
                    ->disabled(fn ($get) => blank($get('type')))
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set): void {
                        $type = (string) $get('type');
                        if (blank($state) || blank($type)) {
                            $set('resolved_url', null);

                            return;
                        }
                        $set('resolved_url', self::resolveUrl($type, (int) $state));
                    }),

                TextInput::make('resolved_url')
                    ->label('URL для копирования')
                    ->readOnly()
                    ->placeholder('— появится после выбора сущности —')
                    ->extraAttributes(['onclick' => 'this.select()'])
                    ->helperText('Кликни в поле → выделится → Ctrl/Cmd+C, затем вставь через кнопку 🔗 в редакторе.'),
            ])
            ->action(function (array $data): void {
                Notification::make()
                    ->title('URL готов')
                    ->body($data['resolved_url'] ?? '—')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int|string, string>
     */
    private static function optionsForType(string $type): array
    {
        if (! array_key_exists($type, self::LINKABLE_TYPES)) {
            return [];
        }

        /** @var class-string<Model> $type */
        $titleColumn = match ($type) {
            Article::class, Page::class => 'title',
            default => 'name',
        };

        return $type::query()
            ->orderBy($titleColumn)
            ->limit(200)
            ->pluck($titleColumn, 'id')
            ->all();
    }

    private static function resolveUrl(string $type, int $id): ?string
    {
        if (! array_key_exists($type, self::LINKABLE_TYPES)) {
            return null;
        }

        /** @var class-string<Model> $type */
        $model = $type::find($id);

        if ($model === null) {
            return null;
        }

        if (method_exists($model, 'url')) {
            return $model->url();
        }

        return null;
    }
}
