<?php
/**
 Admin Page Framework v3.5.12 by Michael Uno
 Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
 <http://en.michaeluno.jp/admin-page-framework>
 Copyright (c) 2013-2015, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT>
 */
class AdminPageFramework_FieldType_text extends AdminPageFramework_FieldType {
    public $aFieldTypeSlugs = array('text', 'password', 'date', 'datetime', 'datetime-local', 'email', 'month', 'search', 'tel', 'url', 'week',);
    protected $aDefaultKeys = array('attributes' => array('maxlength' => 400,),);
    protected function getStyles() {
        return <<<CSSRULES
/* Text Field Type */
.admin-page-framework-field.admin-page-framework-field-text > .admin-page-framework-input-label-container {
    vertical-align: top; 
}

.admin-page-framework-field.admin-page-framework-field-text > .admin-page-framework-input-label-container.admin-page-framework-field-text-multiple-labels {
    /* When the browser screen width gets narrow, avoid the inputs getting placed next each other. */
    display: block;
}
CSSRULES;
        
    }
    protected function getField($aField) {
        $_aOutput = array();
        foreach (( array )$aField['label'] as $_sKey => $_sLabel) {
            $_aOutput[] = $this->_getFieldOutputByLabel($_sKey, $_sLabel, $aField);
        }
        $_aOutput[] = "<div class='repeatable-field-buttons'></div>";
        return implode('', $_aOutput);
    }
    private function _getFieldOutputByLabel($sKey, $sLabel, $aField) {
        $_bIsArray = is_array($aField['label']);
        $_sClassSelector = $_bIsArray ? 'admin-page-framework-field-text-multiple-labels' : '';
        $_sLabel = $this->getElementByLabel($aField['label'], $sKey, $_bIsArray);
        $aField['value'] = $this->getElementByLabel($aField['value'], $sKey, $_bIsArray);
        $_aInputAttributes = $_bIsArray ? array('name' => $aField['attributes']['name'] . "[{$sKey}]", 'id' => $aField['attributes']['id'] . "_{$sKey}", 'value' => $aField['value'],) + $aField['attributes'] : $aField['attributes'];
        $_aOutput = array($this->getElementByLabel($aField['before_label'], $sKey, $_bIsArray), "<div class='admin-page-framework-input-label-container {$_sClassSelector}'>", "<label for='" . $_aInputAttributes['id'] . "'>", $this->getElementByLabel($aField['before_input'], $sKey, $_bIsArray), $_sLabel ? "<span class='admin-page-framework-input-label-string' style='min-width:" . $this->sanitizeLength($aField['label_min_width']) . ";'>" . $_sLabel . "</span>" : '', "<input " . $this->generateAttributes($_aInputAttributes) . " />", $this->getElementByLabel($aField['after_input'], $sKey, $_bIsArray), "</label>", "</div>", $this->getElementByLabel($aField['after_label'], $sKey, $_bIsArray),);
        return implode('', $_aOutput);
    }
}