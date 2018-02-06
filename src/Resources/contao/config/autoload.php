<?php

/**
 * Extension for Contao Open Source CMS
 *
 * Copyright (C) 2013 terminal42 gmbh
 *
 * @package    TableLookupWizard
 * @link       http://www.terminal42.ch
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    'TableLookupWizard' => 'system/modules/tablelookupwizard/TableLookupWizard.php',
));

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'be_widget_tablelookupwizard'               => 'system/modules/tablelookupwizard/templates',
    'be_widget_tablelookupwizard_content'       => 'system/modules/tablelookupwizard/templates',
));
