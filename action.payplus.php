<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if(!function_exists('get_public_amount'))
{
	function get_public_amount($units,$format,$char)
	{
		if(preg_match('/^(.*)?(S)(\W+)?(\d*)$/',$format,$matches))
		{
			$places = strlen($matches[4]);
			$times = pow(10,$places);
			$num = number_format($units/$times,$places);
			if($matches[1])
			{
				if(strpos('.',$num) !== FALSE)
					$num = str_replace('.',$char,$num); //workaround PHP<5.4
				else
					$num .= $char;
			}
			else
			{
				if($matches[3] != '.')
					$num = str_replace('.',$matches[3],$num);
				$num = $char.$num;
			}
			return $num;
		}
		else
			return '$'+number_format($units/100,2);
	}
	function get_private_amount($amount,$format,$char)
	{
		if(preg_match('/^(.*)?(S)(\W+)?(\d*)$/',$format,$matches))
		{
			if($matches[1])
				$num = str_replace($char,'.',$amount);
			else
				$num = str_replace(array($char,$matches[3]),array('','.'),$amount);
			$places = strlen($matches[4]);
			$times = pow(10,$places);
			return (int)($num * $times);
		}
		else
			return preg_replace('/\D/','',$amount) + 0; //assume 'raw' is good enough, in this context
	}
}

if(empty($params['account']) && empty($params['stg_account']))
{
	$default = stripe_utils::GetAccount();
	if($default)
	{
		if(isset($params['submit']))
			$params['stg_account'] = $default;
		else
			$params['account'] = $default;
	}
	else
	{
		echo $this->Lang('err_parameter');
		return;
	}
}

$pref = cms_db_prefix();

if(isset($params['stg_account'])) //we're back, after submission (no 'submit' parameter!)
{
	try {
		require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'init.php');
	} catch (Exception $e) {
		echo $this->Lang('err_system');
		return;
	}
	//some of these are needed only if continuing past error
	$row = $db->GetRow('SELECT name,title,currency,amountformat,minpay,surchargerate,usetest,privtoken,testprivtoken FROM '.
	$pref.'module_sgt_account WHERE account_id=?',array($params['stg_account']));
	if($row['usetest'])
	{
		if($row['testprivtoken'])
			$privkey = stripe_utils::decrypt_value($this,$row['testprivtoken']);
		else
			$privkey = FALSE;
	}
	else
	{
		if($row['privtoken'])
			$privkey = stripe_utils::decrypt_value($this,$row['privtoken']);
		else
			$privkey = FALSE;
	}
	if(!$privkey)
	{
		echo $this->Lang('err_parameter');
		return;
	}

	$card = array(
	 'number' => $params['stg_number'],
	 'exp_month' => $params['stg_month'],
	 'exp_year' => $params['stg_year'],
	 'cvc' => $params['stg_cvc']
	);
	$symbol = stripe_utils::GetCurrency($row['currency']);
	$amount = get_private_amount($params['stg_amount'],$row['amountformat'],$symbol);
	if($row['surchargerate'] && empty($params['nosur']))
		$amount = ceil($amount * (1.0+$row['surchargerate']));
