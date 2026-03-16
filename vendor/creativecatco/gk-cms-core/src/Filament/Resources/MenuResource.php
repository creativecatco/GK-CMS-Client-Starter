<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources;

use CreativeCatCo\GkCmsCore\Filament\Resources\MenuResource\Pages;
use CreativeCatCo\GkCmsCore\Models\Menu;
use CreativeCatCo\GkCmsCore\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationGroup = 'Design';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Menus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Menu Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Menu Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Main Navigation'),

                        Forms\Components\Select::make('location')
                            ->label('Menu Location')
                            ->required()
                            ->options([
                                'header' => 'Header Navigation',
                                'footer' => 'Footer Navigation',
                                'footer_secondary' => 'Footer Secondary Links',
                            ])
                            ->unique(ignoreRecord: true)
                            ->helperText('Each location can only have one menu assigned.'),
                    ])->columns(2),

                Forms\Components\Section::make('Menu Items')
                    ->description('Add pages, custom links, or anchor links to your menu. Drag to reorder.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Item Type')
                                    ->options([
                                        'page' => 'Page',
                                        'custom' => 'Custom Link',
                                        'anchor' => 'Anchor Link',
                                    ])
                                    ->default('page')
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('page_id')
                                    ->label('Select Page')
                                    ->options(function () {
                                        return Page::where(function ($q) {
                                            $q->where('page_type', 'page')->orWhereNull('page_type');
                                        })->pluck('title', 'id')->toArray();
                                    })
                                    ->searchable()
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'page')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('label')
                                    ->label('Display Label')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Menu item text')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('url')
                                    ->label('URL')
                                    ->visible(fn (Forms\Get $get) => in_array($get('type'), ['custom', 'anchor']))
                                    ->placeholder(fn (Forms\Get $get) => $get('type') === 'anchor' ? '#section-id' : 'https://example.com')
                                    ->helperText(fn (Forms\Get $get) => $get('type') === 'anchor' ? 'Use # followed by the section ID' : 'Full URL including https://')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('target')
                                    ->label('Open In')
                                    ->options([
                                        '_self' => 'Same Window',
                                        '_blank' => 'New Tab',
                                    ])
                                    ->default('_self')
                                    ->columnSpan(1),

                                Forms\Components\Repeater::make('children')
                                    ->label('Sub-menu Items')
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->label('Type')
                                            ->options([
                                                'page' => 'Page',
                                                'custom' => 'Custom Link',
                                                'anchor' => 'Anchor Link',
                                            ])
                                            ->default('page')
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('page_id')
                                            ->label('Page')
                                            ->options(function () {
                                                return Page::where(function ($q) {
                                                    $q->where('page_type', 'page')->orWhereNull('page_type');
                                                })->pluck('title', 'id')->toArray();
                                            })
                                            ->searchable()
                                            ->visible(fn (Forms\Get $get) => $get('type') === 'page'),

                                        Forms\Components\TextInput::make('label')
                                            ->label('Label')
                                            ->required()
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('url')
                                            ->label('URL')
                                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['custom', 'anchor'])),

                                        Forms\Components\Select::make('target')
                                            ->label('Open In')
                                            ->options([
                                                '_self' => 'Same Window',
                                                '_blank' => 'New Tab',
                                            ])
                                            ->default('_self'),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->collapsed()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'New Item')
                            ->defaultItems(0)
                            ->addActionLabel('Add Menu Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Menu Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'header' => 'Header',
                        'footer' => 'Footer',
                        'footer_secondary' => 'Footer Secondary',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'header' => 'primary',
                        'footer' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('items')
                    ->label('Items')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' items' : '0 items'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
