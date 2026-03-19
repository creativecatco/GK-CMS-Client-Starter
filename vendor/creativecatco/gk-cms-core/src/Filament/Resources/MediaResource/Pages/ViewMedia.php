<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource;
use CreativeCatCo\GkCmsCore\Models\Media;

class ViewMedia extends ViewRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Image Preview
                Infolists\Components\Section::make('Preview')
                    ->schema([
                        Infolists\Components\ImageEntry::make('path')
                            ->label('')
                            ->disk('public')
                            ->height(400)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Media $record): bool => $record->is_image),

                // File URL with copy button
                Infolists\Components\Section::make('File URL')
                    ->schema([
                        Infolists\Components\ViewEntry::make('url_copy')
                            ->label('')
                            ->view('cms-core::filament.components.media-url-copy')
                            ->columnSpanFull(),
                    ]),

                // File Details
                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('filename')
                            ->label('Filename')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('alt_text')
                            ->label('Alt Text')
                            ->default('—'),

                        Infolists\Components\TextEntry::make('mime_type')
                            ->label('Type'),

                        Infolists\Components\TextEntry::make('human_size')
                            ->label('Size'),

                        Infolists\Components\TextEntry::make('folder')
                            ->label('Folder')
                            ->default('—'),

                        Infolists\Components\TextEntry::make('path')
                            ->label('Storage Path')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Uploaded')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
