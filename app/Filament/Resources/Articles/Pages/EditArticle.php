<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Actions\InternalLinkPickerAction;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->markUpdatedAction(),
            InternalLinkPickerAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Manual «mark article as freshly updated» — sets updated_content_at to
     * now() and bypasses the form. We expose this as an explicit action
     * rather than auto-touch on save because Google's Helpful Content
     * Update penalises fake freshness signals; the admin should only
     * press this after a substantive edit.
     */
    private function markUpdatedAction(): Action
    {
        /** @var Article $record */
        $record = $this->getRecord();

        return Action::make('markContentUpdated')
            ->label('Пометить обновлённой')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Поставит updated_content_at = сейчас. Используется как dateModified в schema.org. Делать только после существенной правки контента — Google карает за fake-touch.')
            ->action(function () use ($record): void {
                $record->forceFill(['updated_content_at' => now()])->saveQuietly();

                /** @var Carbon|null $when */
                $when = $record->updated_content_at;

                Notification::make()
                    ->title('Отмечено как обновлённое')
                    ->body($when?->translatedFormat('j F Y, H:i'))
                    ->success()
                    ->send();
            });
    }
}
