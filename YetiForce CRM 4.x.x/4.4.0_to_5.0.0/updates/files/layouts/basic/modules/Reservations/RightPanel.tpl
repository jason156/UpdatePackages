{*<!-- {[The file is published on the basis of YetiForce Public License 3.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	{if !empty($ALL_ACTIVEGROUP_LIST) || !empty($ALL_ACTIVEUSER_LIST)}
		<div class="calendarUserList">
			<div class="row no-margin">
				<div class="col-12 marginTB10">
					<select class="select2 col-12" id="calendarUserList" multiple>
						{if !empty($ALL_ACTIVEUSER_LIST)}
							<optgroup label="{\App\Language::translate('LBL_USERS')}">
								{foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEUSER_LIST}
									<option class="ownerCBg_{$OWNER_ID} marginBottom5px" value="{$OWNER_ID}"
											{if \App\User::getCurrentUserId() eq $OWNER_ID} selected {/if}>
										{$OWNER_NAME}
									</option>
								{/foreach}
							</optgroup>
						{/if}
						{if !empty($ALL_ACTIVEGROUP_LIST)}
							<optgroup label="{\App\Language::translate('LBL_GROUPS')}">
								{foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEGROUP_LIST}
									<option class="ownerCBg_{$OWNER_ID} marginBottom5px" value="{$OWNER_ID}">
										{$OWNER_NAME}
									</option>
								{/foreach}
							</optgroup>
						{/if}
					</select>
				</div>
			</div>
		</div>
	{/if}
	{if !empty($ALL_ACTIVETYPES_LIST)}
		<div class="calendarTypeList">
			<div class="row no-margin">
				<div class="col-12 marginTB10">
					<select class="select2 form-control col-12" id="timecontrolTypes" name="timecontrolTypes" multiple>
						{foreach key=ITEM_ID item=ITEM from=$ALL_ACTIVETYPES_LIST}
							<option class="picklistCBg_OSSTimeControl_timecontrol_type_{$ITEM} marginBottom5px"
									value="{$ITEM_ID}">{\App\Language::translate($ITEM,$MODULE)}</option>
						{/foreach}
					</select>
				</div>
			</div>
		</div>
	{/if}
{/strip}
