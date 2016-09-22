{if isset($cssscript)}{$cssscript}{/if}
<input id="pay_submit" type="submit" value="{$submit}" name="{$actionid}submit" />
<p id="pay_err"></p>

{if !empty($jsincs)}{foreach from=$jsincs item=inc}{$inc}{/foreach}{/if}
{if !empty($jsfuncs)}
<script type="text/javascript">
//<![CDATA[
{foreach from=$jsfuncs item=func}{$func}{/foreach}
//]]>
</script>
{/if}
