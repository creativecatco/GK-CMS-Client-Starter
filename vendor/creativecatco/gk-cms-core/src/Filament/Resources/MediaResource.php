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
                Forms\Components\Section::make('Media Details')
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('File')
                            ->required()
                            ->directory(config('cms.media_upload_path', 'cms/media'))
                            ->acceptedFileTypes(config('cms.allowed_file_types'))
                            ->maxSize(config('cms.max_upload_size', 10240))
                            ->imagePreviewHeight('250')
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('filename', $state->getClientOriginalName() ?? '');
                                    $set('mime_type', $state->getMimeType() ?? '');
                                    $set('size', $state->getSize() ?? 0);
                                }
                            }),

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
                    ])
                    ->columns(2),

                // ─── IMAGE URL (read-only, shown on edit) ───
                Forms\Components\Section::make('File URL')
                    ->schema([
                        Forms\Components\Placeholder::make('file_url_display')
                            ->label('Public URL')
                            ->content(fn (?Media $record): string => $record ? asset('storage/' . $record->path) : '')
                            ->columnSpanFull(),
                        Forms\Components\View::make('cms-core::filament.components.copy-url-button')
                            ->viewData(['record' => fn (?Media $record) => $record]),
                    ])
                    ->visible(fn (?Media $record) => $record !== null)
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Preview')
                    ->disk(config('cms.media_disk', 'public'))
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
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
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
                Tables\Actions\Action::make('copy_url')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function (Media $record) {
                        // The URL is copied via JS in the frontend
                    })
                    ->extraAttributes(fn (Media $record) => [
                        'x-on:click' => "
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
            ]);
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
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
