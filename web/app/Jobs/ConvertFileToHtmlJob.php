<?php

namespace App\Jobs;

use App\Events\ConversionCompleted;
use App\Events\ConversionFailed;
use App\Models\SourceStatus;
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

    protected $filename;

    protected $loginfo;

    public $tries = 3; // Number of times the job may be attempted

    public $timeout = 120; // Number of seconds the job can run before timing out

    public function __construct($filePath, $projectId, $sourceId, $filename)
    {
        $this->filePath = $filePath;
        $this->projectId = $projectId;
        $this->sourceId = $sourceId;
        $this->filename = $filename;
        $this->loginfo = '[ConvertFileToHtmlJob]['.$this->projectId.'|'.$this->sourceId.']: ';
    }

    public function handle(): void
    {
        Log::info($this->loginfo.'Starting conversion on '.$this->filePath);
        $flaskServerUrl = config('internalPlugins.rtf.endpoint');
        $secretPassword = config('internalPlugins.rtf.pwd');

        if (! $flaskServerUrl || ! $secretPassword) {
            $message = $this->loginfo.'conversion failed: missing configuration';
            Log::info($message);
            $this->fail($message);

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
            Log::info($this->loginfo.'send file for conversion to: '.$flaskServerUrl);
            $response = $this->sendFileForConversion($outputHtmlPath);
            Log::info($this->loginfo.$response->successful() ? 'successful' : 'failed'.' response received: '.$response->status());

            if (! $response->successful()) {
                Log::error($this->loginfo.'fail response from remove with status: '.$response->status());
                $this->fail($response->status());

                return;
            }
            $htmlContent = $response->body();

            // Remove <style></style> tags and their content
            // this gave a weird layout on the whole coding page
            $htmlContent = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $htmlContent);

            // attempt to wrap as json to verify valid html and encoding
            $testJson = json_encode(['html' => $htmlContent]);

            Log::info($this->loginfo.' saving converted file to: '.$outputHtmlPath);
            file_put_contents($outputHtmlPath, $htmlContent);

            Log::info($this->loginfo.' update source status converted:html');
            SourceStatus::updateOrCreate(
                ['source_id' => $this->sourceId],
                ['status' => 'converted:html']
            );

            event(new ConversionCompleted($this->projectId, $this->sourceId, $this->filename));
        } catch (\Exception $e) {
            Log::error('Conversion or file operation failed: '.$e->getMessage());
            File::delete($outputHtmlPath); // Cleanup
            $this->fail($e);
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
            'file',
            file_get_contents($this->filePath),
            basename($this->filePath)
        )->post(config('internalPlugins.rtf.endpoint'), [
            'title' => $this->filename,
            'password' => config('internalPlugins.rtf.pwd'),
        ]);
    }

    public function failed($exception)
    {
        // update source status to failed
        SourceStatus::updateOrCreate(
            ['source_id' => $this->sourceId],
            ['status' => 'failed']
        );
        // broadcast failure event
        $message = $exception ? $exception->getMessage() : 'Conversion failed';
        event(new ConversionFailed($this->projectId, $this->sourceId, $this->filename, $message));
    }
}
