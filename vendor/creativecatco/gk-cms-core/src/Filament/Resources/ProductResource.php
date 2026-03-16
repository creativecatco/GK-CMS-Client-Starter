<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use CreativeCatCo\GkCmsCore\Models\Product;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Filament\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Only show in navigation if product post type is enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        try {
            return (bool) Setting::get('enable_products', true);
        } catch (\Exception $e) {
            return true;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('slug')
                                    ->maxLength(255)
                                    ->helperText('Auto-generated from title if left empty'),
                                Forms\Components\RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->label('Description'),
                                Forms\Components\Textarea::make('excerpt')
                                    ->rows(3)
                                    ->helperText('Short description for listings'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Media')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('featured_image')
                                    ->image()
                                    ->disk('public')
                                    ->directory(config('cms.media_upload_path', 'cms/media') . '/product')
                                    ->imageEditor(),
                                Forms\Components\FileUpload::make('gallery')
                                    ->multiple()
                                    ->image()
                                    ->disk('public')
                                    ->directory(config('cms.media_upload_path', 'cms/media') . '/product/gallery')
                                    ->reorderable()
                                    ->maxFiles(20),
                            ]),
                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step('0.01'),
                                Forms\Components\TextInput::make('sale_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step('0.01')
                                    ->helperText('Leave empty if not on sale'),
                                Forms\Components\TextInput::make('sku')
                                    ->maxLength(100)
                                    ->label('SKU'),
                                Forms\Components\TextInput::make('product_url')
                                    ->url()
                                    ->maxLength(255)
                                    ->label('External Product URL')
                                    ->helperText('Link to external store or purchase page'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Organization')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Forms\Components\Select::make('categories')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required(),
                                        Forms\Components\TextInput::make('slug'),
                                    ]),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Forms\Components\Tabs\Tab::make('Publishing')
                            ->icon('heroicon-o-globe-alt')
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
                            ]),
                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\TextInput::make('seo_title')
                                    ->maxLength(70)
                                    ->helperText('Defaults to product title if empty'),
                                Forms\Components\Textarea::make('seo_description')
                                    ->maxLength(160)
                                    ->rows(3),
                                Forms\Components\FileUpload::make('og_image')
                                    ->image()
                                    ->disk('public')
                                    ->directory(config('cms.media_upload_path', 'cms/media') . '/og')
                                    ->label('Social Share Image'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Image')
                    ->circular(false)
                    ->width(60)
                    ->height(60),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                    ]),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
