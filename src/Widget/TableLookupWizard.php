<?php

declare(strict_types=1);

namespace Terminal42\TableLookupWizardBundle\Widget;

use Codefog\HasteBundle\Formatter;
use Contao\Controller;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property bool   $multiple
 * @property bool   $searchMatchAll
 * @property string $foreignTable
 * @property string $sqlWhere
 * @property string $sqlOrderBy
 * @property string $sqlGroupBy
 * @property string $searchLabel
 * @property string $customRecordsTpl
 */
class TableLookupWizard extends Widget
{
    protected $blnSubmitInput = true;

    protected $strTemplate = 'be_widget';

    protected bool $isAjaxRequest = false;

    protected bool $isSortable = false;

    /**
     * @var array<string>
     */
    protected array $arrSearchFields = [];

    /**
     * @var array<string>
     */
    protected array $arrListFields = [];

    /**
     * @var array<array<string, string>>
     */
    protected array $arrJoins = [];

    /**
     * @var array<string>
     */
    protected array $arrQueryProcedure = [];

    /**
     * @var array<string>
     */
    protected array $arrQueryValues = [];

    /**
     * @var array<string>
     */
    protected array $arrWhereProcedure = [];

    /**
     * @var array<int|string>
     */
    protected array $arrWhereValues = [];

    protected int $intLimit = 30;

    /**
     * @var array<string>
     */
    protected array $headerFields = [];

    #[\Override]
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

            case 'mandatory':
                $this->arrConfiguration['mandatory'] = (bool) $varValue;
                break;

            case 'isSortable':
                if (!$this->multiple) {
                    throw new \RuntimeException('The field must allow multiple values to be sortable!');
                }

                $this->isSortable = (bool) $varValue;
                break;

            case 'sqlJoins':
                $this->arrJoins = $varValue;
                break;

            case 'headerFields':
                $this->headerFields = (array) $varValue;
                break;

