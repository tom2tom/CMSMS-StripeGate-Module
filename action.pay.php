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
if(isset($params['formed']))
{
//	$tplvars['form_start'] = $this->whatever;
}
$tplvars['hidden'] = $this->CreateInputHidden($id,'account',$row['account_id']);
//TODO SETUP button styling
//$tplvars['cssscript'] = $cssscript;
$symbol = sgtUtils::GetSymbol($row['currency']);
if(strpos($params['amount'],$symbol) !== FALSE) 
	$t = $symbol;
else
	$t = '';
//cope with optional currency symbol in amount
$amount = sgtUtils::GetPrivateAmount($params['amount'],$row['amountformat'],$t);
$public = sgtUtils::GetPublicAmount($amount,$row['amountformat'],$symbol);
$tplvars['submit'] = $this->Lang('pay',$public);

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
  var dbg = 1;
/* token = object:
{
  "id": "tok_17Q5EAGajAPEsyVFahrxVJnZ",
  "object": "token",
  "bank_account": {
  STUFF
  },
  "card": {
  STUFF
  },
  "client_ip": whatever,
  "created": 1452040358, (<< i.e. stamp)
  "livemode": false,
  "type": "card",
  "used": false
}
  TODO use the token to create a charge using ajax|server-side script
*/
  }
 });
 $('#chkout_submit').click(function(ev) {
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
