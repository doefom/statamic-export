<?php

namespace Doefom\StatamicExport\Http\Controllers;

use Doefom\StatamicExport\Enums\FileType;
use Doefom\StatamicExport\Exports\EntriesExport;
use Doefom\StatamicExport\Http\Requests\ExportRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
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

        $userFieldHandles = $this->getUserFieldHandles();

        return view('statamic-export::export.utility', [
            'collections' => $collections->values(),
            'fieldHandles' => $fieldHandles,
            'userFieldHandles' => $userFieldHandles,
            'fileTypes' => FileType::all(),
        ]);
    }

    public function export(ExportRequest $request)
    {
        $this->authorize('access export utility');

        $fileType = $request->input('file_type', 'xlsx');
        $excludedFields = $request->input('excluded_fields', []);
        $includeHeaders = $request->input('headers', true);

        if ($request->input('type') === 'users') {
            $items = User::query()->lazy();
            $filename = 'users';
        } else {
            $collectionHandle = $request->input('collection_handle');
            $items = Entry::query()
                ->where('collection', $collectionHandle)
                ->lazy();
            $filename = $collectionHandle;
        }

        $exporter = new EntriesExport($items, [
            'headers' => $includeHeaders,
            'excluded_fields' => $excludedFields,
        ]);

        return Excel::download($exporter, "$filename.$fileType");
    }

    /**
     * Get all unique field handles for a collection.
     *
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

    /**
     * Get all unique field handles for users.
     *
     * @return array
     */
    private function getUserFieldHandles()
    {
        return User::blueprint()
            ->fields()
            ->all()
            ->keys()
            ->sort()
            ->values()
            ->all();
    }
}
