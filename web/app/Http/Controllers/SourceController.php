<?php

namespace App\Http\Controllers;

use App\Events\ConversionCompleted;
use App\Jobs\ConvertFileToHtmlJob;
use App\Jobs\TranscriptionJob;
use App\Models\Project;
use App\Models\Source;
use App\Models\SourceStatus;
use App\Models\Variable;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use OwenIt\Auditing\Models\Audit;

/**
 * This controller handles the creation, updating, and deletion of sources.
 * It also handles the conversion of source files to HTML in the function convertFileToHtml()
 */
class SourceController extends Controller
{
    public function index(Request $request, $projectId)
    {

        $project = Project::findOrFail($projectId);

        // Use ProjectPolicy to authorize the action
        if (!Gate::allows('view', $project)) {
            return response()->json(['success' => false, 'message' => 'Not allowed'], 403);
        }

        return Inertia::render('PreparationPage', [
            'sources' => $this->fetchAndTransformSources($projectId),
            'projectId' => $projectId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|extensions:rtf,txt',
        ]);

        if (RateLimiter::tooManyAttempts('upload-limit:' . optional($request->user())->id ?: $request->ip(), $perMinute = 5)) {
            $seconds = RateLimiter::availableIn('send-message:' . optional($request->user())->id ?: $request->ip());

            return 'You may try again in ' . $seconds . ' seconds.';
        }

        $file = $request->file('file');
        $projectId = $request->input('projectId');

        // Generate a unique filename with a timestamp
        // then store the file
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = str_replace(' ', '_', $filename);
        $extension = $file->getClientOriginalExtension();
        $uniqueFilename = $filename . '_' . time() . '.' . $extension;
        $relativeFilePath = $file->storeAs('projects/' . $projectId . '/sources', $uniqueFilename);
        $path = storage_path("app/{$relativeFilePath}");

        // Check for the keyword and wipe the content if found
        $fileContent = file_get_contents($path);
        $keyword = 'Llanfair­pwllgwyngyll­gogery­chwyrn­drobwll­llan­tysilio­gogo­goch';
        // Append the timestamp to the HTML file as well
        $htmlOutputPath = storage_path('app/projects/' . $projectId . "/sources/{$filename}_" . time() . '.html');

        $source = Source::updateOrCreate(
            ['id' => $request->input('sourceId', Str::uuid()->toString())],
            [
                'name' => $uniqueFilename,
                'upload_path' => $path,
                'project_id' => $projectId,
                'creating_user_id' => Auth::id(),
            ]
        );

        $sourceStatus = SourceStatus::updateOrCreate(
            ['source_id' => $source->id],
            ['path' => $htmlOutputPath, 'status' => 'converted:html']
        );

        // create an empty new file
        if (trim($fileContent) === $keyword) {
            file_put_contents($htmlOutputPath, config('app.layoutBaseHtml'));
            $htmlContent = config('app.layoutBaseHtml');
        } else {
            // elaborate the uploaded file
            if ($extension === 'txt') {
                // Convert TXT file to HTML
                $htmlContent = $this->convertTxtToHtml($path, $projectId);
            } elseif ($extension === 'rtf') {
                if (App::environment(['production', 'staging'])) {
                    $this->convertFileToHtml($path, $projectId, $source->id);
                } else {

                    $htmlContent = $this->convertFileToHtmlLocally($path, $projectId);
                    event(new ConversionCompleted($projectId, $source->id));
                }

            } else {
                // Handle other file types or throw an error
                return response()->json(['error' => 'Unsupported file type'], 400);
            }
        }

        return response()->json([
            'newDocument' => [
                'id' => $source->id,
                'name' => $source->name,
                'type' => 'text',
                'user' => auth()->user()->name,
                'content' => $htmlContent ?? '',
                'converted' => File::exists($htmlOutputPath),

            ],
        ]);

    }