/*DEBUG
	$card = array('number' => '4242424242424242', 'exp_month' => 8, 'exp_year' => 2018);
	$amount = 50; min. charge
*/
	$exdata = array('paywhat'=>$params['stg_paywhat'], 'payfor'=>$params['stg_payfor']);

	$data = array(
	 'card' => $card,
	 'amount' => $amount,
	 'currency' => $row['currency'],
	 'metadata' => $exdata
	);

	try
	{
if(1)
{
		Stripe::setApiKey($privkey);
		$charge = Stripe_Charge::create($data);
		$response = $charge->__toArray(TRUE);
}
else
{
$response = array (
  'id' => 'ch_17Qy5lGajAPEsyVFhftWVEyr',
  'object' => 'charge',
  'amount' => 50,
  'amount_refunded' => 0,
  'application_fee' => null,
  'balance_transaction' => 'txn_17Qy5lGajAPEsyVF75lxMrO4',
  'captured' => true,
  'created' => 1452251257,
  'currency' => 'aud',
  'customer' => null,
  'description' => null,
  'destination' => null,
  'dispute' => null,
  'failure_code' => null,
  'failure_message' => null,
  'fraud_details' => array(),
  'invoice' => null,
  'livemode' => false,
  'metadata' => 
    array (
      'paywhat' => 'Test',
      'payfor' => 'Me'
	  ),
  'paid' => true,
  'receipt_email' => null,
  'receipt_number' => null,
  'refunded' => false,
  'refunds' => 
    array (
      'object' => 'list',
      'data' =>  array (),
      'has_more' => false,
      'total_count' => 0,
      'url' => '/v1/charges/ch_17Qy5lGajAPEsyVFhftWVEyr/refunds',
	  ),
  'shipping' => null,
  'source' => 
    array (
      'id' => 'card_17Qy5lGajAPEsyVFYdwJkKgw',
      'object' => 'card',
      'address_city' => null,
      'address_country' => null,
      'address_line1' => null,
      'address_line1_check' => null,
      'address_line2' => null,
      'address_state' => null,
      'address_zip' => null,
      'address_zip_check' => null,
      'brand' => 'Visa',
      'country' => 'US',
      'customer' => null,
      'cvc_check' => null,
      'dynamic_last4' => null,
      'exp_month' => 8,
      'exp_year' => 2018,
      'fingerprint' => 'fkrclzqDN7Vm2zs9',
      'funding' => 'credit',
      'last4' => '4242',
      'metadata' => array (),
      'name' => null,
      'tokenization_method' => null,
	  'statement_descriptor' => null,
  	  'status' => 'succeeded'
  	)
	);
}
		$sql = 'INSERT INTO '.$pref.'module_sgt_record (
account_id,
amount,
paywhat,
payfor,
recorded,
identifier
) VALUES(?,?,?,?,?,?)';
		$db->Execute($sql,array(
			$params['stg_account'],
			$amount,
			$params['stg_paywhat'],
			$params['stg_payfor'],
			$response['created'],
			$response['id']));

		echo $this->Lang('payment_submitted');
		return;
	}
	catch (Exception $e)
	{
		$message = $e->getMessage();
		$row['account_id'] = $params['stg_account'];
		//all inputs resume
		$amount = $params['stg_amount'];
		$cvc = $params['stg_cvc'];
		$month = $params['stg_month'];
		$number = $params['stg_number'];
		$payfor = $params['stg_payfor'];
		$paywhat = $params['stg_paywhat'];
		$year = $params['stg_year'];

		if(isset($params['stg_formed']))
			$params['formed'] = $params['stg_formed'];
		if(isset($params['stg_nosur']))
			$params['nosur'] = $params['stg_nosur'];
	}
}
else //not submitted
{
	if(is_numeric($params['account']))
	{
		$row = $db->GetRow('SELECT
account_id,
name,
title,
currency,
amountformat,
surchargerate,
minpay,
stylesfile
FROM '.$pref.'module_sgt_account WHERE account_id=? AND isactive=TRUE',array($params['account']));
	}
	else
	{
		$row = $db->GetRow('SELECT
account_id,
name,
title,
currency,
amountformat,
surchargerate,
minpay,
stylesfile
FROM '.$pref.'module_sgt_account WHERE alias=? AND isactive=TRUE',array($params['account']));
	}
	if(!$row)
	{
		echo $this->Lang('err_parameter');
		return;
	}
	$message = FALSE;
	//all inputs start empty
	$amount = NULL;
	$cvc = NULL;
	$month = NULL;
	$number = NULL;
	$payfor= NULL;
	$paywhat = NULL;
	$year = NULL;
}

if(!isset($params['formed']))
	$smarty->assign('form_start',$this->CreateFormStart($id,'payplus',$returnid));

$hidden = '<input type="hidden" name="'.$id.'stg_account" value="'.$row['account_id'].'" />';
if(isset($params['formed']))
	$hidden .= '<input type="hidden" name="'.$id.'stg_formed" value="'.$params['formed'].'" />';
if(isset($params['nosur']))
	$hidden .= '<input type="hidden" name="'.$id.'stg_nosur" value="'.$params['nosur'].'" />';
$smarty->assign('hidden',$hidden);

$symbol = stripe_utils::GetCurrency($row['currency']);

$jsfuncs = array();
$jsloads = array();

if($message)
	$smarty->assign('message',$message);
$smarty->assign('MM',$this->Lang('month_template'));
$smarty->assign('YYYY',$this->Lang('year_template'));
$t = get_public_amount(1999,$row['amountformat'],$symbol);
$smarty->assign('currency_example',$this->Lang('currency_example',$t));
//TODO per country U.S. businesses can accept more card-types >> other image
$smarty->assign('logos',$this->GetModuleURLPath().'/images/3card-logos-small.gif');
$smarty->assign('shortnote',$this->Lang('note_example'));
$smarty->assign('submit',$this->Lang('submit'));
$smarty->assign('title_amount',$this->Lang('payamount'));
$smarty->assign('amount',$amount);
$smarty->assign('title_cvc',$this->Lang('cardcvc'));
$smarty->assign('cvc',$cvc);
$smarty->assign('title_expiry',$this->Lang('cardexpiry'));
$smarty->assign('month',$month);
$smarty->assign('year',$year);
$smarty->assign('title_number',$this->Lang('cardnumber'));
$smarty->assign('number',$number);
$smarty->assign('title_payfor',$this->Lang('payfor'));
$smarty->assign('payfor',$payfor);
$smarty->assign('title_paywhat',$this->Lang('paywhat'));
$smarty->assign('paywhat',$paywhat);
//$smarty->assign('note',$this->Lang('note'));

if($row['title'])
	$smarty->assign('title',$row['title']);
else
	$smarty->assign('title',$this->Lang('title_checkout',$row['name']));

if($row['surchargerate'] > 0 && empty($params['nosur']))
{
	$surrate = $row['surchargerate'];
	$surstr = number_format($surrate * 100, 2).' '.$this->Lang('percent');
	$t = '<span id="surcharge">'.$surstr.'</span>';
	$smarty->assign('surcharge',$this->Lang('surcharge',$t));
}
else
	$surrate = FALSE;

//missing-value errors
$err1 = $this->Lang('err_nonum');
$err2 = $this->Lang('err_nocvc');
$err3 = $this->Lang('err_nomonth');
$err4 = $this->Lang('err_noyear');
$err5 = $this->Lang('err_noamount');
$err6 = $this->Lang('err_nowho');
$err7 = $this->Lang('err_nopurpose');
//bad-value errors
$err13 = $this->Lang('err_badnum');
$err10 = $this->Lang('err_badmonth');
$yr = date('Y');
$cent = substr($yr,0,2);
$err11 = $this->Lang('err_badyear',$yr);
$rawmin = preg_replace('/\D/','',$row['minpay']);
$min = get_public_amount($rawmin,$row['amountformat'],$symbol);
$err12 = $this->Lang('err_toosmall',$min);
if(preg_match('/^(.*)?(S)(\W+)?(\d*)$/',$row['amountformat'],$matches))
{
	$sep = ($matches[1]) ? $symbol : $matches[3];
	$places = strlen($matches[4]);
}
else //defaults like US$
{
	$sep = '.';
	$places = 2;
}

/*
TODO handle account-specific .css file via jQuery?
$('head #stripestyles').replaceWith('<link href="something.css" ... />');
or
$('head #stripestyles').attr('href', 'something.css');
or
document.getElementById("stripestyles").href="something.css";
*/

if(!isset($params['formed']))
{
	$jsfuncs[] = <<<EOS
function lock_inputs() {
 $('#chkout_submit').attr('disabled','disabled');
}
function unlock_inputs() {
 $('#chkout_submit').removeAttr('disabled');
}

EOS;
}
$jsfuncs[] = <<<EOS
function show_error(input, message) {
 $('#chkout_' + input).addClass('error');
 $('#error_' + input).html('<p>'+message+'</p>').show();
 return false;
}
function clear_error(input) {
 $('#chkout_' + input).removeClass('error');
 $('#error_' + input).html('').hide();
}
function validate(field,value) {
 switch(field) {
  case 'paywhat':
   if(!value.length) { return show_error(field,'{$err7}'); }
   break;
  case 'payfor':
   if(!value.length) { return show_error(field,'{$err6}'); }
   break;
  case 'number':
   var len = value.length;
   if(len==0) { return show_error(field,'{$err1}'); }
   if(len<12 || len>16) { return show_error(field,'{$err13}'); }
   break;
  case 'cvc':
   if(!value.length) { return show_error(field,'{$err2}'); }
   break;
  case 'exp_month':
   if(!value.length) { return show_error(field,'{$err3}'); }
   if(value < 1 || value > 12) { return show_error(field,'{$err10}'); }
   break;
  case 'exp_year':
   if(!value.length) { return show_error(field,'{$err4}'); }
   if(value < {$yr}) { return show_error(field,'{$err11}'); }
   break;
  case 'amount':
   if(!value.length) { return show_error(field,'{$err5}'); }
   value = value.replace(/\D/g,'');
   if(value < {$rawmin}) { return show_error(field,'{$err12}'); }
   break;
 }
 clear_error(field);
 return true;
}
function public_amount(amount) {
 var sep = '{$sep}',
  pub = parseFloat(amount).toFixed({$places});
 if(sep !== '.') {
  pub = pub.replace('.',sep);
 }
 if(sep != '{$symbol}') {
  pub = '{$symbol}'+pub;
 }
 return pub;
}
function sanitize(field) {
 var \$in = $('#chkout_' + field),
   value = $.trim(\$in.val());
 switch(field) {
  case 'number':
   value = value.replace(/\D/g,'');
   break;
  case 'cvc':
   value = value.replace(/\D/g,'');
   break;
  case 'exp_month':
   value = value.replace(/\D/g,'');
   value = value.replace(/^0+/,'');
   break;
  case 'exp_year':
   value = value.replace(/\D/g,'');
   if(value.length == 2) { value = '{$cent}' + value; }
   break;
  case 'amount':
   value = value.replace(/[^\d\.]/g,'');
   if(value.length) { value = public_amount(value); }
   break;
 }
 \$in.val(value);
}

EOS;

$jsloads[] = <<<EOS
 $('#container input').attr('autocomplete','off').blur(function() {
  var \$in = $(this),
   id = \$in.attr('id');
  if(id && id.lastIndexOf('chkout_',0) === 0) {
   var name = id.substring(7);
   sanitize(name);
   validate(name,\$in.val());
  }
 });

EOS;

if($surrate)
	$jsloads[] = <<<EOS
 $('#chkout_amount').blur(function(){
  var value = $.trim($(this).val());
  if(value.length) {
   var amt = value.replace(/[^\d\.]+/g, ''),
     num = parseFloat(amt) * {$surrate};
   amt = public_amount(num);
   $('#surcharge').text(amt);
  } else {
   $('#surcharge').text('{$surstr}');
  }
 });

EOS;

$jsloads[] = <<<EOS
 $('#chkout_number').closest('form').submit(function() {
  lock_inputs();
  $('#container input').blur(); //trigger sanitize/validate functions
  if($('input.error').length > 0) {
   unlock_inputs();
   $('input.error:first').focus();
   return false;
  }
  return true;
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
$smarty->assign('jsfuncs',$jsfuncs);

echo $this->ProcessTemplate('payplus.tpl');

?>
