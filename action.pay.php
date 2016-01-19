<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if(empty($params['amount']))
{
	echo $this->Lang('err_parameter');
	return;
}
else
	$params['amount'] = html_entity_decode($params['amount']);

if(empty($params['account']))
{
	$default = sgtUtils::GetAccount();
	if($default)
		$params['account'] = $default;
	else
	{
		echo $this->Lang('err_parameter');
		return;
	}
}
$pref = cms_db_prefix();
if(is_numeric($params['account']))
{
	$row = $db->GetRow('SELECT
account_id,
name,
currency,
amountformat,
usetest,
pubtoken,
testpubtoken,
stylesfile,
iconfile
FROM '.$pref.'module_sgt_account WHERE account_id=? AND isactive=TRUE',array($params['account']));
}
else
{
	$row = $db->GetRow('SELECT
account_id,
name,
currency,
amountformat,
usetest,
pubtoken,
testpubtoken,
stylesfile,
iconfile
FROM '.$pref.'module_sgt_account WHERE alias=? AND isactive=TRUE',array($params['account']));
}
if(!$row)
{
	echo $this->Lang('err_parameter');
	return;
}

$pubkey = $row['usetest'] ? $row['testpubtoken'] : $row['pubtoken'];
if($row['iconfile'])
	$icon = sgtUtils::GetUploadsUrl($this).'/'.str_replace('\\','/',$row['iconfile']);
else
	$icon = '';

$tplvars = array();

//custom button styling ?
if($row['stylesfile']) //using custom css for checkout display
{
	//replace href attribute in existing stylesheet link
	$u = sgtUtils::GetUploadsUrl($this).'/'.str_replace('\\','/',$row['stylesfile']);
	$t = <<<EOS
<script type="text/javascript">
//<![CDATA[
 document.getElementById('stripestyles').setAttribute('href',"{$u}");
//]]>
</script>

EOS;
	$tplvars['cssscript'] = $t;
}
//button label
$symbol = sgtUtils::GetSymbol($row['currency']);
if(strpos($params['amount'],$symbol) !== FALSE)
	$t = $symbol;
else
	$t = '';
//cope with optional currency symbol in amount
$amount = sgtUtils::GetPrivateAmount($params['amount'],$row['amountformat'],$t);
$public = sgtUtils::GetPublicAmount($amount,$row['amountformat'],$symbol);
$tplvars['submit'] = $this->Lang('pay',$public);
//TODO find a way to suppress page output from ajax response
//ajax-parameters
$_SESSION[CMS_USER_KEY] = 1; //need something for this, to workaround downstream
$url = $this->CreateLink($id,'payprocess',NULL,NULL,array(
	'stg_account'=>$row['account_id'],
	'stg_amount'=>$amount,
	'stg_token'=>''),NULL,TRUE);
$offs = strpos($url,'?mact=');
$ajfirst = str_replace('amp;','',substr($url,$offs+1));
$ajaxerr = $this->Lang('err_pay'); //default error msg

$jsincs[] = <<<EOS
<script src="https://checkout.stripe.com/checkout.js"></script>
EOS;
$jsloads[] = <<<EOS
 var handler = StripeCheckout.configure({
  key: '{$pubkey}',
  image: '{$icon}',
  name: '{$row['name']}',
  amount: {$amount},
  locale: 'auto',
  token: function(token) {
   var ajaxdata = '{$ajfirst}'+token.id;
   $.ajax({
    url: 'moduleinterface.php',
    type: 'POST',
    data: ajaxdata,
    dataType: 'text',
    success: function (data,status) {
     if(status == 'success' && !data ) {
       $('#pay_submit').closest('form').submit();
     } else {
      if(!data) {
       data = '{$ajaxerr}';
      }
      $('#pay_err').text(data).css('display','block');
      $('#pay_submit').removeAttr('disabled');
     }
    }
   });
  }
 });
 $('#pay_submit').click(function(ev) {
  $('#pay_err').css('display','none').text('');
  $(this).attr('disabled','disabled');
  handler.open();
  ev.preventDefault();
 });
 $(window).on('popstate',function() {
   handler.close();
 });

EOS;

if($jsloads)
{
	$jsfuncs[] = '$(document).ready(function() {
';
	$jsfuncs = array_merge($jsfuncs,$jsloads);
	$jsfuncs[] = '});
';
}
$tplvars['jsfuncs'] = $jsfuncs;
$tplvars['jsincs'] = $jsincs;

sgtUtils::ProcessTemplate($this,'pay.tpl',$tplvars);
?>
