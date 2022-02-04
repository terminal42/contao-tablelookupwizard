<?php

/*
 * Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2013 - 2018, terminal42 gmbh
 * @package    TableLookupWizard
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

class TableLookupWizard extends Widget
{
    /**
     * Submit user input.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Check if there are already id's stored stored.
     *
     * @var bool
     */
    protected $blnHasValues = false;

    /**
     * Check if this is an ajax request.
     *
     * @var bool
     */
    protected $blnIsAjaxRequest = false;

    /**
     * SQL search operator.
     *
     * @var string
     */
    protected $strOperator = ' OR ';

    /**
     * Javascript (Ajax) fallback.
     *
     * @var bool
     */
    protected $blnEnableFallback = true;

    /**
     * Enable drag n drop sorting.
     *
     * @var bool
     */
    protected $blnEnableSorting = false;

    /**
     * Search fields.
     *
     * @var array
     */
    protected $arrSearchFields = [];

    /**
     * List fields.
     *
     * @var array
     */
    protected $arrListFields = [];

    /**
     * JOIN statements.
     *
     * @var array
     */
    protected $arrJoins = [];

    /**
     * Query Procedure.
     *
     * @var array
     */
    protected $arrQueryProcedure = [];

    /**
     * Query Values.
     *
     * @var array
     */
    protected $arrQueryValues = [];

    /**
     * WHERE Procedure.
     *
     * @var array
     */
    protected $arrWhereProcedure = [];

    /**
     * WHERE Values.
     *
     * @var array
     */
    protected $arrWhereValues = [];

    /**
     * Limit.
     */
    protected $intLimit = 30;

    /**
     * Custom label.
     *
     * @var array
     */
    protected $customLabels = [];

    /**
     * Store config for ajax upload.
     *
     * @param string $strKey
     * @param mixed  $varValue
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'searchFields':
                $arrFields = [];
                foreach ($varValue as $k => $v) {
                    if (is_numeric($k)) {
                        $arrFields[] = $v;
                    } else {
                        $arrFields[] = $v.' AS '.$k;
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
            case 'enableSorting':
                if ('checkbox' !== $this->fieldType) {
                    throw new RuntimeException('You cannot make a non-checkbox field type sortable!');
                }

                $this->blnEnableSorting = $varValue ? true : false;
                break;
            case 'joins':
                $this->arrJoins = $varValue;
                break;
            case 'customLabels':
                $this->customLabels = (array) $varValue;
                break;
            case 'sqlLimit':
                $this->intLimit = (int) $varValue;
                break;
            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }

    /**
     * Validate input and set value.
     *
     * @param mixed $varInput
     *
     * @return mixed Input
     */
    public function validator($varInput)
    {
        if ($this->mandatory && !$varInput) {
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
        }

        return $varInput;
    }

    /**
     * Generate the widget and return it as string.
     *
     * @return string
     */
    public function generate()
    {
        $blnNoAjax = \Input::get('noajax');
        $arrIds = deserialize($this->varValue, true);

        if (!$arrIds[0]) {
            $arrIds = [0];
        } else {
            $this->blnHasValues = true;
        }

        $this->blnIsAjaxRequest = \Input::get('tableLookupWizard') === $this->strId;

        // Ensure search and list fields have correct aliases
        $this->ensureColumnAliases($this->arrSearchFields);
        $this->ensureColumnAliases($this->arrListFields);

        // Ajax call
        if ($this->blnIsAjaxRequest) {
            // Clean buffer
            while (ob_end_clean());

            $this->prepareSelect();
            $this->prepareJoins();
            $this->prepareWhere();
            $this->prepareOrderBy();
            $this->prepareGroupBy();

            $strBuffer = $this->getBody();
            $response = new \Haste\Http\Response\JsonResponse([
                'content' => $strBuffer,
                'token' => REQUEST_TOKEN,
            ]);

            $response->send();
        }

        $this->prepareSelect();
        $this->prepareJoins();

        // Add preselect to WHERE statement
        $this->arrWhereProcedure[] = $this->foreignTable.'.id IN ('.implode(',', $arrIds).')';

        $this->prepareWhere();
        $this->prepareOrderBy();
        $this->prepareGroupBy();

        $objTemplate = new \BackendTemplate($this->customTpl ?: 'be_widget_tablelookupwizard');
        $objTemplate->noAjax = $blnNoAjax;
        $objTemplate->strId = $this->strId;
        $objTemplate->fieldType = $this->fieldType;
        $objTemplate->fallbackEnabled = $this->blnEnableFallback;
        $objTemplate->noAjaxUrl = $this->addToUrl('noajax=1');
        $objTemplate->listFields = $this->arrListFields;
        $objTemplate->colspan = \count($this->arrListFields) + (int) $this->blnEnableSorting;
        $objTemplate->searchLabel = !$this->searchLabel ? $GLOBALS['TL_LANG']['MSC']['searchLabel'] : $this->searchLabel;
        $objTemplate->columnLabels = $this->getColumnLabels();
        $objTemplate->hasValues = $this->blnHasValues;
        $objTemplate->enableSorting = $this->blnEnableSorting;
        $objTemplate->body = $this->getBody();

        return $objTemplate->parse();
    }

    /**
     * Renders the table body.
     *
     * @return string
     */
    public function getBody()
    {
        $objTemplate = new \BackendTemplate($this->customContentTpl ?: 'be_widget_tablelookupwizard_content');
        $arrResults = [];
        $blnQuery = true;

        if ($this->blnIsAjaxRequest && !\Input::get('keywords')) {
            $blnQuery = false;
        }

        if ($blnQuery) {
            $arrResults = $this->getResults();

            \Haste\Generator\RowClass::withKey('rowClass')
                ->addCustom('row')
                ->addCount('row_')
                ->addFirstLast('row_')
                ->addEvenOdd('row_')
                ->applyTo($arrResults);
        }

        if (!empty($arrResults)) {
            $objTemplate->hasResults = true;
        }

        // Add the message about more results than the limit
        if ($this->blnIsAjaxRequest && \Input::get('keywords') && \count($arrResults) > $this->intLimit) {
            $arrResults = \array_slice($arrResults, 0, $this->intLimit);

            $objTemplate->moreResults = true;
            $objTemplate->moreResultsMessage = $GLOBALS['TL_LANG']['MSC']['tlwMoreResults'];
        }

        // Determine the results message based on keywords availability
        if (\strlen(\Input::get('keywords'))) {
            $noResultsMessage = sprintf($GLOBALS['TL_LANG']['MSC']['tlwNoResults'], \Input::get('keywords'));
        } else {
            $noResultsMessage = $GLOBALS['TL_LANG']['MSC']['tlwNoValue'];
        }

        $objTemplate->results = $arrResults;
        $objTemplate->colspan = \count($this->arrListFields) + 1 + (int) $this->blnEnableSorting;
        $objTemplate->noResultsMessage = $noResultsMessage;
        $objTemplate->fieldType = $this->fieldType;
        $objTemplate->isAjax = $this->blnIsAjaxRequest;
        $objTemplate->strId = $this->strId;
        $objTemplate->enableSorting = $this->blnEnableSorting;
        $objTemplate->dragHandleIcon = 'system/themes/'.\Backend::getTheme().'/images/drag.gif';

        return $objTemplate->parse();
    }

    /**
     * Get the results.
     *
     * @return array
     */
    protected function getResults()
    {
        $arrResults = [];
        $objStatement = \Database::getInstance()->prepare(implode(' ', $this->arrQueryProcedure));

        // Apply the limit only for the search results and not the current values
        if ($this->blnIsAjaxRequest && \Input::get('keywords')) {
            $objStatement->limit($this->intLimit + 1);
        }

        $objResults = $objStatement->execute($this->arrQueryValues);

        while ($objResults->next()) {
            $arrRow = $objResults->row();
            $strKey = $arrRow[$this->foreignTable.'_id'];
            $arrResults[$strKey]['rowId'] = $arrRow[$this->foreignTable.'_id'];
            $arrResults[$strKey]['rawData'] = $arrRow;

            // Mark checked if not ajax call
            if (!$this->blnIsAjaxRequest) {
                $arrResults[$strKey]['isChecked'] = $this->optionChecked($arrRow[$this->foreignTable.'_id'], $this->varValue);
            }

            foreach ($this->arrListFields as $strField) {
                list($strTable, $strColumn) = explode('.', $strField);
                $strFieldKey = str_replace('.', '_', $strField);
                $arrResults[$strKey]['formattedData'][$strFieldKey] = \Haste\Util\Format::dcaValue($strTable, $strColumn, $arrRow[$strFieldKey]);
            }
        }

        return $arrResults;
    }

    /**
     * Prepares the SELECT statement.
     */
    protected function prepareSelect()
    {
        $arrSelects = [$this->foreignTable.'.id AS '.$this->foreignTable.'_id'];

        foreach ($this->arrListFields as $strField) {
            $arrSelects[] = $strField.' AS '.str_replace('.', '_', $strField);
        }

        // Build SQL statement
        $this->arrQueryProcedure[] = 'SELECT '.implode(', ', $arrSelects);
        $this->arrQueryProcedure[] = 'FROM '.$this->foreignTable;
    }

    /**
     * Prepares the JOIN statement.
     */
    protected function prepareJoins()
    {
        if (!empty($this->arrJoins)) {
            foreach ($this->arrJoins as $k => $v) {
                $this->arrQueryProcedure[] = sprintf('%s %s ON %s.%s = %s.%s', $v['type'], $k, $k, $v['jkey'], $this->foreignTable, $v['fkey']);
            }
        }
    }

    /**
     * Prepares the WHERE statement.
     */
    protected function prepareWhere()
    {
        $arrKeywords = trimsplit(' ', \Input::get('keywords'));
        $varData = \Input::get($this->strName);

        // Handle keywords
        foreach ($arrKeywords as $strKeyword) {
            if (!$strKeyword) {
                continue;
            }
            $this->arrWhereProcedure[] = '('.implode(' LIKE ? OR ', $this->arrSearchFields).' LIKE ?)';
            $this->arrWhereValues = array_merge($this->arrWhereValues, array_fill(0, \count($this->arrSearchFields), '%'.$strKeyword.'%'));
        }

        // Filter those that have already been chosen
        if ('checkbox' === $this->fieldType && \is_array($varData) && !empty($varData)) {
            $this->arrWhereProcedure[] = $this->foreignTable.'.id NOT IN ('.implode(',', array_map('intval', $varData)).')';
        } elseif ('radio' === $this->fieldType && '' !== $varData) {
            $this->arrWhereProcedure[] = $this->foreignTable.'.id!='.(int) $varData;
        }

        // If custom WHERE is set, add it to the statement
        if ($this->sqlWhere) {
            $this->arrWhereProcedure[] = $this->sqlWhere;
        }

        if (!empty($this->arrWhereProcedure)) {
            $strWhere = implode(' AND ', $this->arrWhereProcedure);
            $this->arrQueryProcedure[] = 'WHERE '.$strWhere;
            $this->arrQueryValues = array_merge($this->arrQueryValues, $this->arrWhereValues);
        }
    }

    /**
     * Prepares the ORDER BY statement.
     */
    protected function prepareOrderBy()
    {
        if ($this->sqlOrderBy && $this->blnEnableSorting) {
            throw new RuntimeException('You cannot use "enableSorting" and a custom "ORDER BY" query part at the same time!');
        }

        if ($this->sqlOrderBy) {
            $this->arrQueryProcedure[] = "ORDER BY {$this->sqlOrderBy}";
        }

        // The sorting of the values has only be done on the initial (= not the ajax) request
        if ($this->blnEnableSorting && !$this->blnIsAjaxRequest) {
            $this->arrQueryProcedure[] = 'ORDER BY '.\Database::getInstance()->findInSet($this->foreignTable.'.id', $this->value);
        }
    }

    /**
     * Prepares the GROUP BY statement.
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
     * column of the foreignTable.
     *
     * @param   array
     */
    protected function ensureColumnAliases(&$arrFields)
    {
        foreach ($arrFields as $k => $strField) {
            if (false !== strpos($strField, '.')) {
                continue;
            }

            $arrFields[$k] = $this->foreignTable.'.'.$strField;
        }
    }

    /**
     * Get formatted column labels.
     *
     * @return array
     */
    protected function getColumnLabels()
    {
        $arrLabels = [];
        $count = 0;

        foreach ($this->arrListFields as $strField) {
            // Use a custom label
            if (\count($this->customLabels) > 0) {
                $label = $this->customLabels[$count++];
            } else {
                // Get the label from DCA
                list($strTable, $strColumn) = explode('.', $strField);
                $label = \Haste\Util\Format::dcaLabel($strTable, $strColumn);
            }

            $arrLabels[standardize($strField)]['label'] = $label;
        }

        \Haste\Generator\RowClass::withKey('rowClass')
            ->addCustom('row')
            ->addCount('row_')
            ->addFirstLast('row_')
            ->addEvenOdd('row_')
            ->applyTo($arrLabels);

        return $arrLabels;
    }
}
