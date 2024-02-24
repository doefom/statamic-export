# Statamic Export

> **Export entries and collections** from Statamic to a variety of formats including **XLSX, CSV, HTML and more**.

## Features

Get your entries and collections ready in the format you need. Currently, we support the following formats:

- **XLSX:** Ideal for detailed data analysis and reporting in Excel.
- **CSV:** Perfect for data import/export with other systems.
- **TSV:** A tab-separated format, offering an alternative to CSV.
- **ODS:** For users of OpenDocument spreadsheet applications.
- **XLS:** Compatible with older versions of Excel.
- **HTML:** Web-friendly format, easily convertible to PDF with online tools.

> Why no PDF you ask? Well, we're working on it. But for now, you can use the HTML format and convert it to PDF using a
> variety of tools available online.

We tried to use the best human readable representation for each format. For example, the Entries fieldtype will be
represented as a comma separated list of entry titles instead of multiple entry IDs.

## Supported Fieldtypes

So, there are quite a few fieldtypes available in Statamic. We're supporting **all of them**, but:

- We do not support custom fieldtypes (yet). There will be a way to extend the functionality to support custom
  fieldtypes in the future.
- However, if your custom fieldtypes extend one of the default fieldtypes, it should work just fine.

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
2. Select one or more entries
3. Click the "Export" action above the table.
4. Choose your export format and specify if you want to include headers (included by default).

### Exporting Collections

1. Navigate to Utilities > Export
2. Select your collection, export format, and whether to include headers or not.
3. Click the "Export collection" button.

## Roadmap

- [ ] Introduce support for custom fieldtypes that do not extend the default fieldtypes
- [ ] Implement PDF export functionality
- [ ] Support customizing the export columns
- [ ] Support setting the export filename
