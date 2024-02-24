<?php

namespace Doefom\StatamicExport\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Entries\Entry;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;

/**
 * Class EntriesExport
 * Implements FromCollection to allow exporting a collection of Statamic entries to Excel.
 * It leverages Laravel's collection methods to format and prepare the data for export.
 */
class EntriesExport implements FromCollection
{
    /**
     * Constructor to inject the collection of items to be exported.
     *
     * @param Collection $items The collection of items (entries) to be exported.
     */
    public function __construct(public Collection $items)
    {
    }

    /**
     * Prepare the data collection for export.
     *
     * This method iterates over each item in the collection, transforming it into a format suitable for export.
     * It retrieves the blueprint for each entry, iterates over the fields, and formats the value for export.
     *
     * @return Collection The formatted collection ready for export.
     */
    public function collection(): Collection
    {
        return $this->items->map(function (Entry $item) {
            // Retrieve all field keys from the entry's blueprint
            $keys = $item->blueprint()->fields()->all()->keys();

            // Map over each key and retrieve its augmented value for export
            return $keys->map(function (string $key) use ($item) {
                $value = $item->augmentedValue($key);
                // Convert each value to a string representation suitable for export
                return $this->toString($value);
            });

        });
    }

    /**
     * Convert a given value into a string representation.
     *
     * This method handles various types of values (e.g., EntryCollection, Carbon, User, etc.)
     * and converts them into a string format. This includes formatting dates, joining collection titles,
     * and encoding arrays to JSON.
     *
     * @param mixed $value The value to be converted to a string.
     * @return string The string representation of the value.
     */
    private function toString(mixed $value): string
    {
        if ($value->value() === null) {
            return '';
        }

        // Note: Revealer field type cannot be exported as it is not used to store data.
        $fieldType = $value->fieldtype();

        if (
            $fieldType instanceof \Statamic\Fieldtypes\Text
            || $fieldType instanceof \Statamic\Fieldtypes\Bard
            || $fieldType instanceof \Statamic\Fieldtypes\Markdown
            || $fieldType instanceof \Statamic\Fieldtypes\Textarea
            || $fieldType instanceof \Statamic\Fieldtypes\Video
            || $fieldType instanceof \Statamic\Fieldtypes\Floatval
            || $fieldType instanceof \Statamic\Fieldtypes\Integer
        ) {
            return $value->value();
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Code) {
            return $value->value()->value();
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Toggle) {
            return $value->value() ? 'yes' : 'no';
        }

        if (
            $fieldType instanceof \Statamic\Fieldtypes\Checkboxes
            || $fieldType instanceof \Statamic\Fieldtypes\Arr
            || $fieldType instanceof \Statamic\Fieldtypes\Grid
            || $fieldType instanceof \Statamic\Fieldtypes\Group
        ) {
            return json_encode($value->value());
        }

        if (
            $fieldType instanceof \Statamic\Fieldtypes\ButtonGroup
            || $fieldType instanceof \Statamic\Fieldtypes\Radio
            || $fieldType instanceof \Statamic\Fieldtypes\Select
            || $fieldType instanceof \Statamic\Fieldtypes\Width
        ) {
            return $value->value()->label();
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Icon) {
            return $value->raw();
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Assets\Assets) {
            return $value->value() instanceof AssetContract
                ? $value->value()->url() // Single asset
                : $value->value()->get()->map(fn(AssetContract $asset) => $asset->url())->implode(', '); // Multiple assets
        }

        if (
            $fieldType instanceof \Statamic\Fieldtypes\Collections
            || $fieldType instanceof \Statamic\Fieldtypes\Navs
            || $fieldType instanceof \Statamic\Forms\Fieldtype
            || $fieldType instanceof \Statamic\Fieldtypes\Structures
            || $fieldType instanceof \Statamic\Fieldtypes\Taxonomies
            || $fieldType instanceof \Statamic\Fieldtypes\UserGroups
            || $fieldType instanceof \Statamic\Fieldtypes\UserRoles
        ) {
            return $value->value() instanceof \Illuminate\Support\Collection
                ? $value->value()->map(fn($item) => $item->title())->implode(', ') // Multiple items
                : $value->value()->title(); // Single item
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Entries) {
            return $value->value() instanceof EntryContract
                ? $value->value()->title // Single entry
                : $value->value()->get()->map(fn(EntryContract $entry) => $entry->title)->implode(', '); // Multiple entries (\Statamic\Query\StatusQueryBuilder)
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Link) {
            $linkVal = $value->value()->value();
            return $linkVal instanceof EntryContract
                ? $linkVal->title
                : $linkVal;
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Sites) {
            return $value->value() instanceof \Statamic\Sites\Site
                ? $value->value()->name()
                : $value->value()->map(fn($site) => $site->name())->implode(', ');
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Terms) {
            return $value->value() instanceof TermContract
                ? $value->value()->title()
                : $value->value()->get()->map(fn($item) => $item->title())->implode(', ');
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Users) {
            return $value->value() instanceof User
                ? $value->value()->title
                : $value->value()->get()->map(fn($item) => $item->title())->implode(', ');
        }

        return '';
    }

}
