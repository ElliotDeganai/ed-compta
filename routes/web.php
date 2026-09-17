<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RecurringItemController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SimulationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('welcome');

Route::get('informations/{page}', [PageController::class, 'show'])->name('pages.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tableau-de-bord', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('dashboard');

    Route::post('rapprochement', [DashboardController::class, 'reconcile'])
        ->middleware('permission:manage transactions')
        ->name('reconciliation.store');

    Route::get('mouvements', [TransactionController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('transactions.index');

    Route::middleware('permission:manage transactions')->group(function () {
        Route::post('mouvements', [TransactionController::class, 'store'])->name('transactions.store');
        Route::put('mouvements/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('mouvements/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::post('mouvements/{transaction}/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    });

    Route::get('documents/{document}', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('recurrents', [RecurringItemController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('recurring.index');

    Route::get('simulation', [SimulationController::class, 'index'])->middleware('permission:view dashboard')->name('simulation.index');

    Route::middleware('permission:manage transactions')->group(function () { Route::post('simulation', [SimulationController::class, 'store'])->name('simulation.store'); Route::put('simulation/{simulation}', [SimulationController::class, 'update'])->name('simulation.update'); Route::post('simulation/{simulation}/bascule', [SimulationController::class, 'toggle'])->name('simulation.toggle'); Route::delete('simulation/{simulation}', [SimulationController::class, 'destroy'])->name('simulation.destroy'); Route::post('simulation/convertir', [SimulationController::class, 'convert'])->name('simulation.convert'); Route::post('simulation/vider', [SimulationController::class, 'clear'])->name('simulation.clear'); });

    Route::middleware('permission:manage recurring')->group(function () {
        Route::post('recurrents', [RecurringItemController::class, 'store'])->name('recurring.store');
        Route::put('recurrents/{recurring}', [RecurringItemController::class, 'update'])->name('recurring.update');
        Route::delete('recurrents/{recurring}', [RecurringItemController::class, 'destroy'])->name('recurring.destroy');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('permission:manage categories')->group(function () {
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        });

        Route::middleware('permission:manage settings')->group(function () {
            Route::get('contenu', [ContentController::class, 'index'])->name('content.index');

            // {page:id} explicite : le modele Page utilise le slug comme cle de
            // route pour les URL publiques, ce qui ferait chercher ici une page
            // dont le slug vaut « 5 » — et renverrait un 404.
            Route::post('contenu/{page:id}', [ContentController::class, 'updatePage'])->name('pages.update');

            Route::get('parametres', [SettingController::class, 'edit'])->name('settings.edit');

            Route::post('parametres', [SettingController::class, 'update'])->name('settings.update');

            Route::post('parametres/reinitialiser-visuel', [SettingController::class, 'resetAsset'])->name('settings.reset');
        });

        Route::middleware('permission:manage users')->group(function () {
            Route::get('utilisateurs', [UserController::class, 'index'])->name('users.index');
            Route::post('utilisateurs', [UserController::class, 'store'])->name('users.store');
            Route::put('utilisateurs/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('utilisateurs/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });
});

require __DIR__.'/auth.php';
