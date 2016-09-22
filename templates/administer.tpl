{$backtomod_nav}<br /><br />
{if !empty($message)}<h3>{$message}</h3><br />{/if}
<div class="pageinput overflow" style="display:inline-block;">
{if $hasnav}
 <div class="tablenav">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}&nbsp;({$pageof})&nbsp;&nbsp;{$rowchanger}</div>
{/if}
<h3>{$title}</h3>
 {$start_form}
  <table id="itemdata" class="pagetable">
   <thead><tr>
{strip}
	<th class="{ldelim}sss:'isoDate'{rdelim}">{$title_submitted}</th>
	<th class="{ldelim}sss:'number'{rdelim}">{$title_amount}</th>
	<th>{$title_what}</th>
	<th>{$title_for}</th>
	<th>{$title_token}</th>
	<th class="nosort pageicon"></th>
{if $pmod} <th class="nosort pageicon"></th>{/if}
	<th class="nosort checkbox" style="width:20px;">{$header_checkbox}</th>
{/strip}
   </tr></thead>
   <tbody>
{foreach from=$rows item=payment}{cycle values='row1,row2' assign='rowclass'}
    <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
{strip}
	<td>{$payment->submitted}</td>
	<td style="text-align:right;">{$payment->amount}</td>
	<td>{$payment->what}</td>
	<td>{$payment->who}</td>
	<td>{$payment->token}</td>
	<td>{$payment->export}</td>
{if $pmod} <td>{$payment->delete}</td>{/if}
	<td>{$payment->selected}</td>
{/strip}
    </tr>
{/foreach}
   </tbody>
  </table>
{if $hasnav}<div class="tablenav">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}</div>{/if}
 <div style="margin-top:1em;float:right;">{$export}{if $pmod}&nbsp;{$delete}{/if}</div>
 <div style="clear:both;"></div>
{$end_form}
</div>

{if !empty($jsincs)}{foreach from=$jsincs item=inc}{$inc}{/foreach}{/if}
{if !empty($jsfuncs)}
<script type="text/javascript">
//<![CDATA[
{foreach from=$jsfuncs item=func}{$func}{/foreach}
//]]>
</script>
{/if}
