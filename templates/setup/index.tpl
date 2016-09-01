<div style="float:left;">
	<h2>{'wgm.twitter.common'|devblocks_translate}</h2>
</div>

<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

<br clear="all">

{* [TODO] Move this to an 'add' popup on the worklist *}
<fieldset>
	<legend>Twitter Application</legend>
	
	<form action="javascript:;" method="post" id="frmSetupTwitter" onsubmit="return false;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="twitter">
	<input type="hidden" name="action" value="saveJson">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
					<b>Consumer key:</b>
				</td>
				<td>
					<b>Consumer secret:</b>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" name="consumer_key" value="{$params.consumer_key}" size="45">
				</td>
				<td>
					<input type="password" name="consumer_secret" value="" size="45">
				</td>
				<td>
					<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
				</td>
			</tr>
		</table>
	
		<div class="status"></div>
	</form>
	
	<form action="{devblocks_url}ajax.php{/devblocks_url}" method="post" id="frmAuthTwitter" style="margin-top:10px;display: {if $params.consumer_key && $params.consumer_secret}block{else}none{/if}">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="twitter">
	<input type="hidden" name="action" value="auth">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		<input type="image" src="{devblocks_url}c=resource&p=wgm.twitter&f=sign_in_with_twitter.png{/devblocks_url}">
	</form>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script type="text/javascript">
$(function() {
	$('#frmSetupTwitter BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupTwitter','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupTwitter div.status',$o.error);
					$('#frmAuthTwitter').fadeOut();
				} else {
					Devblocks.showSuccess('#frmSetupTwitter div.status',$o.message);
					$('#frmAuthTwitter').fadeIn();
				}
			});
		})
	;
});
</script>