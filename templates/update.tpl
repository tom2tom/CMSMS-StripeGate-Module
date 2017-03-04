{$backtomod_nav}<br />
{if !empty($message)}<h4>{$message}</h4>{/if}
{$form_start}
{$hidden}
<div class="pageinput pageoverflow">
{foreach from=$settings item=setting}
 <p class="pagetext" style="margin-left:0;">{$setting->title}:</p>
 <div>{$setting->input}{if isset($setting->help)}<br />{$setting->help}{/if}</div>
{/foreach}
<br />
<p>{$submit} {$cancel}</p>
</div>
{$form_end}
