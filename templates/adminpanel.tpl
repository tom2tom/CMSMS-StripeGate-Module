{if !empty($message)}<h4>{$message}</h4>{/if}
{$tabsheader}
{$tabstart_main}
{$formstart_main}
<div class="pageinput pageoverflow" style="display:inline-block;">
{if $items}
 <table id="itemdata" class="pagetable">
  <thead><tr>
{strip}
			<th>{$title_name}</th>
			<th>{$title_alias}</th>
			<th>{$title_owner}</th>
			<th class="nosort">{$title_default}</th>
			<th class="nosort">{$title_active}</th>
{if $pmod}	<th class="pageicon nosort">&nbsp;</th>
			<th class="pageicon nosort">&nbsp;</th>{/if}
			<th class="pageicon nosort">&nbsp;</th>
{if $pdel}	<th class="pageicon nosort">&nbsp;</th>{/if}
			<th class="checkbox nosort" style="width:20px;">{if isset($selectall)}{$selectall}{/if}</th>
{/strip}
  </tr></thead>
  <tbody>
{foreach from=$items item=entry} {cycle values='row1,row2' assign='rowclass'}
   <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
{strip}
		<td>{$entry->name}</td>
		<td>{$entry->alias}</td>
		<td>{$entry->ownername}</td>
		<td>{$entry->default}</td>
		<td>{$entry->active}</td>
{if $pmod} <td>{$entry->adminlink}</td>
		<td>{$entry->exportlink}</td>{/if}
		<td>{$entry->editlink}</td>
{if $pdel}	<td>{$entry->deletelink}</td>{/if}
		<td class="checkbox">{if isset($entry->selected)}{$entry->selected}{/if}</td>
{/strip}
   </tr>
{/foreach}
  </tbody>
 </table>
<br />
{if $padd}{$add}{/if}
<div style="float:right;">
{if $pmod}{$export} {/if}{if $pdel}{$delete}{/if}
</div>
<div style="clear:both;"></div>
{else}
<p class="pagetext">{$nodata}</p>
{if $padd}
<br />
{$add}{/if}
{/if}
</div>
{$form_end}
{$tab_end}

{$tabstart_settings}
{$formstart_settings}
<div class="pageinput pageoverflow">
<p class="pagetext">{$title_updir}:</p>
<p>{$input_updir}</p>
<p class="pagetext">{$title_password}:</p>
<p>{$input_password}</p>
{if isset($submit)}
<br />
<p>{$submit} {$cancel}</p>
{/if}
</div>
{$form_end}
{$tab_end}
{$tabsfooter}

{if !empty($jsincs)}{foreach from=$jsincs item=inc}{$inc}
{/foreach}{/if}
{if !empty($jsfuncs)}
<script type="text/javascript">
//<![CDATA[
{foreach from=$jsfuncs item=func}{$func}{/foreach}
//]]>
</script>
{/if}
