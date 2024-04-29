<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EditImprint extends Page
{
    protected static ?string $title = 'Edit Imprint';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'edit-imprint-file';
    protected static string $view = 'filament.pages.edit-imprint'; // Correctly define the view
    protected static ?string $navigationGroup = 'Files';


    public $content; // This property will bind to your form input
    public $html;
    public $liveLink;

    public function mount(): void
    {
        $this->content = $this->loadContent(); // Load the content when the page loads
        $this->html = Str::of($this->content)->markdown();
        $this->liveLink = route('imprint');

    }

    public function form(Form $form): Form
    {
        return $form->schema([
            MarkdownEditor::make('content')
                ->label('Markdown Content')
                ->default($this->content), // Load the content when the page loads
        ]);
    }


    public function loadContent()
    {
        // Adjust the path as necessary
        return File::get(resource_path('markdown/imprint.md'));
    }

    public function saveContent()
    {
        // Validate input and save the content back to the file
        $this->validate([
            'content' => 'required|string',
        ]);

        File::put(resource_path('markdown/imprint.md'), $this->content);


    }

    protected function getActions(): array
    {
        return [
            // Add a button to save the form content
            Action::make('save')
                ->label('Save')
                ->action(fn() => $this->saveContent()),
        ];
    }
}
