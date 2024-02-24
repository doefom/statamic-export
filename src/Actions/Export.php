<?php

namespace Doefom\StatamicExport\Actions;

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

    protected $fields = [
        'file_type' => [
            'type' => 'select',
            'options' => [
                'xlsx' => 'XLSX',
                'csv' => 'CSV',
                'tsv' => 'TSV',
                'ods' => 'ODS',
                'xls' => 'XLS',
                'html' => 'HTML',
            ],
            'default' => 'xlsx',
            'instructions' => 'Select the file type for the export.',
        ],
    ];

    public function download($items, $values): BinaryFileResponse|bool
    {
        $collectionHandle = $items->first()->collection()->handle();
        $fileType = Arr::get($values, 'file_type', 'xlsx');

        return Excel::download(new EntriesExport($items), "$collectionHandle.$fileType");
    }

    public function visibleTo($item)
    {
        return $item instanceof Entry;
    }

}
