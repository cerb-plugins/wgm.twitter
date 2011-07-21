<h2>{'wgm.twitter.common'|devblocks_translate}</h2>
{if !$extensions.oauth}
<b>The oauth extension is not installed.</b>
{else}

<form action="javascript:;" method="post" id="frmSetupTwitter" onsubmit="return false;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="twitter">
	<input type="hidden" name="action" value="saveJson">
	
	<fieldset>
		<legend>Twitter Application</legend>
		
		<b>Consumer key:</b><br>
		<input type="text" name="consumer_key" value="{$params.consumer_key}" size="64"><br>
		<br>
		<b>Consumer secret:</b><br>
		<input type="text" name="consumer_secret" value="{$params.consumer_secret}" size="64"><br>
		<br>
		<div class="status"></div>
	
		<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
	</fieldset>
</form>

<form action="{devblocks_url}ajax.php{/devblocks_url}" method="post" id="frmAuthTwitter" style="display: {if $params.consumer_key && $params.consumer_secret}block{else}none{/if}">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="twitter">
	<input type="hidden" name="action" value="auth">
	<fieldset>
		<legend>Twitter Authorization</legend>
		<input type="submit" class="submit" value="Sign in with Twitter">
	</fieldset>
</form>
{if !empty($params.users)}
<form action="{devblocks_url}ajax.php{/devblocks_url}" method="post" id="frmAuthFacebook" style="display: {if $params.client_id && $params.client_secret}block{else}none{/if}">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="facebook">
	<input type="hidden" name="action" value="auth">
	<fieldset>
		<legend>Facebook Auth</legend>
		<input type="submit" class="submit" value="Sign in with Facebook">
	</fieldset>
</form>

<form action="javascript:;" method="post" id="frmDeleteTwitter" onsubmit="return false;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="twitter">
	<input type="hidden" name="action" value="removeUser">
	<fieldset>
		<legend>Authorized Users</legend>
		<ul>
		{foreach $params.users as $user}
		<li>{$user.screen_name} <button id="{$user.user_id}" type="button" class="submit"><span class="cerb-sprite2 sprite-cross-circle-frame"></span></button></li>
		{/foreach}
		</ul>
		<div class="status"></div>
	</fieldset>

</form>
{/if}
<script type="text/javascript">
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

$('#frmDeleteTwitter BUTTON.submit')
.click(function(e) {
	var user_id = $(this).attr('id');
	
	genericAjaxPost('frmDeleteTwitter', '', 'user_id=' + user_id, function(json) {
		$o = $.parseJSON(json);
		if(false == $o || false == $o.status) {
			Devblocks.showError('#frmDeleteTwitter div.status',$o.error);
		} else {
			if($o.count == 0) {
				$('#frmDeleteTwitter').fadeOut();
			} else {
				$('#'+user_id).parent().fadeOut();
				$('#'+user_id).parent().remove();
			}
		}
	});
})
;

</script>
{/if}