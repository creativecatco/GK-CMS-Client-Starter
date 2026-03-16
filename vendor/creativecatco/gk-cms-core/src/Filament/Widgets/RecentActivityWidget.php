<?php

namespace CreativeCatCo\GkCmsCore\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use CreativeCatCo\GkCmsCore\Models\Post;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Recent Activity';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->with(['author', 'categories'])
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                    ]),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
