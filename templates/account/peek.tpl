<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmTwitterAcct">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="twitter">
<input type="hidden" name="action" value="savePeekPopup">
<input type="hidden" name="id" value="{$account->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'dao.twitter_account.screen_name'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				{$account->screen_name}
			</td>
		</tr>
	</table>
</fieldset>

{*
{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}
*}

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete this account?</legend>
	
	<button type="button" class="red" onclick="$frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$fieldset=$(this).closest('fieldset').fadeOut();$fieldset.siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="toolbar">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmTwitterAcct','{$view_id}',false,'example_object_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $account->id}<button type="button" onclick="$toolbar=$(this).closest('div.toolbar').fadeOut();$toolbar.siblings('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'wgm.twitter.common.account'|devblocks_translate|capitalize}");
		$(this).find('input:text:first').select().focus();
	});
</script>