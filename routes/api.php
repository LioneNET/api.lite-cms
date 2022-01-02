<?php

use App\Http\Controllers\BannersController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\TopicsController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin'], function() {

    Route::group(['prefix' => 'menu'], function() {
        Route::get('/', [MenuController::class, 'execute']);
        Route::post('/create', [MenuController::class, 'apiCreateMenu']);
        Route::post('/add', [MenuController::class, 'apiAddMenu']);
        Route::post('/update', [MenuController::class, 'apiUpdateMenu']);
        Route::post('/delete', [MenuController::class, 'apiDeleteMenu']);
        Route::post('/change-position', [MenuController::class, 'apiChangePositionMenu']);
    });

    Route::group(['prefix' => 'category'], function() {
        Route::get('/', [CategoryController::class, 'execute']);
        Route::post('/create', [CategoryController::class, 'apiCreateCategory']);
        Route::post('/add', [CategoryController::class, 'apiAddCategory']);
        Route::post('/update', [CategoryController::class, 'apiUpdateCategory']);
        Route::post('/delete', [CategoryController::class, 'apiDeleteCategory']);
    });

    Route::group(['prefix' => 'topic'], function() {
        Route::get('/', [TopicsController::class, 'execute']);
        Route::post('/create', [TopicsController::class, 'apiCreateTopic']);
        Route::post('/update', [TopicsController::class, 'apiUpdateTopic']);
        Route::post('/delete', [TopicsController::class, 'apiDeleteTopic']);
    });

    Route::group(['prefix' => 'section'], function() {
        Route::get('/', [SectionController::class, 'execute']);
        Route::post('/create', [SectionController::class, 'apiCreate']);
        Route::post('/update', [SectionController::class, 'apiUpdate']);
        Route::post('/delete', [SectionController::class, 'apiDelete']);
    });

    Route::group(['prefix' => 'file'], function() {
        Route::get('/', [FileManagerController::class, 'execute']);
        Route::post('/upload', [FileManagerController::class, 'apiUpload']);
        Route::post('/delete', [FileManagerController::class, 'apiDelete']);
        Route::post('/dir-create', [FileManagerController::class, 'create_directory']);
        Route::post('/dir-rename', [FileManagerController::class, 'rename_directory']);
    });

    Route::group(['prefix' => 'banner'], function() {
        Route::get('/', [BannersController::class, 'execute']);
        Route::post('/create', [BannersController::class, 'apiCreate']);
        Route::post('/update', [BannersController::class, 'apiUpdate']);
        Route::post('/delete', [BannersController::class, 'apiDelete']);
    });

    Route::group(['prefix' => 'position'], function() {
        Route::get('/', [PositionController::class, 'getPositions']);
        Route::get('/get-pos-items', [PositionController::class, 'getPosItems']);
        Route::post('/change', [PositionController::class, 'positionChange']);
    });
});