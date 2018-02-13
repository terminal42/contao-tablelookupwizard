<?php

/*
 * Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2013 - 2018, terminal42 gmbh
 * @package    TableLookupWizard
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Register the classes.
 */
ClassLoader::addClasses([
    'TableLookupWizard' => 'system/modules/tablelookupwizard/TableLookupWizard.php',
]);

/*
 * Register the templates
 */
TemplateLoader::addFiles([
    'be_widget_tablelookupwizard' => 'system/modules/tablelookupwizard/templates',
    'be_widget_tablelookupwizard_content' => 'system/modules/tablelookupwizard/templates',
]);
