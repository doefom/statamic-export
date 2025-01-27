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
use Statamic\Fields\Field;

class EntriesExport implements FromCollection, WithStyles
{
    public function __construct(public Collection $items, public array $config = [])
    {
    }

    /**
     * Get all keys from all items combined (unique). Then go through all keys and check for each item if the key
     * exists and if it does store the value. Else, store an empty string.
     *
     * If headers should be included, prepend those to the result array.
     *
     * @return Collection
     */
    public function collection(): Collection
    {
        // Get all unique keys from all items
        $keys = $this->getAllKeysCombined($this->items);

        $result = [];
        foreach ($keys as $key => $label) {
            // Add the key to the collection if it doesn't exist
            foreach ($this->items as $index => $item) {
                // Handle special field "date" separately
                if ($key === 'date' && $item->hasDate()) {
                    $date = $item->date();

                    if ($date instanceof \Illuminate\Support\Carbon) {
                        if ($item->hasSeconds()) {
                            $value = $date->format('Y-m-d H:i:s');
                        } elseif ($item->hasTime()) {
                            $value = $date->format('Y-m-d H:i');
                        } else {
                            $value = $date->format('Y-m-d');
                        }
                    } else {
                        $value = $date ?? '';
                    }

                    $result[$index][$key] = $value;
                    continue;
                }

                // Handle special field "slug" separately
                if ($key === 'slug') {
                    $result[$index][$key] = $item->slug() ?? '';
                    continue;
                }

                // If the key doesn't exist, add an empty string to avoid unnecessary augmentation.
                if ($item->get($key) === null) {
                    $result[$index][$key] = ''; // Necessary to prevent mixing up columns
                    continue;
                }

                $value = $item->augmentedValue($key);
                $result[$index][$key] = $this->toString($value);
            }
        }

        // Add the headers to the collection
        if (Arr::get($this->config, 'headers', true)) {
            $result = Arr::prepend($result, $keys->toArray());
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

        if ($fieldType instanceof \Statamic\Fieldtypes\Bard) {
            return json_encode($value->value());
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
            return $value->value()->label() ?? $value->raw();
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
        $excludedFields = Arr::get($this->config, 'excluded_fields', []);

        return $items
            // Map each Entry item to its blueprint fields
            ->map(fn(Entry $item) => $item->blueprint()->fields()->all())
            // Flatten the resulting collection to remove nested structures
            ->flatten()
            // Remove duplicate fields
            ->unique(fn(Field $field) => $field->handle())
            // Remove fields that are excluded by the user. If there are no excluded fields, this will have no effect.
            ->filter(fn(Field $field) => !in_array($field->handle(), $excludedFields))
            // Filter out fields that are instances of certain field types
            ->filter(function (Field $field) {
                return !$field->fieldtype() instanceof \Statamic\Fieldtypes\Hidden
                    && !$field->fieldtype() instanceof \Statamic\Fieldtypes\Revealer
                    && !$field->fieldtype() instanceof \Statamic\Fieldtypes\Html
                    && !$field->fieldtype() instanceof \Statamic\Fieldtypes\Spacer;
            })
            // Map the fields to a key-value pair with the handle as key and the display name as value
            ->mapWithKeys(fn(Field $field) => [$field->handle() => $field->display()]);
    }

}
