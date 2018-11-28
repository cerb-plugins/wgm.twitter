<h2>{'wgm.twitter.common'|devblocks_translate}</h2>

<form action="javascript:;" method="post" id="frmSetupTwitter" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="twitter">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="status"></div>

<fieldset>
	<legend>Synchronization</legend>
	
	<b>Download @mentions as Twitter Message records for these connected accounts:</b><br>
	<button type="button" class="chooser-abstract" data-field-name="sync_account_ids[]" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-query="service:twitter"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{if $sync_accounts}
		{foreach from=$sync_accounts item=sync_account}
		<li>
			<input type="hidden" name="sync_account_ids[]" value="{$sync_account->id}">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$sync_account->id}">{$sync_account->name}</a>
		</li>
		{/foreach}
		{/if}
	</ul>
	<br>
	<br>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
</fieldset>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupTwitter');
	
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$frm.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
	
	$frm.find('BUTTON.submit')
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