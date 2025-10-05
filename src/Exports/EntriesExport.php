<?php

namespace Doefom\StatamicExport\Exports;

use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Entries\Entry;
use Statamic\Fields\Field;

class EntriesExport implements FromGenerator, WithStyles
{
    public function __construct(public Generator|LazyCollection $items, public array $config = []) {}

    /**
     * Get all keys from all items combined (unique). Then go through all keys and check for each item if the key
     * exists and if it does store the value. Else, store an empty string.
     *
     * If headers should be included, prepend those to the result array.
     */
    public function generator(): Generator
    {
        Sheet::class;
        $excludedFields = Arr::get($this->config, 'excluded_fields', []);
        $keysForBlueprint = [];

        foreach ($this->items as $index => $item) {
            $blueprint = $item->blueprint();

            if (!in_array($blueprint->handle(), $keysForBlueprint)) {
                $keysForBlueprint[$blueprint->handle()] = $blueprint
                    ->fields()
                    ->all()
                    ->unique(fn (Field $field) => $field->handle())
                    ->filter(fn (Field $field) => ! in_array($field->handle(), $excludedFields))
                    ->filter(fn(Field $field) => ! $field->fieldtype() instanceof \Statamic\Fieldtypes\Hidden
                            && ! $field->fieldtype() instanceof \Statamic\Fieldtypes\Revealer
                            && ! $field->fieldtype() instanceof \Statamic\Fieldtypes\Html
                            && ! $field->fieldtype() instanceof \Statamic\Fieldtypes\Spacer)
                    ->mapWithKeys(fn ($field) => [$field->handle() => $field->display()]);
            }

            // First row with headers
            if ($index === 0 && Arr::get($this->config, 'headers', true)) {
                yield $keysForBlueprint[$blueprint->handle()]->all();
            }

            $row = [];
            foreach ($keysForBlueprint[$blueprint->handle()] as $key => $label) {
                $row[$label] = $this->getItemValue($item, $key);
            }

            yield $row;
        }
    }

    private function getItemValue($item, $key): mixed
    {
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

            return $value;
        }

        if ($item->get($key) === null) {
            return '';
        }

        $value = $item->augmentedValue($key);

        return $this->toString($value);
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

        if ($value->value() instanceof \Statamic\Structures\Page) {
            return $value->value()->id();
        }

        $fieldType = $value->fieldtype();
        $fieldTypeClass = get_class($fieldType);

        // Check for custom mapping
        $mappings = config('statamic.export.fieldtype_mappings', []);
        if (isset($mappings[$fieldTypeClass])) {
            return (string) $mappings[$fieldTypeClass]($value);
        }

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
            $fieldType instanceof \Statamic\Fieldtypes\Select
        ) {
            if (is_array($value->value())) {
                return collect($value->value())
                    ->map(fn ($item) => $item['label'] ?? $item['key'])
                    ->filter()
                    ->implode(', ');
            }

            return $value->value()->label() ?? $value->raw();
        }

        if (
            $fieldType instanceof \Statamic\Fieldtypes\ButtonGroup
            || $fieldType instanceof \Statamic\Fieldtypes\Radio
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
                : $value->value()->get()->map(fn (AssetContract $asset) => $asset->url())->implode(', '); // Multiple assets
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
                ? $value->value()->map(fn ($item) => $item->title())->implode(', ') // Multiple items
                : $value->value()->title(); // Single item
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Entries) {
            return $value->value() instanceof EntryContract
                ? $value->value()->title // Single entry
                : $value->value()->get()->map(fn (EntryContract $entry) => $entry->title)->implode(', '); // Multiple entries (\Statamic\Query\StatusQueryBuilder)
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
                : $value->value()->map(fn ($site) => $site->name())->implode(', ');
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Terms) {
            return $value->value() instanceof TermContract
                ? $value->value()->title()
                : $value->value()->get()->map(fn ($item) => $item->title())->implode(', ');
        }

        if ($fieldType instanceof \Statamic\Fieldtypes\Users) {
            return $value->value() instanceof User
                ? $value->value()->title
                : $value->value()->get()->map(fn ($item) => $item->title())->implode(', ');
        }

        if (
            $fieldType instanceof \Statamic\Fieldtypes\Lists
            || $fieldType instanceof \Statamic\Fieldtypes\Taggable
        ) {
            return collect($value->value())->implode(', ');
        }

        return '';
    }
}
