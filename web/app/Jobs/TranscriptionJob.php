<?php

namespace App\Jobs;

use App\Events\ConversionCompleted;
use App\Models\SourceStatus;
use App\Models\Variable;
use File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    protected $projectId;

    protected $sourceId;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $projectId, $sourceId)
    {
        $this->filePath = $filePath;
        $this->projectId = $projectId;
        $this->sourceId = $sourceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $atrainUrl = config('internalPlugins.atrain.endpoint');

        if (! $atrainUrl) {
            Log::error('Conversion failed: Missing configuration.');
            $this->fail();

            return;
        }

        $outputDirectory = $this->ensureOutputDirectory();

        // Extract filename without extension
        $filenameWithoutExt = pathinfo($this->filePath, PATHINFO_FILENAME);

        // Replace spaces with underscores in the filename
        $filenameWithoutExt = str_replace(' ', '_', $filenameWithoutExt);

        // Construct the new output path
        $outputFilePath = $outputDirectory.'/'.$filenameWithoutExt.'.txt';

        try {
            // Send the file to the aTrain service
            $response = Http::attach('upload', file_get_contents($this->filePath))->post(config('app.atrain'));

            if ($response->successful()) {
                $fileId = $response->json('file_id');

                // Save the file_id in the variables table
                Variable::create([
                    'source_id' => $this->sourceId,
                    'name' => 'atrain_file_id',
                    'type_of_variable' => 'string',
                    'text_value' => $fileId,
                ]);

            } else {
                throw new \Exception('Failed to upload file to aTrain service');
            }

            $response = $this->downloadTranscribedFile($fileId);

            if (! $response->successful()) {
                $this->fail($response->status());
            } else {
                $text = $response->body();
                file_put_contents($outputFilePath, $text);

                // create a new status for the same source.
                $sourceStatus = SourceStatus::where($this->sourceId)->first();
                $sourceStatus->status = 'converted:txt';
                $sourceStatus->path = $outputFilePath;
                $sourceStatus->update();
                event(new ConversionCompleted($this->projectId, $this->sourceId));
            }

        } catch (\Exception $e) {
            File::delete($outputFilePath); // Cleanup
            $this->fail($e);
            Log::error('Conversion or file operation failed: '.$e->getMessage());
        }
    }

    private function ensureOutputDirectory()
    {

        $outputDirectory = storage_path('app/projects/'.$this->projectId.'/sources');
        if (! File::exists($outputDirectory)) {
            File::makeDirectory($outputDirectory, 0755, true);
        }

        return $outputDirectory;
    }

    private function downloadTranscribedFile($fileId)
    {
        return Http::timeout(120)->get(config('internalPlugins.atrain.endpoint').$fileId);
    }

    public function failed($exception)
    {
        Log::error('Job failed: '.$exception->getMessage());
        // Notify admins, or perform other failure logic
    }
}
