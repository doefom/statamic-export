<?php

namespace Doefom\StatamicExport;

use Doefom\StatamicExport\Actions\Export;
use Doefom\StatamicExport\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon(): void
    {
        $this->publishes([
            __DIR__.'/../config/statamic/export.php' => config_path('statamic/export.php'),
        ], 'statamic-export-config');

        // Register the export action
        Export::register();

        // Register the export utility
        Utility::extend(function () {
            Utility::register('export')
                ->action([ExportController::class, 'index'])
                ->icon('download')
                ->description('Export all entries of a collection into the format of your choosing. Make it Excel, CSV and more.');
        });

        $this->registerActionRoutes(function () {
            // Full route name: statamic.statamic-export.export
            Route::post('export', [ExportController::class, 'export'])->name('statamic-export.export');
        });

    }
}
