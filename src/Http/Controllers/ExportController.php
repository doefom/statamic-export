<?php

namespace Doefom\StatamicExport\Http\Controllers;

use Doefom\StatamicExport\Enums\FileType;
use Doefom\StatamicExport\Exports\EntriesExport;
use Doefom\StatamicExport\Http\Requests\ExportRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Statamic\Facades\Blueprint as BlueprintFacade;
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
        $excludedFieldsOptions = $fieldHandles;
        $excludedFieldsOptions['users'] = $this->getUserFieldHandles();

        $values = [];
        $blueprint = BlueprintFacade::makeFromFields($this->editBlueprint($collections));
        $fields = $blueprint ->fields()->addValues($values)->preProcess();

        return Inertia::render('statamic-export::export.utility', [
            'title' => 'Export',
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
            'excludedFieldsOptions' => $excludedFieldsOptions
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

    protected function editBlueprint($collections)
    {
        $fileTypes = FileType::all();
        
        foreach ($collections as $collection) {
            $collectionOptions[] = [
                'key' => $collection->handle(),
                'value' => $collection->title()
            ];
        }

        foreach ($fileTypes as $type) {
            $fileTypeOptions[] = [
                'key' => $type,
                'value' => strtoupper($type)
            ];
        }

        return [
            'type' => [
                'type' => 'button_group', 
                'display' => 'Export Type',
                'instructions' => 'Choose what you want to export.',
                'options' => [
                    [
                        'key' => 'collections',
                        'value' => 'Collections'
                    ],
                    [
                        'key' => 'users',
                        'value' => 'Users'
                    ]
                ],
                'default' => 'collections'
            ],
            'collection_handle' => [
                'type' => 'select', 
                'display' =>  'Collection',
                'instructions' => 'Select the collection you want to export.',
                'placeholder' => __('Choose...'),
                'options' => $collectionOptions,
                'clearable' => true,
                'if' => ['type' => 'equals collections']
            ],
            'file_type' => [
                'type' => 'select', 
                'display' =>  'File Type',
                'instructions' => 'Select the file type for the export.',
                'placeholder' => __('Choose...'),
                'options' => $fileTypeOptions,
                'clearable' => true,
                'default' => 'xlsx'
            ],
            'excluded_fields' => [
                'type' => 'select', 
                'display' =>  'Excluded Fields',
                'instructions' => "Select the fields you want to exclude from the export. If you don't select any fields, all fields will be included.",
                'placeholder' => __('Choose...'),
                'options' => [],
                'multiple' => true,
            ],
            'headers' => [
                'type' => 'toggle',
                'display' => 'Include Headers',
                'instructions' => 'Add a header row as the first row of your exported file which contains the display names of the field for each column.',
                'default' => true
            ],
        ];
    }
}
