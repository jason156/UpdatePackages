{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
{assign var="FIELD_INFO" value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_MODEL->getFieldInfo()))}
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
<div class="input-append">
	<input id="{$MODULE}_editView_fieldName_{$FIELD_MODEL->get('name')}" type="number" title="{vtranslate($FIELD_MODEL->get('label'))}" class="input-medium" min="0" max="100" data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" name="{$FIELD_MODEL->getFieldName()}"
	value="{$FIELD_MODEL->get('fieldvalue')}" data-fieldinfo='{$FIELD_INFO}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} step="any" {if $FIELD_MODEL->get('displaytype') == 10}readonly="readonly"{/if}/><span class="add-on">%</span>
</div>
{/strip}