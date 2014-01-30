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
     * Allowed row ids
     * @var array
     */
    protected $arrIds = false;

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
            case 'allowedIds':
                $this->arrIds = deserialize($varValue);
                break;

            case 'searchFields':
                $arrFields = array();
                foreach ($varValue as $k => $v) {
                    if (is_numeric($k)) {
                        $arrFields[] = $v;
                    } else {
                        $arrFields[] = $v . ' AS ' . $k;
                    }
                }
                parent::__set($strKey, $arrFields);
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
     * @return string
     */
    public function generate()
    {
        if (\Input::get('tableLookupWizard') == $this->strId) {
            while (ob_end_clean());
            $strBuffer = $this->generateAjax();
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

        if (!\Input::get('noajax')) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/tablelookupwizard/assets/tablelookup.min.js';
        }

        $arrIds = deserialize($this->varValue, true);

        if ($arrIds[0] == '') {
            $arrIds = array(0);
        }

        $strReset = '';
        if ($this->fieldType == 'radio') {
            $strReset = '
    <tr class="reset">
      <td><input type="radio" class="radio" name="' . $this->strId . '" id="reset_' . $this->strId . '" value=""' . ($arrIds[0] == 0 ? ' checked="checked"' : '') . ' /></td>
      <td colspan="' . (count($this->listFields)) . '"><label for="reset_' . $this->strId . '" class="tl_change_selected">' . $GLOBALS['TL_LANG']['MSC']['resetSelected'] . '</label></td>
    </tr>';
        }

        // User has javascript disabled and clicked on link
        if ($this->blnEnableFallback && \Input::get('noajax')) {
            $arrResults = \Database::getInstance()->execute("SELECT id, " . implode(', ', $this->listFields) . " FROM {$this->foreignTable}" . (strlen($this->sqlWhere) ? " WHERE {$this->sqlWhere}" : '') . " ORDER BY id=" . implode(' DESC, id=', $arrIds) . " DESC")->fetchAllAssoc();
            $strResults = $this->listResults($arrResults) . $strReset;
        } else {
            $arrResults = \Database::getInstance()->execute("SELECT id, " . implode(', ', $this->listFields) . " FROM {$this->foreignTable} WHERE id IN (" . implode(',', $arrIds) . ")" . (strlen($this->sqlWhere) ? " AND {$this->sqlWhere}" : ''))->fetchAllAssoc();
            $strResults = $this->listResults($arrResults);

            $strResults .= '
    <tr class="jserror">
      <td colspan="' . (count($this->listFields) + 1) . '">
        <p class="tl_error">' . $GLOBALS['TL_LANG']['MSC']['tlwNoJs'] . '</p>';

            if ($this->blnEnableFallback) {
                $strResults .= '<a href="' . $this->addToUrl('noajax=1') . '">' . $GLOBALS['TL_LANG']['MSC']['tlwJsAlternative'] . '</a>';
            }

            $strResults .= '</td>
    </tr>' . $strReset . '
    <tr class="search" style="display:none">
      <td colspan="' . (count($this->listFields) + 1) . '"><label for="ctrl_' . $this->strId . '_search">' . ($this->searchLabel == '' ? $GLOBALS['TL_LANG']['MSC']['searchLabel'] : $this->searchLabel) . ':</label> <input type="text" id="ctrl_' . $this->strId . '_search" name="keywords" class="tl_text" autocomplete="off" /></td>
    </tr>';
        }


        $strBuffer = '
<table cellspacing="0" cellpadding="0" id="ctrl_' . $this->strId . '" class="tl_tablelookupwizard tl_listing" summary="Table data">
  <thead>
    <tr>
      <th class="head_0 col_first tl_folder_tlist">&nbsp;</th>';

        $i = 1;
        foreach ($this->listFields as $k => $v) {
            $field = is_numeric($k) ? $v : $k;

            $strBuffer .= '
        <th class="head_' . $i . ($i == count($this->listFields) ? ' col_last' : '') . ' tl_folder_tlist">' . $this->formatLabel($this->foreignTable, $field) . '</th>';

            $i++;
        }

        $strBuffer .= '
    </tr>
  </thead>
  <tbody>
' . $strResults . '
  </tbody>
</table>';

        if (!\Input::get('noajax')) {
            $strBuffer .= '
<script>
window.addEvent(\'domready\', function() {
  new TableLookupWizard(\'' . $this->strId . '\');
});
</script>';
        }

        return $strBuffer;
    }


    public function generateAjax()
    {
        // Prepare query
        $this->prepareQuery();

        $strProcedure = implode(' ', $this->arrQueryProcedure);

        // Get results
        $arrResults = \Database::getInstance()
            ->prepare($strProcedure)
            ->execute($this->arrQueryValues)
            ->fetchAllAssoc();

        $strBuffer = $this->listResults($arrResults, true);

        // @todo !==
        if (!strlen($strBuffer))
            return '<tr class="found empty"><td colspan="' . (count($this->listFields) + 1) . '">' . sprintf($GLOBALS['TL_LANG']['MSC']['tlwNoResults'], \Input::get('keywords')) . '</td></tr>';

        return $strBuffer;
    }

    /**
     * Prepares the SQL Query
     */
    protected function prepareQuery()
    {
        // Build SQL statement
        $this->arrQueryProcedure[] = "SELECT {$this->foreignTable}.id, " . implode(', ', $this->listFields);
        $this->arrQueryProcedure[] = "FROM {$this->foreignTable}";

        // Handle joins
        if (!empty($this->arrJoins)) {
            foreach ($this->arrJoins as $k => $v) {
                $k = (is_numeric($k) ? ('j_' . $k) : $k);
                $this->arrQueryProcedure[] = sprintf("LEFT JOIN %s AS %s ON %s.%s = %s.%s", $v['table'], $k, $k, $v['jkey'], $this->foreignTable, $v['fkey']);
            }
        }

        // Prepare WHERE
        $this->prepareWhere();

        // If custom GROUP BY is set, add it to the statement
        if ($this->sqlGroupBy) {
            $this->arrQueryProcedure[] = "GROUP BY {$this->sqlGroupBy}";
        }
    }

    protected function prepareWhere()
    {
        $arrKeywords        = trimsplit(' ', \Input::get('keywords'));
        $varData            = \Input::get($this->strName);
        $arrWhereProcedure  = array();
        $arrWhereValues     = array();

        // Handle keywords
        foreach ($arrKeywords as $strKeyword) {
            if (!$strKeyword)
                continue;

            // @todo fix alias
            $arrWhereProcedure[]  = '(' . implode(' LIKE ? OR ', $this->searchFields) . ' LIKE ?)';
            $arrWhereValues       = array_merge($arrWhereValues, array_fill(0, count($this->searchFields), '%' . $strKeyword . '%'));
        }

        // Filter those that have already been chosen
        // @todo !emtpy()
        if ($this->fieldType == 'checkbox' && is_array($varData) && count($varData)) {
            $arrWhereProcedure[] = ") AND {$this->foreignTable}.id NOT IN (" . implode(',', $varData);
        } elseif ($this->fieldType == 'radio' && $varData != '') {
            $arrWhereProcedure[] = ") AND ({$this->foreignTable}.id!='$varData'";
        }

        // If custom WHERE is set, add it to the statement
        if ($this->sqlWhere !== '') {
            $arrWhereProcedure[] = "AND {$this->sqlWhere}";
        }

        if (!empty($arrWhereProcedure)) {
            $this->arrQueryProcedure[]  = 'WHERE';
            $this->arrQueryProcedure    = array_merge($this->arrQueryProcedure, $arrWhereProcedure);
            $this->arrQueryValues       = array_merge($this->arrQueryValues, $arrWhereValues);
        }
    }

    protected function listResults($arrResults, $blnAjax = false)
    {
        $c = 0;
        $strResults = '';

        foreach ($arrResults as $row) {
            if (is_array($this->arrIds) && !in_array($row['id'], $this->arrIds))
                continue;

            switch ($this->fieldType) {
                case 'radio':
                    $input = '<input type="radio" class="radio" name="' . $this->strId . '" value="' . $row['id'] . '"' . ($blnAjax ? '' : $this->optionChecked($row['id'], $this->varValue)) . ' />';
                    break;

                case 'checkbox':
                    $input = '<input type="checkbox" class="checkbox" name="' . $this->strId . '[]" value="' . $row['id'] . '"' . ($blnAjax ? '' : $this->optionChecked($row['id'], $this->varValue)) . ' />';
                    break;

                default:
                    $input = '';
                    break;
            }

            $strResults .= '
    <tr class="' . ($c % 2 ? 'even' : 'odd') . ($c == 0 ? ' row_first' : '') . ($blnAjax ? ' found' : '') . '">
      <td class="col_0 col_first">' . $input . '</td>';

            $i = 1;
            foreach ($row as $field => $value) {
                if ($field == 'id' && !in_array('id', $this->listFields))
                    continue;

                $strResults .= '
      <td class="col_' . $i . '">' . $this->formatValue($this->foreignTable, $field, $value) . '</td>';

                $i++;
            }

            $strResults .= '
    </tr>';

            $c++;
        }

        return $strResults;
    }


    /**
     * Format value (based on DC_Table::show(), Contao 2.9.0)
     * @param  mixed
     * @param  string
     * @param  string
     * @return string
     */
    protected function formatValue($table, $field, $value)
    {
        $value = deserialize($value);

        // Get field value
        if (strlen($GLOBALS['TL_DCA'][$table]['fields'][$field]['foreignKey'])) {
            $chunks = explode('.', $GLOBALS['TL_DCA'][$table]['fields'][$field]['foreignKey']);

            $objKey = \Database::getInstance()->execute("SELECT " . $chunks[1] . " AS value FROM " . $chunks[0] . " WHERE id IN (" . implode(',', array_map('intval', (array)$value)) . ")");

            return implode(', ', $objKey->fetchEach('value'));
        } elseif (is_array($value)) {
            foreach ($value as $kk => $vv) {
                if (is_array($vv)) {
                    $vals = array_values($vv);
                    $value[$kk] = $vals[0] . ' (' . $vals[1] . ')';
                }
            }

            return implode(', ', $value);
        } elseif ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['rgxp'] == 'date') {
            return \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $value);
        } elseif ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['rgxp'] == 'time') {
            return \Date::parse($GLOBALS['TL_CONFIG']['timeFormat'], $value);
        } elseif ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['rgxp'] == 'datim' || in_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'], array(5, 6, 7, 8, 9, 10)) || $field == 'tstamp') {
            return \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $value);
        } elseif ($GLOBALS['TL_DCA'][$table]['fields'][$field]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['multiple']) {
            return strlen($value) ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
        } elseif ($GLOBALS['TL_DCA'][$table]['fields'][$field]['inputType'] == 'textarea' && ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['allowHtml'] || $GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['preserveTags'])) {
            return specialchars($value);
        } elseif (is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'])) {
            return isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value]) ? ((is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value])) ? $GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value][0] : $GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value]) : $value;
        } elseif (is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['options'])) {
            return isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['options'][$value]) ? $GLOBALS['TL_DCA'][$table]['fields'][$field]['options'][$value] : $value;
        }

        // Call the list_value_callback ($value, $field)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strName]['list_value_callback'])) {
            $strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strName]['list_value_callback'][0];
            $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strName]['list_value_callback'][1];

            $this->import($strClass);
            $value = $this->$strClass->$strMethod($value, $field);
        }
        elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strName]['list_value_callback'])) {
            $value = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strName]['list_value_callback']($value, $field);
        }

        return $value;
    }


    /**
     * Format label (based on DC_Table::show(), Contao 2.9.0)
     * @param  mixed
     * @param  string
     * @param  string
     * @return string
     */
    protected function formatLabel($table, $field)
    {
        if (count($GLOBALS['TL_DCA'][$table]['fields'][$field]['label'])) {
            $label = is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['label']) ? $GLOBALS['TL_DCA'][$table]['fields'][$field]['label'][0] : $GLOBALS['TL_DCA'][$table]['fields'][$field]['label'];
        } else {
            $label = is_array($GLOBALS['TL_LANG']['MSC'][$field]) ? $GLOBALS['TL_LANG']['MSC'][$field][0] : $GLOBALS['TL_LANG']['MSC'][$field];
        }

        if (!strlen($label)) {
            $label = $field;
        }

        return $label;
    }
}