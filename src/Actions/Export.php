<?php

namespace Doefom\StatamicExport\Actions;

use Doefom\StatamicExport\Enums\FileType;
use Doefom\StatamicExport\Exports\EntriesExport;
use Maatwebsite\Excel\Facades\Excel;
use Statamic\Actions\Action;
use Statamic\Entries\Entry;
use Statamic\Forms\Submission;
use Statamic\Support\Arr;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Export extends Action
{

    // TODO: Support form submissions as well

    public function __construct()
    {
        $fileTypeOptions = collect(FileType::all())->mapWithKeys(function ($fileType) {
            return [$fileType => strtoupper($fileType)];
        })->all();

        $this->fields = [
            'file_type' => [
                'type' => 'select',
                'options' => $fileTypeOptions,
                'default' => FileType::XLSX->value,
                'instructions' => 'Select the file type for the export.',
            ],
            'excluded_fields' => [
                'type' => 'taggable',
                'instructions' => "Add the fields you want to exclude from the export by entering the field handle. For example: `title`, `content`, etc. If you don't add any, all fields will be included.",
            ],
            'headers' => [
                'type' => 'toggle',
                'default' => true,
                'instructions' => 'Add a header row as the first row of your exported file which contains the display names of the field for each column.',
            ],
        ];
    }

    public function download($items, $values): BinaryFileResponse|bool
    {
        $collectionHandle = $items->first()->collection()->handle();
        $fileType = Arr::get($values, 'file_type', 'xlsx');

        return Excel::download(new EntriesExport($items, $values), "$collectionHandle.$fileType");
    }

    public function visibleTo($item)
    {
        return $item instanceof Entry;
    }

    public function buttonText()
    {
        return 'Export entries';
    }

}
