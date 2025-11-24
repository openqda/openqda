<?php

use App\Http\Controllers\AdditionalTeamController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\AuditsController;
use App\Http\Controllers\CodebookCodesController;
use App\Http\Controllers\CodebookController;
use App\Http\Controllers\CodingController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SelectionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserNavigationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('LandingPage', [
        'title' => config('app.name'),
        'background' => asset(config('app.background')),
        'slogan' => config('app.slogan'),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'description' => config('app.description'),
        'bgtl' => config('app.bgtl'),
        'bgtr' => config('app.bgtr'),
        'bgbr' => config('app.bgbr'),
        'bgbl' => config('app.bgbl'),
    ]);
})->name('welcome');

$staticMarkdownRoute = function ($path, $markdownFile, $routeName) {
    Route::get($path, function () use ($markdownFile) {
        return Inertia::render('RenderHtml', [
            'background' => asset(config('app.background')),
            'html' => Str::of(file_get_contents(resource_path("markdown/{$markdownFile}")))->markdown(),
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'bgtl' => config('app.bgtl'),
            'bgtr' => config('app.bgtr'),
            'bgbr' => config('app.bgbr'),
            'bgbl' => config('app.bgbl'),
        ]);
    })->name($routeName);
};

// Register static markdown pages
$staticMarkdownRoute('/imprint', 'imprint.md', 'imprint');
$staticMarkdownRoute('/faq', 'faq.md', 'faq');
$staticMarkdownRoute('/privacy', 'privacy.md', 'privacy');
$staticMarkdownRoute('/terms', 'terms.md', 'terms');
$staticMarkdownRoute('/license', 'license.md', 'license');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::post('/projects/{project}/sources/{source}/gethtmlcontent', [SourceController::class, 'retryConversion'])->name('source.convert');
    Route::post('/projects/{project}/sources/{source}/retrytranscription', [SourceController::class, 'retryTranscription'])->name('source.convert');

    /**
     * Projects
     */
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('projects.dashboard');
    Route::post('/projects/new', [ProjectController::class, 'store'])->name('project.store');
    Route::get('/projects/{project}/overview', [ProjectController::class, 'show'])->name('project.show');
    Route::get('/projects/{project}/team', [ProjectController::class, 'getTeamData'])->name('project.team-data');
    Route::post('/projects/update/{project}', [ProjectController::class, 'updateProjectAttributes'])->name('project.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('project.destroy');
    Route::post('/projects/{project}/export', [ExportController::class, 'run'])->name('projects.export');

    // For homepage/user audits
    Route::get('/audits', [AuditsController::class, 'index']);

    // For project-specific audits
    Route::get('/audits/{project}', [AuditsController::class, 'projectAudits']);

    /**
     * Coding
     */
    Route::get('/projects/{project}/codes', [CodingController::class, 'show'])->name('coding.show');
    Route::post('/projects/{project}/codes', [CodingController::class, 'store'])->name('coding.store');
    Route::patch('/projects/{project}/codes/{code}', [CodingController::class, 'updateAttribute'])->name('coding.update-attribute');
    Route::post('/projects/{project}/sources/{source}/codes/{code}/selections/{selection}/change-code', [SelectionController::class, 'changeCode'])->name('selection.change-code');
    Route::post('/projects/{project}/sources/{source}/codes/{code}/remove-parent', [CodingController::class, 'removeParent'])->name('coding.remove-parent');
    Route::post('/projects/{project}/sources/{source}/codes/{code}/up-hierarchy', [CodingController::class, 'upHierarchy'])->name('coding.up-hierarchy');
    Route::delete('/projects/{project}/sources/{source}/codes/{code}', [CodingController::class, 'destroy'])->name('coding.destroy');
    Route::post('/projects/{project}/sources/{source}/codes/{code}/description', [CodingController::class, 'updateDescription'])->name('coding.update-description');

    /**
     * Codebooks
     */
    Route::patch('/projects/{project}/codebooks/{codebook}', [CodebookController::class, 'update'])->name('codebook.update');
    Route::delete('/projects/{project}/codebooks/{codebook}', [CodebookController::class, 'destroy'])->name('codebook.destroy');
    Route::post('/projects/{project}/codebooks', [CodebookController::class, 'store'])->name('codebook.store');
    Route::post('/projects/{project}/codebooks/import', [CodebookCodesController::class, 'import'])->name('codebook-codes.import');
    Route::get('/projects/{project}/codebooks/export/{codebook}', [CodebookCodesController::class, 'export'])->name('codebook-codes.export');
    Route::patch('/projects/{project}/codebooks/{codebook}/code-order', [CodebookCodesController::class, 'updateCodeOrder'])->name('codebook-codes.update-order');

    Route::get('/api/codebooks/public', [CodebookController::class, 'getPublicCodebooks'])->name('api.codebooks.public');
    Route::get('/api/codebooks/search', [CodebookController::class, 'searchPublicCodebooks'])->name('api.codebooks.search');
    Route::get('/api/codebooks/{codebook}/codes', [CodebookController::class, 'getCodebookWithCodes'])->name('api.codebooks.codes');

    /**
     * Analysis
     */
    Route::get('/projects/{project}/analysis', [AnalysisController::class, 'show'])->name('analysis.show');
    Route::get('/projects/{project}/analysis', [AnalysisController::class, 'show'])->name('analysis.show');

    /**
     * Selection
     */
    Route::post('/projects/{project}/sources/{source}/codes/{code}', [SelectionController::class, 'store'])->name('selection.store');
    Route::delete('/projects/{project}/sources/{source}/codes/{code}/selections/{selection}', [SelectionController::class, 'destroy'])->name('selection.destroy');
    Route::delete('/projects/{project}/sources/{source}/selections/{selection}', [SelectionController::class, 'destroyOrphan'])->name('selection.destroyOrphan');

    /**
     * Source
     */
    Route::get('/projects/{project}/preparation', [SourceController::class, 'index'])->name('source.index');
    Route::delete('/files/{id}', [SourceController::class, 'destroy']);
    Route::post('/files/upload', [SourceController::class, 'store'])->name('source.store');
    Route::post('/files/transcribe', [SourceController::class, 'transcribe'])->name('source.transcribe');
    Route::get('/files/{id}', [SourceController::class, 'fetchDocument']);
    Route::post('/source/update', [SourceController::class, 'update'])->name('source.update');
    Route::post('/sources/{source}', [SourceController::class, 'rename'])->name('source.rename');
    Route::post('/sources/{sourceId}/unlock', [SourceController::class, 'unlock'])->name('source.unlock');
    Route::match(['get', 'post'], '/sources/{sourceId}/code', [SourceController::class, 'lockAndCode'])
        ->name('source.code');
    Route::post('/sources/{sourceId}/linenumbers', [SourceController::class, 'saveLineNumbers'])->name('sources.linenumbers');
    Route::post('/sources/{sourceId}/download', [SourceController::class, 'download'])->name('sources.download');

    /**
     * Others
     */
    Route::post('/user/navigation', [UserNavigationController::class, 'update']);
    Route::post('/user/feedback', [UserNavigationController::class, 'feedback']);
    Route::post('/user/consent', [UserController::class, 'consentLegal'])->name('user.consent');
    Route::post('/user/research/request', [UserController::class, 'requestResearch'])->name('user.research.request');
    Route::post('/user/research/confirm', [UserController::class, 'confirmResearch'])->name('user.research.confirm');
    Route::post('/user/research/withdraw', [UserController::class, 'withdrawResearch'])->name('user.research.withdraw');
    Route::get('/user/{user}/owned-teams', [UserController::class, 'getOwnedTeams'])->name('user.owned-teams');

    /**
     * Teams
     */
    Route::post('/teams/change-owner', [AdditionalTeamController::class, 'makeOwner'])->name('team-members.make-owner');

    /**
     * Settings
     */
    Route::resource('settings', SettingsController::class);
    Route::patch('settings/{setting}/value', [SettingsController::class, 'updateValue'])->name('settings.update-value');

});
