<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use CreativeCatCo\GkCmsCore\Filament\Resources\UserResource\Pages;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return config('auth.providers.users.model', 'App\\Models\\User');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Role & Access')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options(config('cms.roles', [
                                'admin' => 'Administrator',
                                'editor' => 'Editor',
                                'viewer' => 'Viewer',
                            ]))
                            ->default('editor')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Password')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->confirmed(),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->maxLength(255)
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'success' => 'admin',
                        'info' => 'editor',
                        'gray' => 'viewer',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(config('cms.roles', [
                        'admin' => 'Administrator',
                        'editor' => 'Editor',
                        'viewer' => 'Viewer',
                    ])),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
