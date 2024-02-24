<?php

namespace Doefom\StatamicExport\Exports;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Statamic\Contracts\Auth\User;
use Statamic\Entries\Entry;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;

class EntriesExport implements FromCollection, WithStyles
{
    public function __construct(public Collection $items, public array $config = [])
    {
    }

    public function collection(): Collection
    {
        // Get all unique keys from all items
        $keys = $this->getAllKeysCombined($this->items);

        $result = [];
        foreach ($keys as $key => $label) {
            // Add the key to the collection if it doesn't exist
            foreach ($this->items as $index => $item) {
                $value = $item->augmentedValue($key);
                $result[$index][$key] = $this->toString($value);
            }
        }

        // Add the headers to the collection
        if (Arr::get($this->config, 'headers', true)) {
            $result = array_prepend($result, $keys->toArray());
        }

        return collect($result);
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        $hasHeaders = Arr::get($this->config, 'headers', true);
        if ($hasHeaders) {
            $styles[1] = ['font' => ['bold' => true]];
        }

        return $styles;
    }

    private function toString(mixed $value): string
    {
        if ($value->value() === null) {
            return '';
        }

        $fieldType = $value->fieldtype();

        if (
            $fieldType instanceof \Statamic\Fieldtypes\Text // Slug field type inherits from Text and therefore must not be checked separately
            || $fieldType instanceof \Statamic\Fieldtypes\Bard
            || $fieldType instanceof \Statamic\Fieldtypes\Markdown
            || $fieldType instanceof \Statamic\Fieldtypes\Textarea
            || $fieldType instanceof \Statamic\Fieldtypes\Video
            || $fieldType instanceof \Statamic\Fieldtypes\Floatval
            || $fieldType instanceof \Statamic\Fieldtypes\Integer
            || $fieldType instanceof \Statamic\Fieldtypes\Color
            || $fieldType instanceof \Statamic\Fieldtypes\Hidden
            || $fieldType instanceof \Statamic\Fieldtypes\Template
            || $fieldType instanceof \Statamic\Fieldtypes\Yaml
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
            || $fieldType instanceof \Statamic\Fieldtypes\Replicator
            || $fieldType instanceof \Statamic\Fieldtypes\Table
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

        if (
            $fieldType instanceof \Statamic\Fieldtypes\Icon
            || $fieldType instanceof \Statamic\Fieldtypes\Date
            || $fieldType instanceof \Statamic\Fieldtypes\Time
        ) {
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

        if (
            $fieldType instanceof \Statamic\Fieldtypes\Lists
            || $fieldType instanceof \Statamic\Fieldtypes\Taggable
        ) {
            return collect($value->value())->implode(', ');
        }

        return '';
    }

    private function getAllKeysCombined(Collection $items): Collection
    {
        $keys = $items
            ->map(fn(Entry $item) => $item->blueprint()->fields()->all()->keys())
            ->flatten()
            ->unique();

        $keyLabelPairs = [];
        foreach ($keys as $key) {
            foreach ($items as $item) {
                // Check if key is already in the array
                if (Arr::has($keyLabelPairs, $key)) {
                    break;
                }

                // Get the label for the key and skip if the fieldtype is null.
                // This is particularly important for fields that are not present in all entries because then the
                // augmented value will not have a fieldtype which will result in an error.
                $augmentedValue = $item->augmentedValue($key);
                if ($augmentedValue->fieldtype() === null) {
                    continue;
                }

                // Skip if the label is null
                $labelForKey = $augmentedValue->field()->display();
                if ($labelForKey === null) {
                    continue;
                }

                // Add the key and label to the array
                $keyLabelPairs[$key] = $labelForKey;
            }
        }

        return collect($keyLabelPairs);
    }

    private function getAllLabelsCombined(Collection $items, Collection $keys): Collection
    {
        // TODO: Get the labels by using the keys
        return collect();
    }

}
