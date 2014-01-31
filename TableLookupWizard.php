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

class TableLookupWizard extends Widget
{

    /**
     * Submit user input
     * @var boolean
     */
    protected $blnSubmitInput = true;

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Check if there are already id's stored stored
     * @var boolean
     */
    protected $blnHasValues = false;

    /**
     * Check if this is an ajax request
     * @var boolean
     */
    protected $blnIsAjaxRequest = false;

    /**
     * SQL search operator
     * @var string
     */
    protected $strOperator = ' OR ';

    /**
     * Javascript (Ajax) fallback
     * @var boolean
     */
    protected $blnEnableFallback = true;

    /**
     * Search fields
     * @var array
     */
    protected $arrSearchFields = array();

    /**
     * List fields
     * @var array
     */
    protected $arrListFields = array();

    /**
     * JOIN statements
     * @var array
     */
    protected $arrJoins = array();

    /**
     * Query Procedure
     * @var array
     */
    protected $arrQueryProcedure = array();

    /**
     * Query Values
     * @var array
     */
    protected $arrQueryValues = array();

    /**
     * WHERE Procedure
     * @var array
     */
    protected $arrWhereProcedure = array();

    /**
     * WHERE Values
     * @var array
     */
    protected $arrWhereValues = array();


    /**
     * Store config for ajax upload.
     *
     * @access public
     * @param string $strKey
     * @param mixed $varValue
     * @return void
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'searchFields':
                $arrFields = array();
                foreach ($varValue as $k => $v) {
                    if (is_numeric($k)) {
                        $arrFields[] = $v;
                    } else {
                        $arrFields[] = $v . ' AS ' . $k;
                    }
                    $this->arrSearchFields = $arrFields;
                }
                break;

            case 'listFields':
                $this->arrListFields = $varValue;
                break;

            case 'foreignTable':
                $this->loadDataContainer($varValue);
                \System::loadLanguageFile($varValue);
                parent::__set($strKey, $varValue);
                break;

            case 'matchAllKeywords':
                $this->strOperator = $varValue ? ' AND ' : ' OR ';
                break;

            case 'mandatory':
                $this->arrConfiguration['mandatory'] = $varValue ? true : false;
                break;

            case 'disableJavascriptFallback':
                $this->blnEnableFallback = $varValue ? false : true;
                break;

            case 'joins':
                $this->arrJoins = $varValue;
                break;

            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }


    /**
     * Validate input and set value
     * @return  mixed Input
     */
    public function validator($varInput)
    {
        if ($this->mandatory && ((is_array($varInput) && !count($varInput)) || $varInput == '')) {
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
        }

        return $varInput;
    }


    /**
     * Generate the widget and return it as string
     * @return  string
     */
    public function generate()
    {
        $blnNoAjax          = \Input::get('noajax');
        $arrIds             = deserialize($this->varValue, true);

        if ($arrIds[0] == '') {
            $arrIds = array(0);
        } else {
            $this->blnHasValues = true;
        }

        $this->blnIsAjaxRequest = \Input::get('tableLookupWizard') == $this->strId;

        // Ensure search and list fields have correct aliases
        $this->ensureColumnAliases($this->arrSearchFields);;
        $this->ensureColumnAliases($this->arrListFields);

        // Ajax call
        if ($this->blnIsAjaxRequest) {
            // Clean buffer
            while (ob_end_clean());

            $this->prepareSelect();
            $this->prepareJoins();
            $this->prepareWhere();
            $this->prepareGroupBy();

            $strBuffer = $this->getBody();
            $strBuffer = json_encode(array
                                     (
                                     'content'   => $strBuffer,
                                     'token'     => REQUEST_TOKEN,
                                     ));

            header('Content-Type: application/json; charset=' . $GLOBALS['TL_CONFIG']['characterSet']);
            header('Content-Length: ' . strlen($strBuffer));
            echo $strBuffer;
            exit;
        }

        $GLOBALS['TL_CSS'][] = 'system/modules/tablelookupwizard/assets/tablelookup.min.css';

        if (!$blnNoAjax) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/tablelookupwizard/assets/tablelookup.min.js';
        }

        $this->prepareSelect();
        $this->prepareJoins();

        // Add preselect to WHERE statement
        $this->arrWhereProcedure[] = $this->foreignTable . '.id IN (' . implode(',', $arrIds) . ')';

        $this->prepareWhere();
        $this->prepareGroupBy();

        $objTemplate = new \BackendTemplate('be_widget_tablelookupwizard');
        $objTemplate->noAjax            = $blnNoAjax;
        $objTemplate->strId             = $this->strId;
        $objTemplate->fieldType         = $this->fieldType;
        $objTemplate->fallbackEnabled   = $this->blnEnableFallback;
        $objTemplate->noAjaxUrl         = $this->addToUrl('noajax=1');
        $objTemplate->listFields        = $this->arrListFields;
        $objTemplate->listFieldsCount   = count($this->arrListFields);
        $objTemplate->searchLabel       = $this->searchLabel == '' ? $GLOBALS['TL_LANG']['MSC']['searchLabel'] : $this->searchLabel;
        $objTemplate->columnLabels      = $this->getColumnLabels();
        $objTemplate->hasValues         = $this->blnHasValues;
        $objTemplate->body              = $this->getBody();

