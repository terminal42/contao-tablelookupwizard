# tablelookupwizard Contao Extension

A backend widget for Contao that allows editors to search and select records from a foreign database table without loading the entire dataset.

It is specifically designed for large datasets where a traditional `select`, `checkboxWizard`, or static select field would be inefficient or unusable.

---

## Installation

Install via Composer:

```bash
composer require terminal42/contao-tablelookupwizard
```

---

## Basic Usage (DCA Example)

```php
'myField' => [
    'inputType' => 'tableLookup',
    'eval' => [
        'foreignTable' => 'tl_news',
        'listFields'   => ['headline'],
    ],
    'sql' => ['type' => \Doctrine\DBAL\Types\Types::INTEGER, 'unsigned' => true, 'default' => 0],
],
```

---

## Configuration Reference

### Required Options

| Option         | Type       | Default | Description                                                                       |
|----------------|------------|---------|-----------------------------------------------------------------------------------|
| `foreignTable` | `string`   | -       | Base database table used for querying records.                                    |
| `listFields`   | `string[]` | -       | Fields displayed in the result table. Use fully qualified names when using joins. |

### Selection Options

| Option         | Type       | Default | Description                                                         |
|----------------|------------|---------|---------------------------------------------------------------------|
| `multiple`     | `bool`     | `false` | Allows selecting multiple records.                                  |
| `isSortable`   | `bool`     | `false` | Enables drag & drop sorting. Only effective when `multiple = true`. |
| `headerFields` | `string[]` | `[]`    | Custom column header labels. Must match `listFields` length.        |

### Search Options

| Option           | Type       | Default    | Description                                                         |
|------------------|------------|------------|---------------------------------------------------------------------|
| `searchFields`   | `string[]` | `[]`       | Fields used for keyword searching. Defaults to `listFields`.        |
| `searchMatchAll` | `bool`     | `false`    | If true, all keywords must match (AND). Otherwise OR logic is used. |
| `searchLabel`    | `string`   | `"Search"` | Label for the search button.                                        |

### Template Options

| Option             | Type     | Default                                    | Description                              |
|--------------------|----------|--------------------------------------------|------------------------------------------|
| `customTpl`        | `string` | `backend/widget/tablelookupwizard`         | Custom backend widget template.          |
| `customRecordsTpl` | `string` | `backend/widget/tablelookupwizard_records` | Template for rendering selected records. |


### SQL Options

| Option       | Type     | Default | Description                                                               |
|--------------|----------|---------|---------------------------------------------------------------------------|
| `sqlWhere`   | `string` | `''`    | Additional SQL condition appended to WHERE (without the `WHERE` keyword). |
| `sqlOrderBy` | `string` | `''`    | Adds ORDER BY clause to search results.                                   |
| `sqlGroupBy` | `string` | `''`    | Adds GROUP BY clause.                                                     |
| `sqlLimit`   | `int`    | `30`    | Maximum number of result rows displayed.                                  |
| `sqlJoins`   | `array`  | `[]`    | Defines SQL joins to make related tables available.                       |

#### Join Parameters

| Parameter    | Type     | Description                                              |
|--------------|----------|----------------------------------------------------------|
| `type`       | `string` | SQL join type (`INNER JOIN`, `LEFT JOIN`, `RIGHT JOIN`). |
| `joinKey`    | `string` | Column on the base table (`foreignTable`).               |
| `foreignKey` | `string` | Column on the joined table.                              |

After joining, fields can be referenced as:

```text
tl_news_archive.title
```

---

## Full Configuration Example

```php
'myField' => [
    'inputType' => 'tableLookup',
    'eval' => [

        // Required
        'foreignTable' => 'tl_news',
        'listFields'   => [
            'tl_news.headline',
            'tl_news.date',
            'tl_news_archive.title',
        ],

        // Selection behavior
        'multiple'     => true,
        'isSortable'   => true,

        'headerFields' => [
            'Headline',
            'Date published',
            'News archive',
        ],

        // Search behavior
        'searchFields'   => [
            'tl_news.headline',
            'tl_news_archive.title',
        ],
        'searchMatchAll' => true,
        'searchLabel'    => 'Search records',

        // Template overrides
        'customTpl'        => 'backend/widget/custom_tablelookupwizard',
        'customRecordsTpl' => 'backend/widget/custom_tablelookupwizard_records',

        // SQL customization
        'sqlWhere'   => 'tl_news.protected=1',
        'sqlOrderBy' => 'tl_news.date DESC',
        'sqlGroupBy' => 'tl_news.pid',
        'sqlLimit'   => 100,
        'sqlJoins'   => [
            'tl_news_archive' => [
                'type'       => 'INNER JOIN',
                'joinKey'    => 'pid',
                'foreignKey' => 'id',
            ],
        ],
    ],
    'sql' => ['type' => \Doctrine\DBAL\Types\Types::BLOB, 'notnull' => false],
],
```

---

## Data Storage

Storage behavior:

- `multiple => false` → single ID stored
- `multiple => true` → serialized array of IDs stored

The DCA field should use:

```php
// multiple => false
'sql' => ['type' => \Doctrine\DBAL\Types\Types::INTEGER, 'unsigned' => true, 'default' => 0],

// multiple => true
'sql' => ['type' => \Doctrine\DBAL\Types\Types::BLOB, 'notnull' => false],
```

Adjust if your implementation differs.

---

## Best Practices

- Use fully qualified column names when joins are present.
- Ensure `headerFields` count matches `listFields`.
- Avoid unsanitized input inside `sqlWhere`.
- Index searchable fields for performance.