    private function fetchAndTransformSources($projectId)
    {
        $sources = Source::where('project_id', $projectId)->with('variables')->get();

        return $sources->map(function ($source) {
            return [
                'id' => $source->id,
                'name' => $source->name,
                'type' => $source->type,
                'user' => $source->creatingUser->name,
                'userPicture' => $source->creatingUser->profile_photo_url,
                'date' => $source->created_at->toDateString(),
                'variables' => $source->transformVariables(),
                'converted' => File::exists($source->converted->path),
            ];
        });
    }

    /**
     * Redirects the user to the coding page for the specified source
     *
     * @return \Illuminate\Http\RedirectResponse|never
     */
    public function goAndCode(Request $request, $sourceId)
    {

        $source = Source::findOrFail($sourceId);
        if (!Gate::allows('view', $source->project)) {
            return abort('Not allowed');
        }

        return to_route('coding.show', ['project' => $source->project_id, 'source' => $source]);
    }

    public function lockAndCode(Request $request, $sourceId)
    {

        try {
            // Find the source by its ID
            $source = Source::findOrFail($sourceId);

            // Find the "isLocked" variable for the source
            $isLockedVariable = Variable::where('source_id', $sourceId)
                ->where('name', 'isLocked')
                ->first();

            if ($isLockedVariable) {
                // If the "isLocked" variable exists, set it to true
                $isLockedVariable->boolean_value = true;
                $isLockedVariable->save();
            } else {
                // If the "isLocked" variable doesn't exist, create it and set it to true
                Variable::create([
                    'source_id' => $sourceId,
                    'name' => 'isLocked',
                    'type_of_variable' => 'boolean',
                    'boolean_value' => true,
                ]);
            }

            $audit = new Audit([
                'user_type' => 'App\Models\User',
                'user_id' => auth()->id(),
                'event' => 'content updated',
                'auditable_id' => $source->id,
                'auditable_type' => get_class($source),
                'new_values' => ['message' => $source->name . ' has been locked'],
            ]);

            $audit->save();

            return to_route('coding.show', ['project' => $source->project_id, 'source' => $source]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function unlock(Request $request, $sourceId)
    {

        try {
            // Find the source by its ID
            $source = Source::findOrFail($sourceId);

            // Find the "isLocked" variable for the source
            $isLockedVariable = Variable::where('source_id', $sourceId)
                ->where('name', 'isLocked')
                ->first();

            // If the "isLocked" variable exists, set it to false
            if ($isLockedVariable) {
                $isLockedVariable->boolean_value = false;
                $isLockedVariable->save();
            }
            $audit = new Audit([
                'user_type' => 'App\Models\User',
                'user_id' => auth()->id(),
                'event' => 'content updated',
                'auditable_id' => $source->id,
                'auditable_type' => get_class($source),
                'new_values' => ['message' => $source->name . ' has been unlocked'],
            ]);

            $audit->save();

            return response()->json(['success' => true, 'message' => 'Source unlocked successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function fetchDocument($id)
    {
        // Find the document
        $source = Source::with('variables')->findOrFail($id);  // This throws a 404 if the model is not found

        // It will automatically look for a method named 'update' in the policy for the Source model
        if (!Gate::allows('delete', $source)) {
            return response()->json(['success' => false, 'message' => 'Not allowed'], 403);
        }

        $content = file_get_contents($source->converted->path);

        // Complete source model with content
        $source->content = $content;

        // Add transformed variables
        $source->variables = $source->transformVariables();

        return response()->json($source);
    }

    /**
     * Updates the content of a document
     *
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $content = $request->input('content');
        $id = $request->input('id');

        $source = Source::findOrFail($id);  // This throws a 404 if the model is not found

        // It will automatically look for a method named 'update' in the policy for the Source model
        if (!Gate::allows('update', $source)) {
            return response()->json(['success' => false, 'message' => 'Not allowed'], 403);
        }

        try {

            if (!$source) {

                return response()->json(['success' => false, 'message' => 'Document not found']);
            }

            $htmlOutputPath = $source->converted->path;

            $source->modifying_user_id = auth()->id();
            $source->save();

            file_put_contents($htmlOutputPath, $content);
            $audit = new Audit([
                'user_type' => 'App\Models\User',
                'user_id' => auth()->id(),
                'event' => 'content updated',
                'auditable_id' => $source->id,
                'auditable_type' => get_class($source),
                'new_values' => ['message' => 'content of ' . $source->name . ' has been modified'],
            ]);

            $audit->save();

            return response()->json(['success' => true, 'message' => 'Document successfully saved']);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => 'An error occurred while saving']);
        }
    }

    /**
     * Converts a file to HTML
     *
     * @throws \Exception
     */
    private function convertFileToHtml($filePath, $projectId, $sourceId)
    {

        ConvertFileToHtmlJob::dispatch($filePath, $projectId, $sourceId)->onQueue('conversion');

        return response()->json(['message' => 'Conversion in progress']);

    }

    private function convertFileToHtmlLocally($filePath, $projectId)
    {
        // Define the output directory for the HTML file using the projectId
        $outputDirectory = storage_path('app/projects/' . $projectId . '/sources');
        if (!File::exists($outputDirectory)) {
            File::makeDirectory($outputDirectory, 0755, true);
        }

        $outputHtmlPath = $outputDirectory . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.html';

        // Define the path to the Python script
        $scriptPath = dirname(base_path(), 1) . '/service-convert-rtf-to-html/convert_rtf_to_html_locally.py';

        // Check if the Python script file exists
        if (!file_exists($scriptPath)) {
            throw new \Exception('Python script not found at: ' . $scriptPath);
        }

        try {
            // Build the command to execute the Python script
            $command = escapeshellcmd('python3 ' . $scriptPath) . ' ' . escapeshellarg($filePath) . ' ' . escapeshellarg($outputDirectory);

            // Execute the command
            $output = shell_exec($command);

            // Check if the output file was created
            if (file_exists($outputHtmlPath)) {
                return file_get_contents($outputHtmlPath);
            } else {
                throw new \Exception('Conversion failed. Script output: ' . $output);
            }
        } catch (\Exception $e) {
            throw new \Exception('Script execution failed: ' . $e->getMessage());
        }
    }

    public function rename(Request $request, $sourceId)
    {
        // Validate the new name
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Find the source by ID
        $source = Source::findOrFail($sourceId);

        // Check if the user is authorized to update the source
        if (!Gate::allows('update', $source)) {
            return response()->json(['success' => false, 'message' => 'Not authorized to rename this source'], 403);
        }

        try {
            // Update the source name
            $source->name = $request->input('name');
            $source->save();

            // Log the action
            $audit = new Audit([
                'user_type' => 'App\Models\User',
                'user_id' => auth()->id(),
                'event' => 'updated',
                'auditable_id' => $source->id,
                'auditable_type' => get_class($source),
                'new_values' => ['name' => $source->name],
            ]);
            $audit->save();

            return response()->json(['success' => true, 'message' => 'Source renamed successfully', 'source' => $source]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while renaming the source: ' . $e->getMessage()]);
        }
    }

    public function convertTxtToHtml($txtFilePath, $projectId)
    {
        // Define the output directory for the HTML file using the projectId
        $outputDirectory = storage_path('app/projects/' . $projectId . '/sources');
        if (!File::exists($outputDirectory)) {
            File::makeDirectory($outputDirectory, 0755, true);
        }
        $outputHtmlPath = $outputDirectory . '/' . pathinfo($txtFilePath, PATHINFO_FILENAME) . '.html';
        // Read the contents of the TXT file
        $txtContent = file_get_contents($txtFilePath);

        if ($txtContent === false) {
            // Handle error if file could not be read
            return 'Error reading file.';
        }

        // Convert special characters to HTML entities to prevent HTML injection
        $htmlContent = htmlspecialchars($txtContent);

        // Convert line breaks to <br> tags
        $htmlContent = nl2br($htmlContent);
        file_put_contents($outputHtmlPath, $htmlContent);

        return $htmlContent;
    }

    public function destroy($id)
    {

        // Find the document
        $source = Source::findOrFail($id);  // This throws a 404 if the model is not found

        // It will automatically look for a method named 'delete' in the policy for the Source model
        if (!Gate::allows('delete', $source)) {
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
            if ($source->converted->path) {
                // Assuming it's an absolute path from the system's root
                if (File::exists($source->converted->path)) {
                    File::delete($source->converted->path);

                }
            }
            SourceStatus::where('source_id', $source->id)->delete();

            // Delete the database record
            $source->delete();

            return response()->json(['success' => true, 'message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * @throws \Exception
     */
    public function getHtmlContent(string $path, mixed $projectId, Source $source, bool $fromAdminPanel = false): string|false
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
            $newPath = Str::replaceLast('.rtf', '.html', $source->upload_path);
            $source->converted->path = $newPath;
            $source->save();
        }

        return $htmlContent ?? true;
    }

    public function retryConversion(Project $project, Source $source)
    {
        $this->getHtmlContent($source->upload_path, $project->id, $source, true);

        return response()->json(['message' => 'Conversion in progress']);
    }

    /**
     * XXX: This is a proof of concept! It will be removed, once we have our full plugin spec!
     * Requests a transcription from the external example service.
     */
    public function transcribe(Request $request)
    {
        ray('Transcription request received')->green();

        $request->validate([
            'file' => 'required|file|extensions:mpeg,mpga,mp3,wav,aac,ogg,m4a,flac',
            'model' => 'required|string',
            'language' => 'required|string',
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        $model = $request->input('model');
        $language = $request->input('language');

        DB::beginTransaction();

        try {
            // Generate a unique filename and store the file
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $projectId = $request->input('project_id');
            $path = $file->storeAs('uploads/audio', $filename);
            ray($filename)->label('Stored File Name');
            ray($path)->label('Stored File Path');

            // Create the source record
            $source = Source::create([
                'name' => $file->getClientOriginalName(),
                'creating_user_id' => $user->id,
                'project_id' => $projectId,
                'type' => 'text',
                'upload_path' => $path,
            ]);
            ray($source)->label('Source Record Created');

            // Send the file to the aTrain service
            $response = Http::attach('uploaded', file_get_contents(storage_path('app/' . $path)), $filename)
                ->post(config('app.atrain'));
                /*
                [
                    'model' => $model,
                    'language' => $language,
                    'speaker_detection' => $request->input('speaker_detection', false),
                    'num_speakers' => $request->input('num_speakers'),
                ]
                */
            ray($response)->label('aTrain Service Response');

            if ($response->successful()) {
                $fileId = $response->json('file_id');
                ray($fileId)->label('aTrain File ID');

                // Save the file_id in the variables table
                Variable::create([
                    'source_id' => $source->id,
                    'name' => 'atrain_file_id',
                    'type_of_variable' => 'string',
                    'text_value' => $fileId,
                ]);
                ray('File ID Saved to Variables Table');

                // Create the source status record
                SourceStatus::create([
                    'source_id' => $source->id,
                    'status' => 'converting',
                ]);
                ray('Source Status Set to Converting')->label('Source Status');

                DB::commit();

                TranscriptionJob::dispatch($fileId, $projectId, $source->id)->delay(now()->addSeconds(10))->onQueue('conversion');

                return response()->json(['message' => 'File uploaded and processing started', 'file_id' => $fileId], 200);
            } else {
                throw new \Exception('Failed to upload file to aTrain service');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ray($e)->label('Error Occurred')->red();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
