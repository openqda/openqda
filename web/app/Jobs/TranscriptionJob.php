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
        Log::info('START TranscriptionJob');
        $uploadUrl = config('internalPlugins.aTrainUpload');
        $processingUrl = config('internalPlugins.aTrainProcess');
        $downloadUrl = config('internalPlugins.aTrainDownload');
        $deleteUrl = config('internalPlugins.aTrainDelete');

        if (! $uploadUrl || ! $processingUrl || ! $downloadUrl || ! $deleteUrl) {
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

        $fileId = '';

        // Step1: upload the file and retrieve a fileId and
        // estimated length of processing in seconds
        try {
            Log::info('Send the file to the aTrain service');
            $response = Http::attach(
                'uploaded',                             // field name
                file_get_contents($this->filePath),     // file content
                basename($this->filePath),              // file name
            )
                ->timeout(30)
                ->post($uploadUrl);

            if ($response->successful()) {
                Log::info('Upload successful');
                $fileId = $response->json('file_id');
                $length = intval($response->json('length'));
                Log::info('file_id='.$fileId." length=".$length);

                // Save the file_id in the variables table
                Variable::create([
                    'source_id' => $this->sourceId,
                    'name' => 'atrain_file_id',
                    'type_of_variable' => 'string',
                    'text_value' => $fileId,
                ]);
                // Save the length in the variables table, too
                Variable::create([
                    'source_id' => $this->sourceId,
                    'name' => 'atrain_length',
                    'type_of_variable' => 'int',
                    'integer_value' => $length,
                ]);

            } else {
                Log::error($response);
                throw new \Exception('Failed to upload file to aTrain service:');
            }

            // Step2: start processing here, because the transcription service
            // does not start it automatically
            Log::info("start processing at ".$processingUrl.$fileId);
            $response = Http::timeout($length * 2)->post($processingUrl.$fileId);

            if (! $response->successful()) {
                Log::error($response);
                throw new \Exception('Failed to upload file to aTrain service:');
            }

            // Step3: download the result
            $response = $this->downloadTranscribedFile($downloadUrl.$fileId);

            if (! $response->successful()) {
                $this->fail($response->status());
            } else {
                Log::info("start saving downloaded file");
                $text = $response->body();
                file_put_contents($outputFilePath, $text);

                // create a new status for the same source.
                $sourceStatus = SourceStatus::where('source_id', $this->sourceId)->first();
                $sourceStatus->status = 'converted:txt';
                $sourceStatus->path = $outputFilePath;
                $sourceStatus->update();

                // delete file on aTrain
                Log::info("delete files at ".$deleteUrl.$fileId);
                Http::timeout(30)->delete($deleteUrl.$fileId);
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

    private function downloadTranscribedFile($url)
    {
        Log::info("start download at ".$url);
        return Http::timeout(30)->get($url);
    }

    public function failed($exception)
    {
        Log::error('Job failed: '.$exception->getMessage());
        // Notify admins, or perform other failure logic
    }
}
