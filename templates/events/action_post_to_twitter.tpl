{if !empty($connected_accounts)}
	<b>{'common.connected_account'|devblocks_translate|capitalize}:</b>
	<div style="margin-left:10px;margin-bottom:10px;">
		<select name="{$namePrefix}[connected_account_id]">
		<option value=""></option>
		{foreach from=$connected_accounts item=account}
		<option value="{$account->id}" {if $params.connected_account_id == $account->id}selected="selected"{/if}>{$account->name}</option>
		{/foreach}
		</select>
	</div>
{else}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding:0 0.5em;margin:0.5em;"> 
			<p>
				<span class="ui-icon ui-icon-alert" style="float:left;margin-right:0.3em"></span> 
				<strong>Warning:</strong> No Twitter accounts are configured.  Posts will not be sent.
			</p>
		</div>
	</div>
{/if}

<b>{'common.message'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
