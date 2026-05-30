<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\PriceImport as PriceImportRow;
use App\Services\PriceImportService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Bulk price update from an XLSX/XLS file.
 *
 * Two-step UI:
 *
 *   1. Admin uploads a file → handlePreview() parses + builds preview
 *      → table of «будет обновлено / пропущено / не найдено» rows.
 *   2. Admin clicks «Применить» → handleApply() commits the matched
 *      rows + logs to PriceImport.
 *
 * Recent imports (last 10) shown as a history table at the bottom of
 * the page — admin can see «what we did last week» without leaving.
 */
class PriceImport extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Импорт цен';

    protected static string|null|\UnitEnum $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Импорт цен из Excel';

    protected string $view = 'filament.pages.price-import';

    /** @var UploadedFile|TemporaryUploadedFile|null */
    public mixed $upload = null;

    /** @var array{matched: list<array<string,mixed>>, noop: list<array<string,mixed>>, not_found: list<array<string,mixed>>}|null */
    public ?array $preview = null;

    public ?string $previewFileName = null;

    /**
     * Step 1 — parse the uploaded XLSX and build the preview.
     */
    public function handlePreview(): void
    {
        $this->validate([
            'upload' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        /** @var UploadedFile|TemporaryUploadedFile $file */
        $file = $this->upload;

        try {
            $parsed = app(PriceImportService::class)->parse($file->getRealPath());
            $this->preview = app(PriceImportService::class)->buildPreview($parsed);
            $this->previewFileName = (string) $file->getClientOriginalName();

            Notification::make()
                ->title('Файл обработан')
                ->body(sprintf(
                    '%d товаров будет обновлено · %d без изменений · %d SKU не найдено',
                    count($this->preview['matched']),
                    count($this->preview['noop']),
                    count($this->preview['not_found']),
                ))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Ошибка обработки файла')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Step 2 — commit the preview to DB + log.
     */
    public function handleApply(): void
    {
        if ($this->preview === null || $this->previewFileName === null) {
            Notification::make()
                ->title('Нечего применять')
                ->body('Сначала загрузи файл и посмотри превью.')
                ->warning()
                ->send();

            return;
        }

        if ($this->preview['matched'] === []) {
            Notification::make()
                ->title('В файле нет изменений')
                ->body('Все цены уже актуальны или SKU не найдены.')
                ->warning()
                ->send();

            return;
        }

        try {
            $import = app(PriceImportService::class)->apply(
                $this->preview,
                $this->previewFileName,
                Auth::id(),
            );

            Notification::make()
                ->title('Цены обновлены')
                ->body(sprintf(
                    '%d товаров обновлено. Файл сохранён в журнале импортов #%d.',
                    $import->rows_updated,
                    $import->id,
                ))
                ->success()
                ->send();

            $this->reset(['upload', 'preview', 'previewFileName']);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Ошибка применения')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function handleCancel(): void
    {
        $this->reset(['upload', 'preview', 'previewFileName']);
    }

    /**
     * @return Collection<int, PriceImportRow>
     */
    public function getRecentImportsProperty(): Collection
    {
        return PriceImportRow::query()
            ->with('importedBy')
            ->latest('created_at')
            ->limit(10)
            ->get();
    }
}
