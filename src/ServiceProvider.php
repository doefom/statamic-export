<?php

namespace Doefom\StatamicExport;

use Doefom\StatamicExport\Actions\Export;
use Doefom\StatamicExport\Enums\FileType;
use Doefom\StatamicExport\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\Collection;
use Statamic\Facades\Utility;
use Statamic\Fields\Blueprint;
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

        // Register the export action
        Export::register();

        // Register the export utility
        Utility::extend(function () {

            $collections = Collection::all()->sortBy('title');
            $fieldHandles = $collections->mapWithKeys(function ($collection) {
                return [$collection->handle() => $this->getFieldHandles($collection)];
            });

            Utility::register('export')
                ->view('statamic-export::export.utility', function ($request) use ($collections, $fieldHandles) {
                    return [
                        'collections' => $collections->values(),
                        'fieldHandles' => $fieldHandles,
                        'fileTypes' => FileType::all(),
                    ];
                })
                ->icon('download')
                ->description('Export all entries of a collection into the format of your choosing. Make it Excel, CSV and more.');
        });

        $this->registerActionRoutes(function () {
            // Full route name: statamic.statamic-export.export
            Route::post('export', [ExportController::class, 'export'])->name('statamic-export.export');
        });

    }

    /**
     * Get all unique field handles for a collection.
     * @param \Statamic\Entries\Collection $collection
     * @return mixed
     */
    private function getFieldHandles(\Statamic\Entries\Collection $collection)
    {
        return $collection->entryBlueprints()->map(function (Blueprint $blueprint): array {
            return $blueprint->fields()->all()->keys()->toArray();
        })
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
