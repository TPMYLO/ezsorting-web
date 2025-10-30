<?php

use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SortingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Google Drive Routes
    Route::prefix('google')->name('google.')->group(function () {
        Route::get('/auth', [GoogleDriveController::class, 'redirectToGoogle'])->name('auth');
        Route::get('/callback', [GoogleDriveController::class, 'handleGoogleCallback'])->name('callback');
        Route::get('/folders', [GoogleDriveController::class, 'listFolders'])->name('folders.list');
        Route::get('/images', [GoogleDriveController::class, 'listImages'])->name('images.list');
        Route::post('/folders', [GoogleDriveController::class, 'createFolder'])->name('folders.create');
        Route::post('/files/move', [GoogleDriveController::class, 'moveFile'])->name('files.move');
        Route::get('/files/{fileId}', [GoogleDriveController::class, 'getFile'])->name('files.get');
    });

    // Sorting Routes
    Route::prefix('sorting')->name('sorting.')->group(function () {
        Route::get('/', [SortingController::class, 'index'])->name('index');
        Route::post('/session', [SortingController::class, 'createSession'])->name('session.create');
        Route::post('/session/folder', [SortingController::class, 'addDestinationFolder'])->name('session.folder.add');
        Route::delete('/session/folder', [SortingController::class, 'removeDestinationFolder'])->name('session.folder.remove');
        Route::post('/session/start', [SortingController::class, 'startSorting'])->name('session.start');
        Route::post('/session/sort', [SortingController::class, 'sortImage'])->name('session.sort');
        Route::post('/session/skip', [SortingController::class, 'skipImage'])->name('session.skip');
        Route::delete('/session', [SortingController::class, 'resetSession'])->name('session.reset');
    });
});

require __DIR__.'/auth.php';
