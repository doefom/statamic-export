<?php

namespace Doefom\StatamicExport\Enums;

use Statamic\Support\Arr;

enum FileType: string
{
    case XLSX = 'xlsx';
    case CSV = 'csv';
    case TSV = 'tsv';
    case ODS = 'ods';
    case XLS = 'xls';
    case HTML = 'html';

    /**
     * Check if a given value is a valid enum case
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }

    public static function all(): array
    {
        return Arr::map(self::cases(), fn ($fileType) => $fileType->value);
    }
}
