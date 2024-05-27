<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestUsersWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function query(): Builder
    {
        return User::query()->latest()->limit(10); // Using `latest()` assumes you have a 'created_at' column
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),

                CheckboxColumn::make('email_verified_at')
                    ->label('Verified')
                    ->disabled()
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->date('d.m.Y'), // Format the date as DD.MM.YYYY

            ])->filters([
                QueryBuilder::make('created_at')
                    ->label('Created At')
                    ->constraints([
                        DateConstraint::make('created_at'),
                    ]),
            ], layout: FiltersLayout::AboveContent)->defaultSort('created_at', 'desc');
    }
}
