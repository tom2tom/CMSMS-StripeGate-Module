{if isset($form_start)}{$form_start}{/if}
{$hidden}
<input id="chkout_submit" type="submit" value="{$submit}" name="{$actionid}submit" style="margin-top:10px;" />
{if isset($form_start)}</form>{/if}

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
