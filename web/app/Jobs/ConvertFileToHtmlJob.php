<?php

namespace App\Jobs;

use App\Events\ConversionCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConvertFileToHtmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    protected $projectId;

    protected $sourceId;

    public $tries = 3; // Number of times the job may be attempted

    public $timeout = 120; // Number of seconds the job can run before timing out

    public function __construct($filePath, $projectId, $sourceId)
    {
        $this->filePath = $filePath;
        $this->projectId = $projectId;
        $this->sourceId = $sourceId;

    }

    public function handle(): void
    {
        $flaskServerUrl = config('convertrtftohtml.endpoint');
        $secretPassword = config('convertrtftohtml.pwd');

        if (! $flaskServerUrl || ! $secretPassword) {
            Log::error('Conversion failed: Missing configuration.');
            $this->fail();

            return;
        }

        $outputDirectory = $this->ensureOutputDirectory();

        // Extract filename without extension
        $filenameWithoutExt = pathinfo($this->filePath, PATHINFO_FILENAME);

        // Replace spaces with underscores in the filename
        $filenameWithoutExt = str_replace(' ', '_', $filenameWithoutExt);

        // Construct the new output HTML path
        $outputHtmlPath = $outputDirectory.'/'.$filenameWithoutExt.'.html';

        // Remove single quotes from the path
        if (str_starts_with($outputHtmlPath, "'")) {
            $outputHtmlPath = substr($outputHtmlPath, 1);
        }
        if (str_ends_with($outputHtmlPath, "'")) {
            $outputHtmlPath = substr($outputHtmlPath, 0, -1);
        }

        try {
            $response = $this->sendFileForConversion($outputHtmlPath);

            if (! $response->successful()) {
                $this->fail($response->status());
            } else {
                $htmlContent = $response->body();

                // Remove <style></style> tags and their content
                // this gave a weird layout on the whole coding page
                $htmlContent = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $htmlContent);

                file_put_contents($outputHtmlPath, $htmlContent);
                event(new ConversionCompleted($this->projectId, $this->sourceId));

            }

        } catch (\Exception $e) {
            File::delete($outputHtmlPath); // Cleanup
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

    private function sendFileForConversion($outputHtmlPath)
    {
        return Http::timeout(120)->attach(
            'file', file_get_contents($this->filePath), basename($this->filePath)
        )->post(config('convertrtftohtml.endpoint'), [
            'password' => config('convertrtftohtml.pwd'),
        ]);
    }

    public function failed($exception)
    {
        Log::error('Job failed: '.$exception->getMessage());
        // Notify admins, or perform other failure logic
    }
}
