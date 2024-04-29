<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelectionResource\Pages;
use App\Filament\Resources\SelectionResource\RelationManagers;
use App\Models\Selection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SelectionResource extends Resource
{
    protected static ?string $model = Selection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                Forms\Components\TextInput::make('creating_user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('modifying_user_id')
                    ->numeric(),
                Forms\Components\Select::make('code_id')
                    ->relationship('code', 'name')
                    ->required(),
                Forms\Components\Select::make('source_id')
                    ->relationship('source', 'name')
                    ->required(),
                Forms\Components\Textarea::make('text')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('start_position')
                    ->maxLength(255),
                Forms\Components\TextInput::make('end_position')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creating_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modifying_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source.id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_position')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_position')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSelections::route('/'),
            'create' => Pages\CreateSelection::route('/create'),
            'edit' => Pages\EditSelection::route('/{record}/edit'),
        ];
    }
}
