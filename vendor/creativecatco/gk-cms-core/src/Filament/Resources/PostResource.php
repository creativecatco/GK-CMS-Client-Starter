<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use CreativeCatCo\GkCmsCore\Models\Post;
use CreativeCatCo\GkCmsCore\Models\Category;
use CreativeCatCo\GkCmsCore\Models\Tag;
use CreativeCatCo\GkCmsCore\Filament\Resources\PostResource\Pages;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Post Content')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', \Illuminate\Support\Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Forms\Components\RichEditor::make('content')
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory(config('cms.media_upload_path', 'cms/media')),

                        Forms\Components\Textarea::make('excerpt')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText('A short summary of the post.'),

                        Forms\Components\FileUpload::make('featured_image')
                            ->image()
                            ->disk('public')
                            ->directory(config('cms.media_upload_path', 'cms/media') . '/blog')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Categorization')
                    ->schema([
                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2),
                            ]),

                        Forms\Components\Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Publishing')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->default(now()),

                        Forms\Components\Select::make('author_id')
                            ->label('Author')
                            ->relationship('author', 'name')
                            ->default(auth()->id())
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('Meta Title')
                            ->maxLength(70)
                            ->helperText('Leave blank to use the post title.'),

                        Forms\Components\Textarea::make('seo_description')
                            ->label('Meta Description')
                            ->maxLength(160)
                            ->rows(3)
                            ->helperText('Recommended: 150–160 characters.'),

                        Forms\Components\FileUpload::make('og_image')
                            ->label('OG Image')
                            ->image()
                            ->disk('public')
                            ->directory(config('cms.media_upload_path', 'cms/media') . '/og')
                            ->helperText('Recommended size: 1200×630 pixels.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                    ]),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),

                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('author')
                    ->relationship('author', 'name'),
            ])
            ->actions([
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
