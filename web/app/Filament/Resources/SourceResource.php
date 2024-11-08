<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceResource\Pages;
use App\Http\Controllers\SourceController;
use App\Models\Project;
use App\Models\Source;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SourceResource extends Resource
{
    protected static ?string $model = Source::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('creating_user_id')
                    ->relationship('creatingUser', 'name')
                    ->required(),
                Forms\Components\Select::make('modifying_user_id')
                    ->relationship('modifyingUser', 'name'),
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'text',
                        'audio' => 'audio',
                        'video' => 'video',
                        'pdf' => 'pdf',
                        'picture' => 'picture',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('upload_path')
                    ->maxLength(255),
                Actions::make([
                    Action::make('convertToHtml')
                        ->label('Convert to HTML')
                        ->action(function ($record) {
                            $controller = new SourceController();
                            $controller->getHtmlContent($record->upload_path, $record->project_id, $record, true);
                        })
                        ->icon('heroicon-o-document-text')
                        ->requiresConfirmation()
                        ->modalDescription('convert this will overwrite the current html content, if exists'),
                    Action::make('coding page')
                        ->url(fn (Source $record): string => route('coding.show', ['project' => $record->project_id, 'source' => $record->id]))
                        ->icon('heroicon-o-document-text')
                        ->openUrlInNewTab(),
                    Action::make('downloadRichText')
                        ->label('Download Rich Text')
                        ->action(function ($record): StreamedResponse {
                            $fullPath = $record->converted->path; // Assuming this contains the full path
                            $relevantPath = substr($fullPath, strpos($fullPath, 'app/') + 4); // Extracts everything after 'app/'

                            return Storage::download($relevantPath);
                        })
                        ->icon('heroicon-o-arrow-down')
                        ->disabled(fn ($record) => empty($record->converted->path)),
                    Action::make('downloadPlainText')
                        ->label('Download Plain Text')
                        ->action(function ($record): StreamedResponse {
                            $fullPath = $record->upload_path; // Assuming this contains the full path
                            $relevantPath = substr($fullPath, strpos($fullPath, 'app/') + 4); // Extracts everything after 'app/'

                            return Storage::download($relevantPath);

                        })
                        ->icon('heroicon-o-arrow-down')
                        ->disabled(fn ($record) => empty($record->upload_path)),
                ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\IconColumn::make('converted->path_exists')
                    ->label('Converted')
                    ->getStateUsing(function ($record) {

                        return (filled($record->converted->path) && $record->converted->path !== ' ' && $record->converted->path !== '') && (file_exists($record->converted->path));
                    })
                    ->trueIcon('heroicon-s-check')
                    ->falseIcon('heroicon-s-x-mark'),
                Tables\Columns\TextColumn::make('creatingUser.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modifyingUser.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('base_path')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('upload_path')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('converted->path')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('path')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_path')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('convertToHtml')
                    ->label('Convert to HTML')
                    ->action(function ($record) {
                        $controller = new SourceController();
                        $controller->retryConversion(Project::find($record->project_id), $record);
                    })
                    ->requiresConfirmation()
                    ->modalDescription('convert this will overwrite the current html content, if exists')
                    ->icon('heroicon-o-document-text'),
                Tables\Actions\Action::make('coding page')
                    ->url(fn (Source $record): string => route('coding.show', ['project' => $record->project_id, 'source' => $record->id]))
                    ->icon('heroicon-o-document-text')
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('downloadRichText')
                    ->label('Download Rich Text')
                    ->action(function ($record): StreamedResponse {
                        $fullPath = $record->converted->path; // Assuming this contains the full path
                        $relevantPath = substr($fullPath, strpos($fullPath, 'app/') + 4); // Extracts everything after 'app/'

                        return Storage::download($relevantPath);
                    })
                    ->icon('heroicon-o-arrow-down')
                    ->disabled(fn ($record) => empty($record->converted->path)),
                Tables\Actions\Action::make('downloadPlainText')
                    ->label('Download Plain Text')
                    ->action(function ($record): StreamedResponse {
                        $fullPath = $record->upload_path; // Assuming this contains the full path
                        $relevantPath = substr($fullPath, strpos($fullPath, 'app/') + 4); // Extracts everything after 'app/'

                        return Storage::download($relevantPath);

                    })
                    ->icon('heroicon-o-arrow-down')
                    ->disabled(fn ($record) => empty($record->upload_path)),
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
            'index' => Pages\ListSources::route('/'),
            'create' => Pages\CreateSource::route('/create'),
            'edit' => Pages\EditSource::route('/{record}/edit'),
        ];
    }
}
