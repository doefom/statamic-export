<?php

namespace Doefom\StatamicExport\Http\Controllers;

use Doefom\StatamicExport\Enums\FileType;
use Doefom\StatamicExport\Exports\EntriesExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Statamic\Facades\Entry;

class ExportController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function export(Request $request)
    {
        $this->authorize('access export utility');

        // Validate the file type using the enum
        $fileType = $request->input('file_type', 'xlsx');
        if (!FileType::isValid($fileType)) {
            throw ValidationException::withMessages(['file_type' => 'Invalid file type.']);
        }

        // Validate the collection handle
        $collectionHandle = $request->input('collection_handle');
        if (!$collectionHandle) {
            throw ValidationException::withMessages(['collection_handle' => 'Collection handle is required.']);
        }

        // Query the entries by collection
        $items = Entry::query()
            ->where('collection', $collectionHandle)
            ->get();

        // Download the export
        return Excel::download(new EntriesExport($items), "$collectionHandle.$fileType");
    }
}
