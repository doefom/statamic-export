<?php

namespace Doefom\StatamicExport\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Entries\Entry;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;

class EntriesExport implements FromCollection
{
    public function __construct(public Collection $items)
    {
    }

    public function collection(): Collection
    {
        // Get all unique keys from all items
        $keys = $this->getAllKeysCombined($this->items);

        $result = [];
        foreach ($this->items as $item) {
            // Initialize an array to store the item's values for each key
            $values = [];
            foreach ($keys as $key) {
                // Get the augmented value for the key
                $value = $item->augmentedValue($key);
                // Convert the value to a string representation suitable for export
                $values[] = $this->toString($value);
            }
            // Add the mapped keys for this item to the result array
            $result[] = $values;
        }

        return collect($result);
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
        return $items
            ->map(fn(Entry $item) => $item->blueprint()->fields()->all()->keys())
            ->flatten()
            ->unique();
    }

}
