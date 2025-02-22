# Statamic Export

> **Export entries and collections** from Statamic to a variety of formats including **XLSX, CSV, HTML and more**.

### Supported versions

- ✅ Statamic v4
- ✅ Statamic v5

## Features

### Export Entries and Collections

Get your entries and collections ready in the format you need. Currently, we support the following formats:

- **XLSX:** Ideal for detailed data analysis and reporting in Excel.
- **CSV:** Perfect for data import/export with other systems.
- **TSV:** A tab-separated format, offering an alternative to CSV.
- **ODS:** For users of OpenDocument spreadsheet applications.
- **XLS:** Compatible with older versions of Excel.
- **HTML:** Web-friendly format, easily convertible to PDF with online tools.

> Why no PDF you ask? Well, we're working on it. But for now, you can use the HTML format and convert it to PDF using a
> variety of tools available online.

We tried to use the best human-readable representation for each fieldtype. For example, the `Entries` fieldtype will be
represented as a comma separated list of entry titles instead of multiple entry IDs.

### Export Users

Exporting users works the same way as exporting entries. You can either export specific users via the user listing
or export all users using the utility.

## Supported Fieldtypes

So, there are quite a few fieldtypes available in Statamic. We're supporting **all of them**, even custom ones. The
fieldtypes `\Statamic\Fieldtypes\Hidden`, `\Statamic\Fieldtypes\Revealer`, `\Statamic\Fieldtypes\Html` and
`\Statamic\Fieldtypes\Spacer` are excluded by default because they don't contain any data.

### Custom Fieldtypes

If your custom fieldtypes extend one of the default fieldtypes, it should work just fine as is. But if you have a fully
custom fieldtype, you can add support for it by defining it in the `config/export.php` file and providing a closure that
returns a string representation of the field value.

```php
<?php

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
        \Custom\Fieldtype::class => function (\Statamic\Fields\Value $value) {
            // ... do something with the value ...
            return (string) $transformedValue;
        },
    ],
];
```

## How to Install

### Via Statamic Control Panel

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**.

### Via Composer

Alternatively, install the addon using Composer by running the following command in your project root:

``` bash
composer require doefom/statamic-export
```

## How to Use

Once installed, there are basically two ways to use this addon.

### Exporting Entries

1. Head to the entries listing of one of your collections.
2. Select one or more entries.
3. Click the "Export" action above the table.
4. Choose your export format, exclude certain fields if you want to and specify whether you'd like to include headers (
   included by default).

### Exporting Users

1. Navigate to Users.
2. Select one or more users.
3. Click the "Export" action above the table.
4. Choose your export format, exclude certain fields if you want to and specify whether you'd like to include headers (
   included by default).

### Exporting Collections

1. Navigate to Utilities > Export.
2. Select the "Collections" tab.
3. Select your collection, export format, add excluded fields if you want to and specify whether to include headers or
   not.
4. Click the "Export collection" button.

### Exporting Users

1. Navigate to Utilities > Export.
2. Select the "Users" tab.
3. Select the export format, add excluded fields if you want to and specify whether to include headers or not.
4. Click the "Export users" button.

## Roadmap

- [x] Introduce support for custom fieldtypes that do not extend the default fieldtypes
- [x] Support exporting users
- [ ] Implement PDF export functionality
- [ ] Support setting the export filename
