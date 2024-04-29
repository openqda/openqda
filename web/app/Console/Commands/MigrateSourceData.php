<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Models\SourceStatus;
use Illuminate\Console\Command;

class MigrateSourceData extends Command
{

    protected $signature = 'sources:rich-text-migration';
    protected $description = 'Migrates rich text data to SourceStatus.';

    public function handle()
    {
        Source::whereNotNull('rich_text_path')->chunk(100, function ($sources) {
            foreach ($sources as $source) {
                SourceStatus::create([
                    'source_id' => $source->id,
                    'status' => 'converted:html',
                    'path' => $source->rich_text_path
                ]);

                // Optional: Clear the rich_text_path on the source

            }
        });

        $this->info('Rich text migrated successfully!');

    }

}
