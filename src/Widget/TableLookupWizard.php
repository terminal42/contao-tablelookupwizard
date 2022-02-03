<?php

declare(strict_types=1);

namespace Terminal42\TableLookupWizardBundle\Widget;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Controller;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Database;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Haste\Generator\RowClass;
use Haste\Util\Format;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @property string $fieldType
 * @property string $foreignTable
 * @property string $sqlWhere
 * @property string $sqlOrderBy
 * @property string $sqlGroupBy
 * @property string $searchLabel
 * @property string $customContentTpl
 */
class TableLookupWizard extends Widget
{
    protected $blnSubmitInput = true;
    protected $strTemplate = 'be_widget';

    /**
     * Check if there are already id's stored stored.
     */
    protected bool $blnHasValues = false;

    /**
     * Check if this is an ajax request.
     */
    protected bool $blnIsAjaxRequest = false;

    /**
     * SQL search operator.
     */
    protected string $strOperator = ' OR ';

    /**
     * Javascript (Ajax) fallback.
     */
    protected bool $blnEnableFallback = true;

    /**
     * Enable drag n drop sorting.
     */
    protected bool $blnEnableSorting = false;

    protected array $arrSearchFields = [];
    protected array $arrListFields = [];
    protected array $arrJoins = [];
    protected array $arrQueryProcedure = [];
    protected array $arrQueryValues = [];
    protected array $arrWhereProcedure = [];
    protected array $arrWhereValues = [];
    protected int $intLimit = 30;
    protected array $customLabels = [];

    public function __set($strKey, $varValue): void
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
                Controller::loadDataContainer($varValue);
                System::loadLanguageFile($varValue);
                parent::__set($strKey, $varValue);
                break;

            case 'matchAllKeywords':
                $this->strOperator = $varValue ? ' AND ' : ' OR ';
                break;

            case 'mandatory':
                $this->arrConfiguration['mandatory'] = (bool) $varValue;
                break;

            case 'disableJavascriptFallback':
                $this->blnEnableFallback = !$varValue;
                break;

            case 'enableSorting':
                if ('checkbox' !== $this->fieldType) {
                    throw new \RuntimeException('You cannot make a non-checkbox field type sortable!');
                }

                $this->blnEnableSorting = (bool) $varValue;
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

    public function validator($varInput)
    {
        if ($this->mandatory && !$varInput) {
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
        }

        return $varInput;
    }

    public function generate(): string
    {
        $blnNoAjax = Input::get('noajax');
        $arrIds = StringUtil::deserialize($this->varValue, true);

        if (!$arrIds[0]) {
            $arrIds = [0];
        } else {
            $this->blnHasValues = true;
        }

        $this->blnIsAjaxRequest = Input::get('tableLookupWizard') === $this->strId;

        // Ensure search and list fields have correct aliases
        $this->ensureColumnAliases($this->arrSearchFields);
        $this->ensureColumnAliases($this->arrListFields);

        if ($this->blnIsAjaxRequest) {
            $this->prepareSelect();
            $this->prepareJoins();
            $this->prepareWhere();
            $this->prepareOrderBy();
            $this->prepareGroupBy();

            throw new ResponseException(new JsonResponse(['content' => $this->getBody(), 'token' => REQUEST_TOKEN]));
        }

        $this->prepareSelect();
        $this->prepareJoins();

        // Add preselect to WHERE statement
        $this->arrWhereProcedure[] = $this->foreignTable.'.id IN ('.implode(',', $arrIds).')';

        $this->prepareWhere();
        $this->prepareOrderBy();
        $this->prepareGroupBy();

        /** @var BackendTemplate&object $objTemplate */
        $objTemplate = new BackendTemplate($this->customTpl ?: 'be_widget_tablelookupwizard');
        $objTemplate->noAjax = $blnNoAjax;
        $objTemplate->strId = $this->strId;
        $objTemplate->fieldType = $this->fieldType;
        $objTemplate->fallbackEnabled = $this->blnEnableFallback;
        $objTemplate->noAjaxUrl = Controller::addToUrl('noajax=1');
        $objTemplate->listFields = $this->arrListFields;
        $objTemplate->colspan = \count($this->arrListFields) + (int) $this->blnEnableSorting;
        $objTemplate->searchLabel = !$this->searchLabel ? $GLOBALS['TL_LANG']['MSC']['searchLabel'] : $this->searchLabel;
        $objTemplate->columnLabels = $this->getColumnLabels();
        $objTemplate->hasValues = $this->blnHasValues;
        $objTemplate->enableSorting = $this->blnEnableSorting;
        $objTemplate->body = $this->getBody();

        return $objTemplate->parse();
    }

