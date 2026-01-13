<?php

namespace App\Http\Controllers;

use App\Events\ConversionCompleted;
use App\Http\Requests\DownloadSourceRequest;
use App\Http\Requests\FetchSourceRequest;
use App\Http\Requests\IndexSourceRequest;
use App\Http\Requests\LockSourceRequest;
use App\Http\Requests\RenameSourceRequest;
use App\Http\Requests\StoreSourceRequest;
use App\Http\Requests\TranscribeSourceRequest;
use App\Http\Requests\UpdateSourceRequest;
use App\Jobs\ConvertFileToHtmlJob;
use App\Jobs\TranscriptionJob;
use App\Models\Project;
use App\Models\Source;
use App\Models\SourceStatus;
use App\Models\Variable;
use App\Traits\ValidatesStoragePath;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use OwenIt\Auditing\Models\Audit;
use Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * This controller handles the creation, updating, and deletion of sources.
 * It also handles the conversion of source files to HTML in the function convertFileToHtml()
 */
class SourceController extends Controller
{
    use ValidatesStoragePath;

    /**
     * View of the preparation page with the Sources (Documents)
     */
    public function index(IndexSourceRequest $request, $projectId)
    {

        return Inertia::render('PreparationPage', [
            'sources' => $this->fetchAndTransformSources($projectId),
            'projectId' => $projectId,
        ]);
    }

    /**
     * Provides a list of available file formats for upload.
     *
     * @return JsonResponse
     */
    public function availableFormats(Project $project)
    {
        $baseFormats = config('internalPlugins.rtf.formats');
        // TODO for plugin system
        // 1. get user
        // 2. check if user has access to project
        // 3. get transform services for project
        // 4. return available formats

        $formats = [
            '.txt' => 'Plain Text (.txt)',
            'audio/*' => 'Audio Files (.mp3, .wav, .m4a, etc.)',
        ];

        if ($baseFormats) {
            $formatList = explode(',', $baseFormats);
            foreach ($formatList as $format) {
                $formats['.'.$format] = strtoupper($format).' (.'.$format.')';
            }
        }

        return response()->json($formats);
    }

