<?php

namespace Doefom\StatamicExport\Http\Controllers;

use Doefom\StatamicExport\Enums\FileType;
use Doefom\StatamicExport\Exports\EntriesExport;
use Doefom\StatamicExport\Http\Requests\ExportRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Fields\Blueprint;

class ExportController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $this->authorize('access export utility');

        $collections = Collection::all()->sortBy('title');
        $fieldHandles = $collections->mapWithKeys(function ($collection) {
            return [$collection->handle() => $this->getFieldHandles($collection)];
        });

        return view('statamic-export::export.utility', [
            'collections' => $collections->values(),
            'fieldHandles' => $fieldHandles,
            'fileTypes' => FileType::all(),
        ]);
    }

    public function export(ExportRequest $request)
    {
        $this->authorize('access export utility');

        // Get the request parameters
        $collectionHandle = $request->input('collection_handle');
        $fileType = $request->input('file_type', 'xlsx');
        $excludedFields = $request->input('excluded_fields', []);
        $includeHeaders = $request->input('headers', true);

        // Query the entries by collection
        $items = Entry::query()
            ->where('collection', $collectionHandle)
            ->get();

        // Download the export
        return Excel::download(new EntriesExport($items, [
            'headers' => $includeHeaders,
            'excluded_fields' => $excludedFields,
        ]), "$collectionHandle.$fileType");
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