        return $objTemplate->parse();
    }

    /**
     * Renders the table body
     * @return  string
     */
    public function getBody()
    {
        $objTemplate    = new \BackendTemplate('be_widget_tablelookupwizard_content');
        $arrResults     = array();

        // Get results
        $objResults = \Database::getInstance()
            ->prepare(implode(' ', $this->arrQueryProcedure))
            ->execute($this->arrQueryValues);

        if ($objResults->numRows) {
            $objTemplate->hasResults = true;
        }

        while($objResults->next()) {
            $arrRow = $objResults->row();
            $strKey = $arrRow[$this->foreignTable . '_id'];
            $arrResults[$strKey]['rowId'] = $arrRow[$this->foreignTable . '_id'];
            $arrResults[$strKey]['rawData'] = $arrRow;

            // Mark checked if not ajax call
            if (!$this->blnIsAjaxRequest) {
                $arrResults[$strKey]['isChecked'] = $this->optionChecked($arrRow[$this->foreignTable . '_id'], $this->varValue);
            }

            foreach ($this->arrListFields as $strField) {
                list($strTable, $strColumn) = explode('.', $strField);
                $strFieldKey = str_replace('.', '_', $strField);
                $arrResults[$strKey]['formattedData'][$strFieldKey] = \Haste\Util\Format::dcaValue($strTable, $strColumn, $arrRow[$strFieldKey]);
            }
        }

        \Haste\Generator\RowClass::withKey('rowClass')
            ->addCount('row_')
            ->addFirstLast('row_')
            ->addEvenOdd('row_')
            ->applyTo($arrResults);

        $objTemplate->results           = $arrResults;
        $objTemplate->colspan           = count($this->arrListFields) + 1;
        $objTemplate->noResultsMessage  = sprintf($GLOBALS['TL_LANG']['MSC']['tlwNoResults'], \Input::get('keywords'));
        $objTemplate->fieldType         = $this->fieldType;
        $objTemplate->isAjax            = $this->blnIsAjaxRequest;
        $objTemplate->strId             = $this->strId;

        return $objTemplate->parse();
    }

    /**
     * Prepares the SELECT statement
     */
    protected function prepareSelect()
    {
        $arrSelects = array($this->foreignTable . '.id AS ' . $this->foreignTable .'_id');

        foreach ($this->arrListFields as $strField) {
            $arrSelects[] = $strField . ' AS ' . str_replace('.', '_', $strField);
        }

        // Build SQL statement
        $this->arrQueryProcedure[] = 'SELECT ' . implode(', ', $arrSelects);
        $this->arrQueryProcedure[] = 'FROM ' . $this->foreignTable;
    }

    /**
     * Prepares the JOIN statement
     */
    protected function prepareJoins()
    {
        if (!empty($this->arrJoins)) {
            foreach ($this->arrJoins as $k => $v) {
                $this->arrQueryProcedure[] = sprintf("%s %s ON %s.%s = %s.%s", $v['type'], $k, $k, $v['jkey'], $this->foreignTable, $v['fkey']);
            }
        }
    }

    /**
     * Prepares the WHERE statement
     */
    protected function prepareWhere()
    {
        $arrKeywords        = trimsplit(' ', \Input::get('keywords'));
        $varData            = \Input::get($this->strName);

        // Handle keywords
        foreach ($arrKeywords as $strKeyword) {
            if (!$strKeyword)
                continue;

            $this->arrWhereProcedure[]  = '(' . implode(' LIKE ? OR ', $this->arrSearchFields) . ' LIKE ?)';
            $this->arrWhereValues       = array_merge($this->arrWhereValues, array_fill(0, count($this->arrSearchFields), '%' . $strKeyword . '%'));
        }

        // Filter those that have already been chosen
        if ($this->fieldType == 'checkbox' && is_array($varData) && !empty($varData)) {
            $this->arrWhereProcedure[] = $this->foreignTable . '.id NOT IN (' . implode(',', $varData) . ')';
        } elseif ($this->fieldType == 'radio' && $varData != '') {
            $this->arrWhereProcedure[] = "{$this->foreignTable}.id!='$varData'";
        }

        // If custom WHERE is set, add it to the statement
        if ($this->sqlWhere) {
            $this->arrWhereProcedure[] = $this->sqlWhere;
        }

        if (!empty($this->arrWhereProcedure)) {
            $strWhere = implode(' AND ', $this->arrWhereProcedure);
            $this->arrQueryProcedure[]  = 'WHERE ' . $strWhere;
            $this->arrQueryValues       = array_merge($this->arrQueryValues, $this->arrWhereValues);
        }
    }

    /**
     * Prepares the GROUP BY statement
     */
    protected function prepareGroupBy()
    {
        if ($this->sqlGroupBy) {
            $this->arrQueryProcedure[] = "GROUP BY {$this->sqlGroupBy}";
        }
    }

    /**
     * Ensures that the columns are all aliased
     * If there's no alias passed in, it will automatically treat it as a
     * column of the foreignTable
     * @param   array
     */
    protected function ensureColumnAliases(&$arrFields)
    {
        foreach ($arrFields as $k => $strField) {
            if (strpos($strField, '.') !== false) {
                continue;
            }

            $arrFields[$k] = $this->foreignTable . '.' . $strField;
        }
    }

    /**
     * Get formatted column labels
     * @return  array
     */
    protected function getColumnLabels()
    {
        $arrLabels = array();

        foreach ($this->arrListFields as $strField) {
            $strKey = standardize($strField);
            list($strTable, $strColumn) = explode('.', $strField);
            $arrLabels[$strKey]['label'] = \Haste\Util\Format::dcaLabel($strTable, $strColumn);
        }

        \Haste\Generator\RowClass::withKey('rowClass')
            ->addCount('row_')
            ->addFirstLast('row_')
            ->addEvenOdd('row_')
            ->applyTo($arrLabels);

        return $arrLabels;
    }
}