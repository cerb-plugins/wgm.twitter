<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmTwitterMessage">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="twitter_message">
<input type="hidden" name="action" value="savePeekPopup">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<img src="{$message->user_profile_image_url}" style="margin-right:5px;" width="48" height="48">
		</td>
		<td width="99%" valign="top">
			<b class="subject" title="{$message->user_name}">{$message->user_screen_name}</b> 
			{$message->content|devblocks_hyperlinks nofilter}
			<abbr style="font-size:90%;" title="{$message->created_date|devblocks_date}">{$message->created_date|devblocks_prettytime}</abbr>
		</td>
	</tr>
</table>

<fieldset class="peek">
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'dao.twitter_message.is_closed'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<label><input type="radio" name="is_closed" value="1" {if $message->is_closed}checked="checked"{/if}> {'common.yes'|devblocks_translate|lower}</label>
				<label><input type="radio" name="is_closed" value="0" {if !$message->is_closed}checked="checked"{/if}> {'common.no'|devblocks_translate|lower}</label>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><label><b>Reply:</b> <input type="checkbox" name="do_reply" value="1"></label></td>
			<td width="99%" valign="top">
				<textarea name="reply" rows="5" cols="80" style="width:98%;height:50px;display:none;" spellcheck="false"></textarea>
			</td>
		</tr>
	</table>
</fieldset>


{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context='cerberusweb.contexts.twitter.message' context_id=$message->id}

<div class="toolbar">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmTwitterMessage','{$view_id}',false,'twitter_message_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		{$account = $accounts.{$message->account_id}}
		$(this).dialog('option','title',"{'wgm.twitter.common.message'|devblocks_translate|capitalize|escape:'javascript' nofilter}{if !empty($account)} @{$account->screen_name|escape:'javascript' nofilter}{/if}");
		
		var $txt = $(this).find('textarea:first');
		$txt.autosize();
		
		$(this).find('input:checkbox[name=do_reply]').click(function(e) {
			if($(this).is(':checked')) {
				$txt.show();
				$txt.focus();
				$txt.val('');
				$txt.insertAtCursor('@{$message->user_screen_name|escape:'javascript'} ');
			} else {
				$txt.hide().blur();
			}
		});
	});
</script>