    /**
     * Stores and optionally converts a new source file.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function store(StoreSourceRequest $request)
    {
        $file = $request->file('file');
        $projectId = $request->input('projectId');
        Log::info('Uploading file: '.$file->getClientOriginalName().'.'.$file->extension().' to project ID: '.$projectId);

        // Generate filename without timestamp
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->extension();
        $isText = $extension === 'txt';
        $isMarkdown = false;

        // markdown support
        if ($isText && ($file->getMimeType() === 'text/markdown' || stripos($file->getClientOriginalName(), '.md') !== false)) {
            $extension = 'md';
            $isMarkdown = true;
        }

        $relativePath = 'projects/'.$projectId.'/sources';

        // iterate files with the same name to find a unique one
        // while (Storage::exists($relativePath.'/'.$uniqueFilename)) {
        //     $uniqueFilename = $baseFilename.'_'.$counter.'.'.$extension;
        //     $counter++;
        // }

        // we use the unique source id to store files ascii safe and collision free
        // old: $request->input('sourceId', Str::uuid()->toString())
        $sourceId = Str::uuid()->toString();
        $uniqueFilename = $sourceId.'.'.$extension;

        // move the original file to storage
        $relativeFilePath = $file->storeAs($relativePath, $uniqueFilename);
        $path = storage_path("app/{$relativeFilePath}");

        // we use a keyword to detect "new" created files that were not uploaded
        // Check for the keyword and wipe the content if found
        $fileContent = file_get_contents($path);
        $keyword = 'Llanfair­pwllgwyngyll­gogery­chwyrn­drobwll­llan­tysilio­gogo­goch';

        // Use the same filename pattern for HTML output
        $htmlOutputPath = storage_path('app/'.$relativePath.'/'.pathinfo($uniqueFilename, PATHINFO_FILENAME).'.html');

        $source = Source::updateOrCreate(
            ['id' => $sourceId],
            [
                'name' => $filename.'.'.$extension,
                'upload_path' => $path,
                'project_id' => $projectId,
                'creating_user_id' => Auth::id(),
            ]
        );

        Log::info('Conversion for '.$filename.'.'.$extension);

        $isEmpty = trim($fileContent) === $keyword;
        $isPlain = $extension === 'txt' && ! $isMarkdown;
        $status = 'converting';

        // A - creating a new empty text file
        if ($isEmpty) {
            Log::info('Keyword detected in file. Creating empty HTML document.');
            file_put_contents($htmlOutputPath, config('app.layoutBaseHtml'));
            $htmlContent = config('app.layoutBaseHtml');
            $status = 'converted:html';
        }

        // B - converting a plain text file as is
        if (! $isEmpty && $isPlain) {
            Log::info('Converting TXT file to HTML locally.');
            $htmlContent = $this->convertTxtToHtml($path, $projectId);
            $status = 'converted:html';
        }

        // C - converting any other supported text-based formats using transform plugin
        if (! $isEmpty && ! $isPlain) {
            Log::info('Converting file to HTML using job dispatch.');
            $htmlContent = $this->convertFileToHtml($path, $projectId, $source->id, $filename);
        }

        // D - File is HTML already, then clean and store it directly
        // TODO: implemented

        // create initial source status
        $sourceStatus = SourceStatus::updateOrCreate(
            ['source_id' => $source->id],
            ['path' => $htmlOutputPath, 'status' => $status]
        );

        //
        //          if (App::environment(['production', 'staging'])) {
        //
        //                          } else {
        //                              Log::info('Converting file to HTML locally.');
        //                              try {
        //                                  $htmlContent = $this->convertFileToHtmlLocally($path, $projectId);
        //                                  event(new ConversionCompleted($projectId, $source->id));
        //                                  } catch (Exception $e) {
        //                                     Log::info('Conversion failed, remove original file at '.$path);
        //                                      File::delete($path);
        //                                      Source::destroy($source->id);
        //                                      throw $e;
        //                                  }
        //                          }

        return response()->json([
            'newDocument' => [
                'id' => $source->id,
                'name' => $source->name,
                'type' => 'text',
                'user' => auth()->user()->name,
                'content' => $htmlContent ?? '',
                'converted' => $htmlContent ? true : false,
                'converting' => $status === 'converting',
                'exists' => File::exists($htmlOutputPath),
                'failed' => false,
            ],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    private function fetchAndTransformSources($projectId)
    {
        $sources = Source::where('project_id', $projectId)->with('variables')->get();

        return $sources->map(function ($source) {
            $converted = $source->converted;
            $exists = $converted ? File::exists($converted->path) : false;
            $status = $source->sourceStatuses()->latest()->first();

            return [
                'id' => $source->id,
                'name' => $source->name,
                'type' => $source->type,
                'user' => $source->creatingUser->name,
                'userPicture' => $source->creatingUser->profile_photo_url,
                'date' => $source->created_at->toDateString(),
                'variables' => $source->transformVariables(),
                'converted' => (bool) $converted,
                'exists' => $exists,
                'status' => $status && $status->status ? $status->status : 'unknown',
                'failed' => $status && $status->status === 'failed',
            ];
        });
    }

    /**
     * Redirects the user to the coding page for the specified source. This is used when a file is already locked.
     *
     **/
    public function lockAndCode(LockSourceRequest $request, $sourceId)
    {
        try {
            $source = Source::findOrFail($sourceId);

            // Lock the source (if not already locked)
            $source->lock();
            $source->createAudit(Source::AUDIT_LOCKED, ['message' => $source->name.' has been locked']);

            return to_route('coding.show', ['project' => $source->project_id, 'source' => $source]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Unlock a source by setting the isLocked variable to false.
     *
     * @return JsonResponse
     */
    public function unlock(LockSourceRequest $request, $sourceId)
    {
        try {
            $source = Source::findOrFail($sourceId);

            $source->unlock();
            $source->createAudit(Source::AUDIT_UNLOCKED, ['message' => $source->name.' has been unlocked']);

            return response()->json(['success' => true, 'message' => 'Source unlocked successfully']);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * @return JsonResponse
     */
    public function fetchDocument(FetchSourceRequest $request, $id)
    {
        $source = Source::with('variables')->findOrFail($id);

        if (! $source->converted) {
            return response()->json(['error' => 'Source file not found'], 404);
        }

        $validatedPath = $this->validateStoragePath($source->converted->path);
        if ($validatedPath === null) {
            return response()->json(['error' => 'Invalid file path'], 400);
        }

        $content = file_get_contents($validatedPath);
        $source->content = $content;
        $source->variables = $source->transformVariables();

        return response()->json($source);
    }

    /**
     * Updates the content of a document
     *
     * @return JsonResponse
     */
    public function update(UpdateSourceRequest $request)
    {
        $content = $request->input('content');
        $id = $request->input('id');

        $source = Source::findOrFail($id);  // This throws a 404 if the model is not found

        try {

            if (! $source) {

                return response()->json(['success' => false, 'message' => 'Document not found']);
            }

            if (! $source->converted) {
                return response()->json(['success' => false, 'message' => 'Source file not found']);
            }

            $validatedPath = $this->validateStoragePath($source->converted->path);
            if ($validatedPath === null) {
                return response()->json(['success' => false, 'message' => 'Invalid file path']);
            }

            $source->modifying_user_id = auth()->id();
            $source->save();

            file_put_contents($validatedPath, $content);
            $audit = new Audit([
                'user_type' => 'App\Models\User',
                'user_id' => auth()->id(),
                'event' => 'content updated',
                'auditable_id' => $source->id,
                'auditable_type' => get_class($source),
                'new_values' => ['message' => 'content of '.$source->name.' has been modified'],
            ]);

            $audit->save();

            return response()->json(['success' => true, 'message' => 'Document successfully saved']);
        } catch (Exception $e) {

            return response()->json(['success' => false, 'message' => 'An error occurred while saving']);
        }
    }

    /**
     * Converts a file to HTML using a queued job, where an external service is called.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    private function convertFileToHtml($filePath, $projectId, $sourceId, $filename)
    {
        ConvertFileToHtmlJob::dispatch($filePath, $projectId, $sourceId, $filename)->onQueue('conversion');

        return response()->json(['message' => 'Conversion in progress']);
    }

    /**
     * This uses a python script to convert a file to HTML
     *
     * @return false|string
     *
     * @throws Exception
     */
    private function convertFileToHtmlLocally($filePath, $projectId)
    {
        // Define the output directory for the HTML file using the projectId
        $outputDirectory = storage_path('app/projects/'.$projectId.'/sources');
        if (! File::exists($outputDirectory)) {
            File::makeDirectory($outputDirectory, 0755, true);
        }

        $outputHtmlPath = $outputDirectory.'/'.pathinfo($filePath, PATHINFO_FILENAME).'.html';

        // Define the path to the Python script
        $scriptPath = dirname(base_path(), 1).'/services/transform/convert-rtf-to-html/convert_rtf_to_html_locally.py';

        // Check if the Python script file exists
        if (! file_exists($scriptPath)) {
            throw new Exception('Python script not found at: '.$scriptPath);
        }

        // Build the command to execute the Python script
        $command = escapeshellcmd('/usr/bin/python3 '.$scriptPath).' '.escapeshellarg($filePath).' '.escapeshellarg($outputDirectory).' 2>&1';
        Log::info('Python script command: '.$command);

        // Execute the command
        $output = null;
        $retval = null;
        exec($command, $output, $retval);

        $message = implode('\n', $output);
        if ($retval !== 0) {
            throw new Exception('Python script failed with return code '.$retval.'. Output: '.$message.';');
        }

        // Check if the output file was created
        if (file_exists($outputHtmlPath)) {
            return file_get_contents($outputHtmlPath);
        } else {

            throw new Exception('Conversion failed. Script output: '.implode('\n', $output));
        }
    }

    /**
     * Rename a Document
     *
     * @return JsonResponse
     */
    public function rename(RenameSourceRequest $request, $sourceId)
    {
        $source = Source::findOrFail($sourceId);

        try {
            $source->name = $request->input('name');
            $source->save();

            $source->createAudit(Source::AUDIT_RENAMED, ['name' => $source->name]);

            return response()->json([
                'success' => true,
                'message' => 'Source renamed successfully',
                'source' => $source,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while renaming the source: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * @return string
     */
    public function convertTxtToHtml($txtFilePath, $projectId)
    {
        // Define the output directory for the HTML file using the projectId
        $outputDirectory = storage_path('app/projects/'.$projectId.'/sources');
        if (! File::exists($outputDirectory)) {
            File::makeDirectory($outputDirectory, 0755, true);
        }
        $outputHtmlPath = $outputDirectory.'/'.pathinfo($txtFilePath, PATHINFO_FILENAME).'.html';

        // Read the contents of the TXT file
        $txtContent = file_get_contents($txtFilePath);

        if ($txtContent === false) {
            // Handle error if file could not be read
            return 'Error reading file.';
        }

        // Detect the encoding of the text
        $encoding = mb_detect_encoding($txtContent, mb_list_encodings(), true);
        if ($encoding === false) {
            // Handle error if encoding could not be detected
            return 'Error detecting encoding.';
        }

        // Simple approach: Only convert if not already UTF-8
        if (! mb_check_encoding($txtContent, 'UTF-8')) {
            // If not UTF-8, try to convert from detected encoding
            $encoding = mb_detect_encoding($txtContent, mb_list_encodings(), true) ?: 'Windows-1252';
            $utf8Content = iconv($encoding, 'UTF-8//TRANSLIT//IGNORE', $txtContent);
        } else {
            $utf8Content = $txtContent; // Already UTF-8, leave it alone
        }

        // Convert special characters to HTML entities
        $htmlContent = htmlspecialchars($utf8Content);
        $htmlContent = nl2br($htmlContent);

        // Save the HTML content to the output file
        file_put_contents($outputHtmlPath, $htmlContent);

        return $htmlContent;
    }

    /**
     * @return JsonResponse
     */
    public function destroy($id)
    {

        // Find the document
        $source = Source::findOrFail($id);  // This throws a 404 if the model is not found

        // It will automatically look for a method named 'delete' in the policy for the Source model
        if (! Gate::allows('delete', $source)) {
            return response()->json(['success' => false, 'message' => 'Not allowed'], 403);
        }
        try {

            // Delete the plain_text file
            if ($source->upload_path) {
                // Assuming it's a relative path from Laravel's base directory
                $plainTextFullPath = $source->upload_path;
                if (File::exists($plainTextFullPath)) {
                    File::delete($plainTextFullPath);
                }
            }

            // Delete the rich_text file
            if (($source->converted && $source->converted->path)) {
                // Assuming it's an absolute path from the system's root
                if (File::exists($source->converted->path)) {
                    File::delete($source->converted->path);

                }
            }
            SourceStatus::where('source_id', $source->id)->delete();

            // Delete the database record
            $source->delete();

            return response()->json(['success' => true, 'message' => 'Document deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }

    /**
     * @throws Exception
     */
    private function getHtmlContent(string $path, mixed $projectId, Source $source, bool $fromAdminPanel = false): string|false
    {

        // Existing conversion logic
        if (App::environment(['production', 'staging'])) {
            $this->convertFileToHtml($path, $projectId, $source->id);
        } else {
            $htmlContent = $this->convertFileToHtmlLocally($path, $projectId);
            event(new ConversionCompleted($projectId, $source->id));
        }

        if ($fromAdminPanel) {
            // Logic to handle the call from the admin panel
            $extension = pathinfo($source->upload_path, PATHINFO_EXTENSION);
            $newPath = Str::replaceLast($extension, '.html', $source->upload_path);
            $source->converted->path = $newPath;
            $source->save();
        }

        return $htmlContent ?? true;
    }

    public function retryConversion(Project $project, Source $source)
    {
        // Check if the user can view the source
        if (! Gate::allows('view', $source)) {
            return response()->json(['message' => 'Not authorized to view this source'], 403);
        }

        $this->getHtmlContent($source->upload_path, $project->id, $source, true);

        return response()->json(['message' => 'Conversion in progress']);
    }

    /**
     * Requests a transcription from the external example service.
     * Handle database transactions and error handling, leave file processing to the job.
     */
    public function transcribe(TranscribeSourceRequest $request)
    {
        $user = Auth::user();
        $file = $request->file('file');

        try {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $projectId = $request->input('project_id');
            $relativeFilePath = $file->storeAs('projects/'.$projectId.'/sources', $filename);
            $path = storage_path("app/{$relativeFilePath}");

            $source = Source::create([
                'name' => $file->getClientOriginalName(),
                'creating_user_id' => $user->id,
                'project_id' => $projectId,
                'type' => 'audio',
                'upload_path' => $path,
            ]);

            SourceStatus::create([
                'source_id' => $source->id,
                'status' => 'converting',
            ]);

            TranscriptionJob::dispatch($path, $projectId, $source->id)->onQueue('conversion');

            return response()->json([
                'message' => 'File uploaded and processing started',
                'newDocument' => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'type' => 'audio',
                    'user' => auth()->user()->name,
                    'content' => '',
                    'converted' => false,
                ],
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function retryTranscription(Project $project, Source $source)
    {
        // check if job exists and what status it has
        // retry job? restart job? what do here?
        $jobRef = Variable::where('source_id', $source->id)->where('name', 'transcription_job_status')->first();

        if (! $jobRef) {
            return response()->json(['message' => 'Conversion has finished', 'status' => 'finished']);
        }

        if ($jobRef->text_value == 'failed') {
            TranscriptionJob::dispatch($source->upload_path, $project->id, $source->id)->onQueue('conversion');

            return response()->json(['message' => 'Conversion restarted', 'status' => 'restarted']);
        } else {
            return response()->json(['message' => 'Conversion is still running', 'status' => $jobRef->text_value]);
        }
    }

    /**
     * Downloads the source file
     *
     * @return BinaryFileResponse
     */
    public function download(DownloadSourceRequest $request, $sourceId)
    {
        $source = Source::findOrFail($sourceId);
        $source->createAudit(Source::AUDIT_DOWNLOADED, ['message' => $source->name.' was downloaded']);

        return response()->download($source->upload_path, $source->name);
    }
}
