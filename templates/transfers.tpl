{$backtomod_nav}<br /><br />
{if !empty($message)}<h3>{$message}</h3><br />{/if}
{if $rows}
<div class="pageinput overflow" style="display:inline-block;">
{if $hasnav}
 <div class="tablenav">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}&nbsp;({$pageof})&nbsp;&nbsp;{$rowchanger}</div>
{/if}
{/if}
<h3>{$title}</h3>
 {$start_form}
 {if $rows}
  <table id="transferdata" class="pagetable">
   <thead><tr>
{strip}
	<th>{$title_token}</th>
	<th class="{ldelim}sss:'isoDate'{rdelim}">{$title_transferred}</th>
	<th class="{ldelim}sss:'isoDate'{rdelim}">{$title_paidat}</th>
	<th class="{ldelim}sss:'number'{rdelim}">{$title_grossamount}</th>
	<th class="{ldelim}sss:'number'{rdelim}">{$title_netamount}</th>
	<th>{$title_what}</th>
	<th>{$title_for}</th>
	<th class="nosort pageicon"></th>
	<th class="nosort checkbox" style="width:20px;">{$header_checkbox}</th>
{/strip}
   </tr></thead>
   <tbody>
{foreach from=$rows item=payment}{cycle values='row1,row2' assign='rowclass'}
    <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
{strip}
	<td>{$payment->token}</td>
	<td>{$payment->when}</td>
	<td>{$payment->paidat}</td>
	<td style="text-align:right;">{$payment->gross}</td>
	<td style="text-align:right;">{$payment->net}</td>
	<td>{$payment->what}</td>
	<td>{$payment->who}</td>
	<td>{$payment->export}</td>
	<td>{$payment->selected}</td>
{/strip}
    </tr>
{/foreach}
   </tbody>
  </table>
{if $hasnav}<div class="tablenav">{$first}&nbsp;|&nbsp;{$prev}&nbsp;&lt;&gt;&nbsp;{$next}&nbsp;|&nbsp;{$last}</div>{/if}
 {else}
{$norecords}
 {/if}
 <div style="margin-top:1em;">{$close}{if $rows} {$duration}
 <div style="float:right;">{$export}</div>
 <div style="clear:both;"></div>{/if}
 </div>
{$end_form}
{if $rows}
</div>
{/if}
