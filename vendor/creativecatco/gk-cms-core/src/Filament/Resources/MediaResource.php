<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use CreativeCatCo\GkCmsCore\Models\Media;
use CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource\Pages;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'filename';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ─── Image Preview (edit only) ───
                Forms\Components\Section::make('Preview')
                    ->schema([
                        Forms\Components\Placeholder::make('image_preview')
                            ->label('')
                            ->content(function (?Media $record): \Illuminate\Support\HtmlString {
                                if (!$record || !$record->is_image) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">No preview available</p>');
                                }
                                $url = asset('storage/' . $record->path);
                                return new \Illuminate\Support\HtmlString(
                                    '<img src="' . e($url) . '" alt="' . e($record->alt_text ?? $record->filename) . '" class="max-h-64 rounded-lg shadow-sm" />'
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?Media $record) => $record !== null && $record->is_image)
                    ->collapsible(),

                // ─── Upload (create only) ───
                Forms\Components\Section::make('Upload File')
                    ->schema([
                        Forms\Components\FileUpload::make('upload_file')
                            ->label('File')
                            ->required()
                            ->disk('public')
                            ->directory(config('cms.media_upload_path', 'media/uploads'))
                            ->acceptedFileTypes([
                                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                                'application/pdf', 'text/plain',
                                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(config('cms.max_upload_size', 10240))
                            ->imagePreviewHeight('250')
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    // $state is a Livewire TemporaryUploadedFile
                                    if (method_exists($state, 'getClientOriginalName')) {
                                        $set('filename', $state->getClientOriginalName());
                                    }
                                    if (method_exists($state, 'getMimeType')) {
                                        $set('mime_type', $state->getMimeType());
                                    }
                                    if (method_exists($state, 'getSize')) {
                                        $set('size', $state->getSize());
                                    }
                                }
                            })
                            ->reactive(),
                    ])
                    ->visible(fn (?Media $record) => $record === null), // Only show on create

                // ─── Media Details ───
                Forms\Components\Section::make('Media Details')
                    ->schema([
                        Forms\Components\TextInput::make('filename')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('alt_text')
                            ->label('Alt Text')
                            ->maxLength(255)
                            ->helperText('Describe the image for accessibility and SEO.'),

                        Forms\Components\TextInput::make('folder')
                            ->maxLength(255)
                            ->helperText('Organize media into folders (e.g., "blog", "pages").'),

                        Forms\Components\Hidden::make('mime_type'),
                        Forms\Components\Hidden::make('size'),
                        Forms\Components\Hidden::make('path'),
                    ])
                    ->columns(2),

                // ─── File URL (edit only) ───
                Forms\Components\Section::make('File URL')
                    ->schema([
                        Forms\Components\View::make('cms-core::filament.components.media-url-copy'),
                    ])
                    ->visible(fn (?Media $record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('public')
                    ->height(80)
                    ->width(80),

                Tables\Columns\TextColumn::make('filename')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('alt_text')
                    ->label('Alt Text')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('human_size')
                    ->label('Size')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('folder')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('folder')
                    ->options(fn () => Media::whereNotNull('folder')
                        ->distinct()
                        ->pluck('folder', 'folder')
                        ->toArray()
                    ),

                Tables\Filters\Filter::make('images_only')
                    ->label('Images Only')
                    ->query(fn ($query) => $query->where('mime_type', 'like', 'image/%')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Preview'),

                Tables\Actions\Action::make('copy_url')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function (Media $record) {
                        // URL is copied via Alpine.js on the frontend
                    })
                    ->extraAttributes(fn (Media $record) => [
                        'x-on:click.stop' => "
                            navigator.clipboard.writeText('" . asset('storage/' . $record->path) . "');
                            \$dispatch('notify', {message: 'URL copied to clipboard!'});
                        ",
                    ]),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (Media $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'view' => Pages\ViewMedia::route('/{record}'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