    public function getBody(): string
    {
        /** @var BackendTemplate&object $objTemplate */
        $objTemplate = new BackendTemplate($this->customContentTpl ?: 'be_widget_tablelookupwizard_content');
        $arrResults = [];
        $blnQuery = true;

        if ($this->blnIsAjaxRequest && !Input::get('keywords')) {
            $blnQuery = false;
        }

        if ($blnQuery) {
            $arrResults = $this->getResults();

            RowClass::withKey('rowClass')
                ->addCustom('row')
                ->addCount('row_')
                ->addFirstLast('row_')
                ->addEvenOdd('row_')
                ->applyTo($arrResults)
            ;
        }

        if (!empty($arrResults)) {
            $objTemplate->hasResults = true;
        }

        // Add the message about more results than the limit
        if ($this->blnIsAjaxRequest && Input::get('keywords') && \count($arrResults) > $this->intLimit) {
            $arrResults = \array_slice($arrResults, 0, $this->intLimit);

            $objTemplate->moreResults = true;
            $objTemplate->moreResultsMessage = $GLOBALS['TL_LANG']['MSC']['tlwMoreResults'];
        }

        // Determine the results message based on keywords availability
        if (!empty(Input::get('keywords'))) {
            $noResultsMessage = sprintf($GLOBALS['TL_LANG']['MSC']['tlwNoResults'], Input::get('keywords'));
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
        $objTemplate->dragHandleIcon = 'system/themes/'.Backend::getTheme().'/images/drag.gif';

        return $objTemplate->parse();
    }

    protected function getResults(): array
    {
        $arrResults = [];
        $objStatement = Database::getInstance()->prepare(implode(' ', $this->arrQueryProcedure));

        // Apply the limit only for the search results and not the current values
        if ($this->blnIsAjaxRequest && Input::get('keywords')) {
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
                $arrResults[$strKey]['isChecked'] = self::optionChecked($arrRow[$this->foreignTable.'_id'], $this->varValue);
            }

            foreach ($this->arrListFields as $strField) {
                [$strTable, $strColumn] = explode('.', $strField);
                $strFieldKey = str_replace('.', '_', $strField);
                $arrResults[$strKey]['formattedData'][$strFieldKey] = Format::dcaValue($strTable, $strColumn, $arrRow[$strFieldKey]);
            }
        }

        return $arrResults;
    }

    /**
     * Prepares the SELECT statement.
     */
    protected function prepareSelect(): void
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
    protected function prepareJoins(): void
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
    protected function prepareWhere(): void
    {
        $arrKeywords = StringUtil::trimsplit(' ', Input::get('keywords'));
        $varData = Input::get($this->strName);

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
    protected function prepareOrderBy(): void
    {
        if ($this->sqlOrderBy && $this->blnEnableSorting) {
            throw new \RuntimeException('You cannot use "enableSorting" and a custom "ORDER BY" query part at the same time!');
        }

        if ($this->sqlOrderBy) {
            $this->arrQueryProcedure[] = "ORDER BY $this->sqlOrderBy";
        }

        // The sorting of the values has only be done on the initial (= not the ajax) request
        if ($this->blnEnableSorting && !$this->blnIsAjaxRequest) {
            $this->arrQueryProcedure[] = 'ORDER BY '.Database::getInstance()->findInSet($this->foreignTable.'.id', $this->value);
        }
    }

    /**
     * Prepares the GROUP BY statement.
     */
    protected function prepareGroupBy(): void
    {
        if ($this->sqlGroupBy) {
            $this->arrQueryProcedure[] = "GROUP BY $this->sqlGroupBy";
        }
    }

    /**
     * Ensures that the columns are all aliased
     * If there's no alias passed in, it will automatically treat it as a
     * column of the foreignTable.
     *
     * @param array $arrFields
     */
    protected function ensureColumnAliases(array &$arrFields): void
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
     */
    protected function getColumnLabels(): array
    {
        $arrLabels = [];
        $count = 0;

        foreach ($this->arrListFields as $strField) {
            // Use a custom label
            if (\count($this->customLabels) > 0) {
                $label = $this->customLabels[$count++];
            } else {
                // Get the label from DCA
                [$strTable, $strColumn] = explode('.', $strField);
                $label = Format::dcaLabel($strTable, $strColumn);
            }

            $arrLabels[StringUtil::standardize($strField)]['label'] = $label;
        }

        RowClass::withKey('rowClass')
            ->addCustom('row')
            ->addCount('row_')
            ->addFirstLast('row_')
            ->addEvenOdd('row_')
            ->applyTo($arrLabels)
        ;

        return $arrLabels;
    }
}
