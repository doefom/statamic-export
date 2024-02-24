<?php

namespace Doefom\StatamicExport;

use Doefom\StatamicExport\Actions\Export;
use Doefom\StatamicExport\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {

        // Register the export action
        Export::register();

        // Register the export utility
        Utility::extend(function () {
            Utility::register('export')
                ->view('statamic-export::export.utility')
                ->icon('download')
                ->description('Export all entries of a collection into the format of your choosing. Make it Excel, CSV and more.');
        });

        $this->registerActionRoutes(function () {
            // Full route name: statamic.statamic-export.export
            Route::post('export', [ExportController::class, 'export'])->name('statamic-export.export');
        });

    }
}
