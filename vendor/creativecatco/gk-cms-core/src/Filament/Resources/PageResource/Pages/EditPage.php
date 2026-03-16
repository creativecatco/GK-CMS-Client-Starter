<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PageResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\PageResource;
use CreativeCatCo\GkCmsCore\Models\Page;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_template_code')
                ->label('View Template Code')
                ->icon('heroicon-o-code-bracket')
                ->color('info')
                ->modalHeading('Template Source Code')
                ->modalDescription(fn () => $this->getTemplateSourceDescription())
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('Save Changes')
                ->modalCancelActionLabel('Close')
                ->form([
                    Forms\Components\Textarea::make('template_code')
                        ->label('')
                        ->rows(30)
                        ->default(fn () => $this->getTemplateSourceCode())
                        ->columnSpanFull()
                        ->extraAttributes([
                            'style' => 'font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace; font-size: 13px; line-height: 1.5; tab-size: 4; white-space: pre; overflow-x: auto; background: #1e1e2e; color: #cdd6f4; padding: 16px; border-radius: 8px;',
                        ]),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;
                    $newCode = $data['template_code'];

                    if ($record->template === 'custom') {
                        // Save to database custom_template field
                        $record->custom_template = $newCode;
                        $record->saveQuietly();

                        // Re-discover fields from updated template
                        $discovered = Page::discoverFieldsFromTemplate($newCode);
                        if (!empty($discovered)) {
                            $existing = $record->field_definitions ?? [];
                            $existingKeys = collect($existing)->pluck('key')->toArray();
                            foreach ($discovered as $field) {
                                if (!in_array($field['key'], $existingKeys)) {
                                    $existing[] = $field;
                                }
                            }
                            $record->field_definitions = $existing;
                            $record->saveQuietly();
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Template code saved')
                            ->body('Custom template has been updated in the database.')
                            ->success()
                            ->send();
                    } else {
                        // Save to the blade file on disk
                        $filePath = $this->getTemplateFilePath();
                        if ($filePath && is_writable($filePath)) {
                            file_put_contents($filePath, $newCode);

                            // Clear compiled views so changes take effect
                            \Illuminate\Support\Facades\Artisan::call('view:clear');

                            \Filament\Notifications\Notification::make()
                                ->title('Template file saved')
                                ->body('Changes written to: ' . basename($filePath))
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot save to file')
                                ->body('The template file is not writable. Copy the code and update the file manually.')
                                ->warning()
                                ->send();
                        }
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Get the template source code for display in the modal.
     */
    protected function getTemplateSourceCode(): string
    {
        $record = $this->record;

        // Custom templates are stored in the database
        if ($record->template === 'custom') {
            return $record->custom_template ?? '{{-- No custom template code found. Paste your Blade/HTML here. --}}';
        }

        // File-based templates: read from disk
        $filePath = $this->getTemplateFilePath();
        if ($filePath && file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        return '{{-- Template file not found for: ' . ($record->template ?? 'default') . ' --}}';
    }

    /**
     * Get the description showing the template source location.
     */
    protected function getTemplateSourceDescription(): string
    {
        $record = $this->record;

        if ($record->template === 'custom') {
            return 'Source: Database (custom_template field) — You can edit the code directly and save.';
        }

        $filePath = $this->getTemplateFilePath();
        if ($filePath) {
            $relativePath = str_replace(base_path() . '/', '', $filePath);
            return 'Source: ' . $relativePath . ' — You can edit the code directly and save.';
        }

        return 'Template: ' . ($record->template ?? 'default');
    }

    /**
     * Resolve the file path for a file-based template.
     */
    protected function getTemplateFilePath(): ?string
    {
        $record = $this->record;
        $template = $record->template ?? 'default';

        if ($template === 'custom') {
            return null;
        }

        // Check theme path first
        $themePath = resource_path('views/theme/pages/' . $template . '.blade.php');
        if (file_exists($themePath)) {
            return $themePath;
        }

        // Check package path
        $packagePath = dirname(__DIR__, 5) . '/resources/views/pages/' . $template . '.blade.php';
        if (file_exists($packagePath)) {
            return $packagePath;
        }

        // Try slug-based resolution
        $slug = $record->slug;
        if ($slug && $slug !== $template) {
            $slugThemePath = resource_path('views/theme/pages/' . $slug . '.blade.php');
            if (file_exists($slugThemePath)) {
                return $slugThemePath;
            }
        }

        // Fallback to default
        $defaultPackagePath = dirname(__DIR__, 5) . '/resources/views/pages/default.blade.php';
        if (file_exists($defaultPackagePath)) {
            return $defaultPackagePath;
        }

        return null;
    }

    /**
     * After saving, auto-discover field definitions from the custom template
     * if the template is 'custom' and field_definitions haven't been manually set.
     */
    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->template === 'custom' && !empty($record->custom_template)) {
            $discovered = Page::discoverFieldsFromTemplate($record->custom_template);

            if (!empty($discovered)) {
                // Merge with existing definitions (keep manually added ones)
                $existing = $record->field_definitions ?? [];
                $existingKeys = collect($existing)->pluck('key')->toArray();

                foreach ($discovered as $field) {
                    if (!in_array($field['key'], $existingKeys)) {
                        $existing[] = $field;
                    }
                }

                $record->field_definitions = $existing;
                $record->saveQuietly();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
