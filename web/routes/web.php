<?php

use App\Http\Controllers\AdditionalTeamController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\CodebookCodesController;
use App\Http\Controllers\CodebookController;
use App\Http\Controllers\CodingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SelectionController;
use App\Http\Controllers\SourceController;
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

Route::get('/faq', function () {
    return Inertia::render('RenderHtml', [
        'background' => asset(config('app.background')),
        'html' => Str::of(file_get_contents(resource_path('markdown/faq.md')))->markdown(),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'bgtl' => config('app.bgtl'),
        'bgtr' => config('app.bgtr'),
        'bgbr' => config('app.bgbr'),
        'bgbl' => config('app.bgbl'),

    ]);
})->name('faq');

Route::get('/imprint', function () {
    return Inertia::render('RenderHtml', [
        'background' => asset(config('app.background')),
        'html' => Str::of(file_get_contents(resource_path('markdown/imprint.md')))->markdown(),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'bgtl' => config('app.bgtl'),
        'bgtr' => config('app.bgtr'),
        'bgbr' => config('app.bgbr'),
        'bgbl' => config('app.bgbl'),

    ]);
})->name('imprint');

Route::get('/license', function () {
    return Inertia::render('RenderHtml', [
        'background' => asset(config('app.background')),
        'html' => Str::of(file_get_contents(resource_path('markdown/license.md')))->markdown(),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'bgtl' => config('app.bgtl'),
        'bgtr' => config('app.bgtr'),
        'bgbr' => config('app.bgbr'),
        'bgbl' => config('app.bgbl'),

    ]);
})->name('license');

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
    Route::post('/projects/update/{project}', [ProjectController::class, 'updateProjectAttributes'])->name('project.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('project.destroy');
    Route::get('/projects/{project}/load-more-audits', [ProjectController::class, 'loadMoreAudits']);
    Route::get('/projects/load-more-audits', [ProjectController::class, 'loadAllProjectsAudits']);

    /**
     * Coding
     */
    Route::get('/projects/{project}/codes', [CodingController::class, 'show'])->name('coding.show');
    Route::post('/projects/{project}/codes', [CodingController::class, 'store'])->name('coding.store');
    Route::post('/projects/{project}/codes/{code}/update-color', [CodingController::class, 'updateColor'])->name('coding.update-color');
    Route::post('/projects/{project}/codes/{code}/update-title', [CodingController::class, 'updateTitle'])->name('coding.update-title');
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
    Route::get('/projects/{project}/codebooks/export/{id}', [CodebookCodesController::class, 'export'])->name('codebook-codes.export');
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
    Route::post('/sources/{sourceId}/lock-and-code', [SourceController::class, 'lockAndCode'])->name('source.lock');
    Route::post('/sources/{sourceId}/unlock', [SourceController::class, 'unlock'])->name('source.unlock');
    Route::get('/sources/{source}/', [SourceController::class, 'goAndCode'])->name('source.go-and-code');
    Route::post('/sources/{sourceId}/linenumbers', [SourceController::class, 'saveLineNumbers'])->name('sources.linenumbers');
    Route::post('/sources/{sourceId}/download', [SourceController::class, 'download'])->name('sources.download');

    /**
     * Others
     */
    Route::post('/user/navigation', [UserNavigationController::class, 'update']);
    Route::post('/user/feedback', [UserNavigationController::class, 'feedback']);

    /**
     * Teams
     */
    Route::post('/teams/change-owner', [AdditionalTeamController::class, 'makeOwner'])->name('team-members.make-owner');

});
