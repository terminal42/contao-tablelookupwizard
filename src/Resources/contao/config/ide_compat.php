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


// This file is not used in Contao. Its only purpose is to make PHP IDEs like
// Eclipse, Zend Studio or PHPStorm realize the class origins, since the dynamic
// class aliasing we are using is a bit too complex for them to understand.

namespace {
    \define('TL_ROOT', __DIR__ . '/../../../../../');
    \define('TL_ASSETS_URL', 'http://localhost/');
    \define('TL_FILES_URL', 'http://localhost/');
}

namespace {
    class TableLookupWizard extends \Contao\TableLookupWizard {}
}