            case 'sqlLimit':
                $this->intLimit = (int) $varValue;
                break;

            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }

    #[\Override]
    public function validator($varInput)
    {
        if ($this->mandatory && !$varInput) {
            $this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
        }

        return $varInput;
    }

    public function generate(): string
    {
        $this->isAjaxRequest = Input::get('tableLookupWizard') === $this->strId;

        // Ensure search and list fields have correct aliases
        $this->ensureColumnAliases($this->arrSearchFields);
        $this->ensureColumnAliases($this->arrListFields);

        if ($this->isAjaxRequest) {
            throw new ResponseException(new Response($this->getRecords()));
        }

        $ids = StringUtil::deserialize($this->varValue, true);

        if (empty($ids) || !\is_array($ids)) {
            $ids = [0];
        }

        return System::getContainer()->get('twig')->render(
            \sprintf('@Contao/%s.html.twig', $this->customTpl ?: 'backend/widget/tablelookupwizard'),
            [
                'css_class' => $this->strClass,
                'header_fields' => $this->getHeaderFields(),
                'id' => $this->strId,
                'multiple' => $this->multiple,
                'name' => $this->strName,
                'records' => $this->getRecords(where: [\sprintf('%s.id IN (%s)', $this->foreignTable, implode(',', $ids))]),
                'search_label' => $this->searchLabel,
                'sortable' => $this->isSortable,
            ],
        );
    }

    /**
     * @param array<string> $where
     */
    public function getRecords(array $where = []): string
    {
        $this->prepareSelect();
        $this->prepareJoins();
        $this->prepareWhere($where);
        $this->prepareOrderBy();
        $this->prepareGroupBy();

        $results = [];
        $keywords = Input::get('keywords');

        if (!$this->isAjaxRequest || $keywords) {
            $results = $this->getResults();
        }

        $hasMoreResults = false;

        // Add the message about more results than the limit
        if ($this->isAjaxRequest && $keywords && \count($results) > $this->intLimit) {
            $results = \array_slice($results, 0, $this->intLimit);
            $hasMoreResults = true;
        }

        return System::getContainer()->get('twig')->render(
            \sprintf('@Contao/%s.html.twig', $this->customRecordsTpl ?: 'backend/widget/tablelookupwizard_records'),
            [
                'button_add' => Image::getUrl('new.svg'),
                'button_remove' => Image::getUrl('delete.svg'),
                'has_more_results' => $hasMoreResults,
                'header_fields' => $this->getHeaderFields(),
                'id' => $this->strId,
                'input_name' => $this->multiple ? \sprintf('%s[]', $this->strName) : $this->strName,
                'name' => $this->strName,
                'results' => $results,
                'sortable' => $this->isSortable,
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getResults(): array
    {
        $arrResults = [];
        $objStatement = Database::getInstance()->prepare(implode(' ', $this->arrQueryProcedure));

        // Apply the limit only for the search results and not the current values
        if ($this->isAjaxRequest && Input::get('keywords')) {
            $objStatement->limit($this->intLimit + 1);
        }

        $dataContainer = null;

        // Prepare the data container for formatter
        if ($this->objDca instanceof DataContainer) {
            $dataContainer = $this->objDca;
        }

        $objResults = $objStatement->execute(...$this->arrQueryValues);

        while ($objResults->next()) {
            $arrRow = $objResults->row();
            $strKey = $arrRow[$this->foreignTable.'_id'];
            $arrResults[$strKey]['rowId'] = $arrRow[$this->foreignTable.'_id'];
            $arrResults[$strKey]['rawData'] = $arrRow;
            $arrResults[$strKey]['isChecked'] = false;

            // Mark checked if not ajax call
            if (!$this->isAjaxRequest) {
                $arrResults[$strKey]['isChecked'] = self::optionChecked($arrRow[$this->foreignTable.'_id'], $this->varValue);
            }

            foreach ($this->arrListFields as $strField) {
                [$strTable, $strColumn] = explode('.', $strField);
                $strFieldKey = str_replace('.', '_', $strField);
                $arrResults[$strKey]['formattedData'][$strFieldKey] = $this->getFormatter()->dcaValue($strTable, $strColumn, $arrRow[$strFieldKey], $dataContainer);
            }
        }

        return $arrResults;
    }

    /**
     * @return array<string>
     */
    protected function getSearchFields(): array
    {
        return $this->arrSearchFields ?: $this->arrListFields;
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
        foreach ($this->arrJoins as $k => $v) {
            $this->arrQueryProcedure[] = \sprintf('%s %s ON %s.%s = %s.%s', $v['type'], $k, $k, $v['joinKey'], $this->foreignTable, $v['foreignKey']);
        }
    }

    /**
     * Prepares the WHERE statement.
     *
     * @param array<string> $extra
     */
    protected function prepareWhere(array $extra = []): void
    {
        $arrKeywords = StringUtil::trimsplit(' ', Input::get('keywords'));
        $varData = Input::get($this->strName);

        if ([] !== $extra) {
            $this->arrWhereProcedure = [...$this->arrWhereProcedure, ...$extra];
        }

        // Handle keywords
        foreach ($arrKeywords as $strKeyword) {
            if (!$strKeyword) {
                continue;
            }

            $this->arrWhereProcedure[] = '('.implode(\sprintf(' LIKE ? %s ', $this->searchMatchAll ? 'AND' : 'OR'), $this->getSearchFields()).' LIKE ?)';
            $this->arrWhereValues = array_merge($this->arrWhereValues, array_fill(0, \count($this->getSearchFields()), '%'.$strKeyword.'%'));
        }

        // Filter those that have already been chosen
        if ($this->multiple && !empty($varData) && \is_array($varData)) {
            $this->arrWhereProcedure[] = $this->foreignTable.'.id NOT IN ('.implode(',', array_map(intval(...), $varData)).')';
        } elseif (!$this->multiple && '' !== $varData) {
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
        if ($this->sqlOrderBy && $this->isSortable) {
            throw new \RuntimeException('You cannot use "isSortable" and a custom "ORDER BY" query part at the same time!');
        }

        if ($this->sqlOrderBy) {
            $this->arrQueryProcedure[] = "ORDER BY $this->sqlOrderBy";
        }

        // The sorting of the values has only be done on the initial (= not the ajax) request
        if ($this->isSortable && !$this->isAjaxRequest) {
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
     * @param array{int|string, string} $arrFields
     */
    protected function ensureColumnAliases(array &$arrFields): void
    {
        foreach ($arrFields as $k => $strField) {
            if (str_contains((string) $strField, '.')) {
                continue;
            }

            $arrFields[$k] = $this->foreignTable.'.'.$strField;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaderFields(): array
    {
        $headerFields = [];
        $count = 0;

        foreach ($this->arrListFields as $field) {
            // Use a custom label
            if (\count($this->headerFields) > 0) {
                $label = $this->headerFields[$count++];
            } else {
                // Get the label from DCA
                [$table, $column] = explode('.', $field);
                $label = $this->getFormatter()->dcaLabel($table, $column);
            }

            $headerFields[StringUtil::standardize($field)] = $label;
        }

        return $headerFields;
    }

    protected function getFormatter(): Formatter
    {
        return System::getContainer()->get(Formatter::class);
    }
}
