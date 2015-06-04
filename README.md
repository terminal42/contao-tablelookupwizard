tablelookupwizard Contao Extension
==========================

This widget allows you to lookup a foreign table and select records from it. Its primary advantage is that not all database records are listed, so it is very useful if you have a large set of records.

How to use:

```php
// DCA definition
'fieldname' => array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_tablename']['fieldname'],
    'inputType'               => 'tableLookup',

    'eval'                    => array
    (
        // The foreign table you want to search in
        'foreignTable'        => 'tl_foreign_tablename',

        // Define "checkbox" for multi selects and "radio" for single selects
        'fieldType'           => 'checkbox',

        // A list of fields to be displayed in the table
        'listFields'          => array('field1', 'field2', 'tl_my_superb_join_table.field1'),

        // Fields that can be searched for the keyword
        'searchFields'        => array('field1', 'tl_my_superb_join_table.field1'),

        // Adds multiple left joins to the sql statement (optional)
        'joins'               => array
        (
            // Defines the join table
            'tl_my_superb_join_table' => array
            (
                // Join type (e.g. INNER JOIN, LEFT JOIN, RIGHT JOIN)
                'type' => 'LEFT JOIN',

                // Key of the join table
                'jkey' => 'pid',

                // Key of the foreign table
                'fkey' => 'id'
            )
        ),

        // Adds a "GROUP BY" to the sql statement (optional)
        'sqlGroupBy'          => 'tl_my_superb_join_table.fid',

        // Find every given keyword
        'matchAllKeywords'    => true

        // Custom additional WHERE conditions
        'sqlWhere'            => 'AND someother=condition',

        // The search button label
        'searchLabel'               => 'Search my table now!',
    ),

    // SQL field definition
    'sql'                     => "blob NULL"
);
```
