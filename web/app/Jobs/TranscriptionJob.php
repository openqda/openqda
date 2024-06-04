<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileId;
    protected $projectId;
    protected $sourceId;

    /**
     * Create a new job instance.
     */
    public function __construct($fileId, $projectId, $sourceId)
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
            $response = $this->downloadTranscribedFile($outputFilePath);

            if (! $response->successful()) {
                $this->fail($response->status());
            } else {
                $text = $response->body();
                file_put_contents($outputFilePath, $text);

                // also update source status and save output file path
                $sourceStatus = SourceStatus::where($this->sourceId)->first();
                $sourceStatus->status = 'converted';
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

    private function downloadTranscribedFile($outputFilePath)
    {
        return Http::timeout(120)->get(config('internalPlugins.atrain.endpoint').$this->fileId);
    }

    public function failed($exception)
    {
        Log::error('Job failed: '.$exception->getMessage());
        // Notify admins, or perform other failure logic
    }
}
