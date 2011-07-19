<b>User:</b>
<select name="{$namePrefix}[user]">
{foreach $users as $user}
<option value="{$user.user_id}"{if $params.user == $user.user_id} selected{/if}>{$user.screen_name}</option>
{/foreach}
</select>
<br>

<b>Update status:</b><br>
<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;">{$params.content}</textarea><br>

{if !empty($token_labels)}
<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=content');">{'common.test'|devblocks_translate|capitalize}</button>
<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<div class="tester"></div>
{/if}
