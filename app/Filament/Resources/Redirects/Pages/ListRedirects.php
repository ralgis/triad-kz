<?php

declare(strict_types=1);

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Resources\Redirects\RedirectResource;
use App\Filament\Resources\Redirects\Tables\RedirectsTable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->importCsvAction(),
        ];
    }

    /**
     * Paste-CSV bulk-import action. Accepts CSV with optional header,
     * columns: from,to[,status]. Status defaults to 301 if omitted.
     * Lines starting with '#' are ignored (commenting allowed).
     */
    private function importCsvAction(): Action
    {
        return Action::make('importCsv')
            ->label('Импорт из CSV')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->modalHeading('Импорт 301-редиректов из CSV')
            ->modalSubmitActionLabel('Импортировать')
            ->schema([
                Textarea::make('csv')
                    ->label('CSV')
                    ->required()
                    ->rows(14)
                    ->placeholder("from,to,status\n/old-page/,/new-page/,301\n/another-old/,/another-new/")
                    ->helperText('Заголовок необязателен. Разделители: запятая или точка с запятой. Строки начинающиеся с # игнорируются.'),
            ])
            ->action(function (array $data): void {
                $rows = $this->parseCsv((string) $data['csv']);

                if ($rows === []) {
                    Notification::make()
                        ->title('Пусто')
                        ->body('В CSV не найдено валидных строк.')
                        ->warning()
                        ->send();

                    return;
                }

                $result = RedirectsTable::upsertBatch($rows);

                Notification::make()
                    ->title('Импорт завершён')
                    ->body(sprintf(
                        'Создано: %d, обновлено: %d.',
                        $result['created'],
                        $result['updated'],
                    ))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return list<array{from: string, to: string, status: int}>
     */
    private function parseCsv(string $csv): array
    {
        $rows = [];
        $lines = preg_split("/\r\n|\r|\n/", $csv) ?: [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Allow either comma or semicolon as separator.
            $cols = preg_split('/[,;]/', $line, 3) ?: [];
            if (count($cols) < 2) {
                continue;
            }

            $from = trim($cols[0]);
            $to = trim($cols[1]);

            // Skip header row if present.
            if (strcasecmp($from, 'from') === 0 && strcasecmp($to, 'to') === 0) {
                continue;
            }

            $status = isset($cols[2]) ? (int) trim($cols[2]) : 301;
            if (! in_array($status, [301, 302], true)) {
                $status = 301;
            }

            if ($from === '' || $to === '') {
                continue;
            }

            $rows[] = ['from' => $from, 'to' => $to, 'status' => $status];
        }

        return $rows;
    }
}
