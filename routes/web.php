<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/templates');

/* Templates — the format designer */
Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
Route::post('/companies/{company}/templates', [TemplateController::class, 'store'])->name('templates.store');
Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
Route::post('/templates/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('templates.duplicate');
Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');

/* Segments */
Route::post('/templates/{template}/segments', [TemplateController::class, 'addSegment'])->name('segments.add');
Route::put('/templates/{template}/segments', [TemplateController::class, 'saveSegments'])->name('segments.save');
Route::patch('/segments/{segment}/move/{direction}', [TemplateController::class, 'moveSegment'])
    ->whereIn('direction', ['up', 'down'])->name('segments.move');
Route::delete('/segments/{segment}', [TemplateController::class, 'deleteSegment'])->name('segments.delete');

/* Project category */
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/companies/{company}/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('/companies/{company}/categories', [CategoryController::class, 'saveAll'])->name('categories.saveAll');
Route::post('/companies/{company}/categories/bulk', [CategoryController::class, 'bulk'])->name('categories.bulk');
Route::post('/companies/{company}/categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

/* Import & match */
Route::get('/import', [ImportController::class, 'index'])->name('import.index');
Route::post('/import/upload', [ImportController::class, 'upload'])->name('import.upload');
Route::post('/import/discard', [ImportController::class, 'discard'])->name('import.discard');
Route::match(['get', 'post'], '/import/sample/{company}', [ImportController::class, 'sample'])->name('import.sample');

/* Companies */
Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

/* Export everything as JSON */
Route::get('/export', ExportController::class)->name('export');
