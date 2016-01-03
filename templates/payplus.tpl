{if !empty($message)}<p class="chkout_message">{$message}</p>{/if}
<div id="container">
{if !empty($title)}<h2>{$title}</h2>{/if}
{if isset($form_start)}{$form_start}{/if}
{$hidden}
<table>
<tbody>
<tr><td class="title">{$title_paywhat}</td><td>
<input id="chkout_paywhat" class="form_input" type="text" name="{$actionid}stg_paywhat"{if $paywhat} value="{$paywhat}"{/if} size="20" maxlength="50" />
<div id="error_paywhat" class="error_wrapper"></div>
</td></tr>
<tr><td class="title">{$title_payfor}</td><td>
<input id="chkout_payfor" class="form_input" type="text" name="{$actionid}stg_payfor"{if $payfor} value="{$payfor}"{/if} size="20" maxlength="50" />
<div id="error_payfor" class="error_wrapper"></div>
</td></tr>
<tr><td class="title">{$title_amount}</td><td>
<input id="chkout_amount" class="form_input" type="text" name="{$actionid}stg_amount"{if $amount} value="{$amount}"{/if} size="7" maxlength="9" placeholder="{$currency_example}" />
<div id="error_amount" class="error_wrapper"></div>
</td></tr>
{if isset($surcharge)}
<tr>
<td colspan="2" style="vertical-align:top;">
<p style="font-weight:bold;">{$surcharge}</p>
</td>
</tr>
{/if}
<tr>
<td colspan="2" style="text-align:center;vertical-align:bottom;">
<img alt="supported cards" src="{$logos}"><br />
</td>
</tr>
<tr><td class="title">{$title_number}</td><td>
<input id="chkout_number" class="form_input" type="text" name="{$actionid}stg_number"{if $number} value="{$number}"{/if} size="18" maxlength="20" pattern="[0-9 ]*"/>
<div id="error_number" class="error_wrapper"></div>
</td></tr>
<tr><td class="title">{$title_cvc}</td><td>
<input id="chkout_cvc" class="form_input" type="text" name="{$actionid}stg_cvc"{if $cvc} value="{$cvc}"{/if} size="4" maxlength="6" pattern="[0-9]*" />
<div id="error_cvc" class="error_wrapper"></div>
</td></tr>
<tr><td class="title">{$title_expiry}</td><td>
 <input id="chkout_exp_month" class="form_input" type="text" name="{$actionid}stg_month"{if $month} value="{$month}"{/if} size="2" maxlength="2" pattern="[0-9]{literal}{1,2}{/literal}" placeholder="{$MM}" />
 <span> / </span>
 <input id="chkout_exp_year" type="text" name="{$actionid}stg_year"{if $year} value="{$year}"{/if} size="4" maxlength="4" pattern="[0-9]{literal}{2,4}{/literal}" placeholder="{$YYYY}" />
<div id="error_exp_month" class="error_wrapper"></div>
<div id="error_exp_year" class="error_wrapper"></div>
</td></tr>
</tbody>
</table>
{if isset($form_start)}
<input id="chkout_submit" type="submit" value="{$submit}" name="{$actionid}submit" style="margin-top:10px;" />
</form>{/if}
</div>

{if !empty($jsincs)}
{foreach from=$jsincs item=file}{$file}
{/foreach}{/if}
{if !empty($jsfuncs)}
<script type="text/javascript">
//<![CDATA[
{foreach from=$jsfuncs item=func}{$func}{/foreach}
//]]>
</script>
{/if}
