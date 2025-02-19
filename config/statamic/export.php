<?php

use Illuminate\Support\Arr;

return [
    /*
    |--------------------------------------------------------------------------
    | Fieldtype Mappings
    |--------------------------------------------------------------------------
    |
    | Define custom mappings for how specific fieldtypes should be converted to
    | strings during export. Each key should be the fully qualified class name
    | of the fieldtype, and the value should be a closure that receives the
    | field value and returns a string.
    |
    */
    'fieldtype_mappings' => [
        // Example:
        // \Custom\Fieldtype::class => function (\Statamic\Fields\Value $value) {
        //     ... do something with the value ...
        //     return (string) $transformedValue;
        // },
    ]
];
