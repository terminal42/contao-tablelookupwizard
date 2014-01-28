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
		// The foreign table is searched
		'foreignTable'        => 'tl_foreign_tablename',

		// Define "checkbox" on multi select and "radio" on single select
		'fieldType'           => 'checkbox',

		// A list of fields to be displayed in the table
		'listFields'          => array('field1', 'field2'),

		// Fields that can be searched according to which
		// jtf.content is a foreign key and will work only if joins are defined
		'searchFields'        => array('field1', 'jtf.content'),

		// Adds multiple left joins to the sql statement (optional)
		'joins'               => array
		(
			// Defines the join alias
			'jtf' => array
			(
				// Join table
				'table' => 'tl_translation_fields',

				// Key of the join table
				'jkey' => 'fid',

				// Key of the foreign table
				'fkey' => 'title'
			),
			'joinAlias' => array
			(
				// ...
			)
		),

		// Adds a "GROUP BY" to the sql statement (optional)
		'sqlGroupBy'          => 'jtf.fid',

		// Find every given keyword
		'matchAllKeywords'    => true
	),

	// Use the callback to change the value of a list item
	'list_value_callback' => function ($varValue, $strField)
	{
		if ($strField == 'field1') {
			// Returns a translated string by a translation fid
			return \TranslationFields::translateValue($varValue);
		}
	},

	// SQL field definition
	'sql'                     => "blob NULL"
);
```
