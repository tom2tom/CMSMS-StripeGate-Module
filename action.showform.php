<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
/*
This is a superset of the 'payplus' action, suitable for initiation by other modules
Supported (optional) on-first-arrival $params are:
'account'
'amount'
'currency'
'message'
'nosur'
'payer'
'payfor'
'preserve'
'surrate'
'withcancel'
Not supported:
'contact'
'payee'
'senddata'

and upon return
'stg_account'
'stg_amount'
'stg_currency'
'stg_cvc'
'stg_month'
'stg_nosur'
'stg_number'
'stg_payfor'
'stg_paywhat'
'stg_surrate'
'stg_year'

'origreturnid' after redirect?
'cancel' maybe
'submit' maybe
*/

if (empty($params['account']) && empty($params['stg_account'])) {
	$default = StripeGate\Utils::GetAccount();
	if ($default) {
		if (isset($params['submit']))
			$params['stg_account'] = $default;
		else
			$params['account'] = $default;
	} else {
		echo $this->Lang('err_parameter');
		return;
	}
}

if (isset($params['cancel'])) {
	$caller = NULL;
	$funcs = new StripeGate\Payer($caller,$this);
	$funcs->HandleResult($params); //redirects
	exit;
}

$pref = cms_db_prefix();

if (isset($params['submit'])) {
	//some of these are needed only if continuing past error
	$row = $db->GetRow('SELECT name,title,currency,amountformat,minpay,surchargerate,usetest,privtoken,testprivtoken,stylesfile FROM '.
	$pref.'module_sgt_account WHERE account_id=?',[$params['stg_account']]);
	if ($row['usetest']) {
		if ($row['testprivtoken']) {
			$cfuncs = new StripeGate\Crypter($this);
			$privkey = $cfuncs->decrypt_value($row['testprivtoken']);
		} else {
			$privkey = FALSE;
		}
	} else {
		if ($row['privtoken']) {
			$cfuncs = new StripeGate\Crypter($this);
			$privkey = $cfuncs->decrypt_value($row['privtoken']);
		} else {
			$privkey = FALSE;
		}
	}
	if (!$privkey) {
		echo $this->Lang('err_parameter');
		return;
	}

	if(!empty($params['stg_currency']))
		$row['currency'] = $params['stg_currency'];
	if(!empty($params['stg_surrate']))
		$row['surchargerate'] = $params['stg_surrate'];

	$symbol = StripeGate\Utils::GetSymbol($row['currency']);
	$amount = StripeGate\Utils::GetPrivateAmount($params['stg_amount'],$row['amountformat'],$symbol);
	if ($row['surchargerate'] > 0.0 && empty($params['stg_nosur']))
		$amount = ceil($amount * (1.0+$row['surchargerate']));

	$card = [
		'number' => $params['stg_number'],
		'exp_month' => $params['stg_month'],
		'exp_year' => $params['stg_year'],
		'cvc' => $params['stg_cvc']
	];

	$exdata = [
		'paywhat' => $params['stg_paywhat'],
		'payfor' => $params['stg_payfor']
	];

	$data = [
		'amount' => $amount,
		'currency' => $row['currency'],
		'source' => $card,
		'metadata' => $exdata
	];

	try {
		Stripe\Stripe::setApiKey($privkey);
		$charge = Stripe\Charge::create($data); //synchronous
		$response = $charge->__toArray(TRUE);
		$sql = 'INSERT INTO '.$pref.'module_sgt_record (
account_id,
amount,
paywhat,
payfor,
recorded,
identifier
) VALUES(?,?,?,?,?,?)';
		$db->Execute($sql,[
			$params['stg_account'],
			$amount,
			$params['stg_paywhat'],
			$params['stg_payfor'],
			$response['created'],
			$response['id']]);

		$params = array_merge($params,$response);
		$caller = NULL;
		$funcs = new StripeGate\Payer($caller,$this);
		$funcs->HandleResult($params); //redirects
		exit;
	} catch (Exception $e) {
		$message = $e->getMessage();
		//all inputs resume
		foreach ($params as $key=>$val) {
			if (strpos($key,'stg_') === 0) {
				$t = substr($key,4);
				$$t = $val;
				unset($params[$key]);
			}
		}

		if (isset($currency))
			$params['currency'] = $currency;
		if (isset($nosur))
			$params['nosur'] = 1;
		if (isset($surrate))
			$params['surrate'] = $surrate;
		if (isset($withcancel))
			$params['withcancel'] = 1;
		$params['preserve'] = $preserve;
	}
} else { //not submitted i.e. first-time
	if (is_numeric($params['account'])) {
		$row = $db->GetRow('SELECT
account_id,
name,
title,
currency,
amountformat,
surchargerate,
minpay,
usetest,
privtoken,
testprivtoken,
stylesfile
FROM '.$pref.'module_sgt_account WHERE account_id=? AND isactive=TRUE',[$params['account']]);
	} else {
		$row = $db->GetRow('SELECT
account_id,
name,
title,
currency,
amountformat,
surchargerate,
minpay,
usetest,
privtoken,
testprivtoken,
stylesfile
FROM '.$pref.'module_sgt_account WHERE alias=? AND isactive=TRUE',[$params['account']]);
	}
	if (!$row) {
		echo $this->Lang('err_parameter');
		return;
	}

	$account_id = $row['account_id'];
	if(!empty($params['currency']))
		$row['currency'] = $params['currency'];
	if(!empty($params['surrate']))
		$row['surchargerate'] = html_entity_decode($params['surrate']);

	$message = (!empty($params['message'])) ? html_entity_decode($params['message']) : FALSE;
	//populate inputs from supplied data
	if (!empty($params['amount'])) {
		$amount = html_entity_decode($params['amount']);
		$symbol = StripeGate\Utils::GetSymbol($row['currency']);
		$amount = StripeGate\Utils::CleanPublicAmount($amount,$row['amountformat'],$symbol);
	} else {
		$amount = NULL;
	}
	$cvc = NULL;
	$month = NULL;
	$number = NULL;
	$payfor= (!empty($params['payer'])) ? $params['payer'] : NULL;
	$paywhat = (!empty($params['payfor'])) ? $params['payfor'] : NULL;
	$year = NULL;
}

$baseurl = $this->GetModuleURLPath();
$tplvars = [];

$hidden = [
 'stg_account_id' => $account_id,
 'stg_preserve' => $params['preserve']
];
if(isset($params['currency']))
	$hidden['stg_currency'] = $params['currency'];
if (isset($params['nosur']))
	$hidden['stg_nosur'] = 1;
if (isset($params['surrate']))
	$hidden['stg_surrate'] = $params['surrate'];
if (isset($params['withcancel']))
	$hidden['stg_withcancel'] = 1;

$tplvars['form_start'] = $this->CreateFormStart($id,'showform',$returnid,'POST',
	'',TRUE,'',$hidden);
$tplvars['hidden'] = NULL;

if ($message)
	$tplvars['message'] = $message;

if ($row['title'])
	$tplvars['title'] = $row['title'];
else
	$tplvars['title'] = $this->Lang('title_checkout',$row['name']);

/*
U.S. businesses can accept
 Visa, MasterCard, American Express, JCB, Discover, Diners Club.
Australian, Canadian, European, and Japanese businesses can accept
 Visa, MasterCard, American Express.
*/
if ($row['usetest']) {
	if ($row['testprivtoken']) {
		$cfuncs = new StripeGate\Crypter($this);
		$privkey = $cfuncs->decrypt_value($row['testprivtoken']);
	} else {
		$privkey = FALSE;
	}
} else {
	if ($row['privtoken']) {
		$cfuncs = new StripeGate\Crypter($this);
		$privkey = $cfuncs->decrypt_value($row['privtoken']);
	} else {
		$privkey = FALSE;
	}
}
if (!$privkey) {
	echo $this->Lang('err_parameter');
	return;
}

$account = Stripe\Account::retrieve($privkey);
if ($account) {
	$data = $account->__toArray();
	switch ($data['country']) {
		case 'AU':
		case 'CA':
		case 'JP':
			$iconfile = $baseurl.'/images/3card-logos-small.gif'; //show 3 icons
			break;
		case 'US':
			$iconfile = $baseurl.'/images/6card-logos-small.gif'; //show 6 icons
			break;
		default:
			if (strpos($data['timezone'],'Europe/') === 0)
				$iconfile = $baseurl.'/images/3card-logos-small.gif';
			else
				$iconfile = NULL;
			break;
	}
} else
	$iconfile = NULL;

$symbol = StripeGate\Utils::GetSymbol($row['currency']);
$t = StripeGate\Utils::GetPublicAmount(1999,$row['amountformat'],$symbol);
$tplvars += [
	'currency_example' => $this->Lang('currency_example',$t),
	'logos' => $iconfile,
	'title_amount' => $this->Lang('payamount'),
	'amount' => $amount,
	'title_cvc' => $this->Lang('cardcvc'),
	'cvc' => $cvc,
	'title_expiry' => $this->Lang('cardexpiry'),
	'MM' => $this->Lang('month_template'),
	'month' => $month,
	'YYYY' => $this->Lang('year_template'),
	'year' => $year,
	'title_number' => $this->Lang('cardnumber'),
	'number' => $number,
	'title_payfor' => $this->Lang('payfor'),
	'payfor' => $payfor,
	'title_paywhat' => $this->Lang('paywhat'),
	'paywhat' => $paywhat,
	'submit' => $this->Lang('submit')
];
if (isset($params['withcancel']))
	$tplvars['cancel'] = $this->Lang('cancel');

if ($row['surchargerate'] > 0.0 && empty($params['nosur'])) {
	$surrate = $row['surchargerate'];
	$t = number_format($surrate * 100,2);
	if (strrpos($t,'0') > 0)
		$t = rtrim($t,'0.');
	$surstr = $this->Lang('percent',$t);
	$t = '<span id="surcharge">'.$surstr.'</span>';
	$tplvars['surcharge'] = $this->Lang('surcharge',$t);
} else
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
$err12 = $this->Lang('err_badnum');
$err13 = $this->Lang('err_badmonth');
$yr = date('Y');
$cent = substr($yr,0,2);
$err14 = $this->Lang('err_badyear',$yr);
$rawmin = preg_replace('/\D/','',$row['minpay']);
$min = StripeGate\Utils::GetPublicAmount($rawmin,$row['amountformat'],$symbol);
$err15 = $this->Lang('err_toosmall',$min);
if (preg_match('/^(.*)?(S)(\W+)?(\d*)$/',$row['amountformat'],$matches)) {
	$sep = ($matches[1]) ? $symbol : $matches[3];
	$places = strlen($matches[4]);
} else { //defaults like US$
	$sep = '.';
	$places = 2;
}

$jsfuncs = [];
$jsloads = [];
$jsincs = [];

$jsfuncs[] = <<<EOS
function lock_inputs() {
 $('#pplus_submit').attr('disabled','disabled');
}
function unlock_inputs() {
 $('#pplus_submit').removeAttr('disabled');
}
function show_error(input, message) {
 $('#pplus_' + input).addClass('error');
 $('#error_' + input).html('<p>'+message+'</p>').show();
 return false;
}
function clear_error(input) {
 $('#pplus_' + input).removeClass('error');
 $('#error_' + input).html('').hide();
}
function validate(field,value) {
 switch (field) {
  case 'paywhat':
   if (!value.length) { return show_error(field,'{$err7}'); }
   break;
  case 'payfor':
   if (!value.length) { return show_error(field,'{$err6}'); }
   break;
  case 'number':
   var len = value.length;
   if (len==0) { return show_error(field,'{$err1}'); }
   if (len<12 || len>16) { return show_error(field,'{$err12}'); }
   break;
  case 'cvc':
   if (!value.length) { return show_error(field,'{$err2}'); }
   break;
  case 'exp_month':
   if (!value.length) { return show_error(field,'{$err3}'); }
   if (value < 1 || value > 12) { return show_error(field,'{$err13}'); }
   break;
  case 'exp_year':
   if (!value.length) { return show_error(field,'{$err4}'); }
   if (value < {$yr}) { return show_error(field,'{$err14}'); }
   break;
  case 'amount':
   if (!value.length) { return show_error(field,'{$err5}'); }
   value = value.replace(/\D/g,'');
   if (value < {$rawmin}) { return show_error(field,'{$err15}'); }
   break;
 }
 clear_error(field);
 return true;
}
function public_amount(amount) {
 var sep = '{$sep}',
  pub = parseFloat(amount).toFixed({$places});
 if (sep !== '.') {
  pub = pub.replace('.',sep);
 }
 if (sep != '{$symbol}') {
  pub = '{$symbol}'+pub;
 }
 return pub;
}
function sanitize(field) {
 var \$in = $('#pplus_' + field),
   value = $.trim(\$in.val());
 switch (field) {
  case 'number':
   value = value.replace(/\D/g,'');
   break;
  case 'cvc':
   value = value.replace(/\D/g,'');
   break;
  case 'exp_month':
   value = value.replace(/\D/g,'');
   value = value.replace(/^0+/,'');
   if (value.length == 1) { value = '0' + value; }
   break;
  case 'exp_year':
   value = value.replace(/\D/g,'');
   if (value.length == 2) { value = '{$cent}' + value; }
   break;
  case 'amount':
   value = value.replace(/[^\d\.]/g,'');
   if (value.length) { value = public_amount(value); }
   break;
 }
 \$in.val(value);
}
EOS;

$jsloads[] = <<<EOS
 $('#pplus_container input').attr('autocomplete','off').blur(function() {
  var \$in = $(this),
   id = \$in.attr('id');
  if (id && id.lastIndexOf('pplus_',0) === 0) {
   var name = id.substring(6);
   sanitize(name);
   validate(name,\$in.val());
  }
 });
EOS;

if ($surrate)
	$jsloads[] = <<<EOS
 $('#pplus_amount').blur(function(){
  var value = $.trim($(this).val());
  if (value.length) {
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
 $('#pplus_submit').click(function() {
  lock_inputs();
  $('#pplus_container input').blur(); //trigger sanitize/validate functions
  unlock_inputs();
  if ($('input.error').length > 0) {
   $('input.error:first').focus();
   return false;
  }
  return true;
 });
 $('.watermark').watermark();
EOS;

$jsincs[] = '<script type="text/javascript" src="'.$baseurl.'/lib/js/jquery.watermark.min.js"></script>';

if ($row['stylesfile']) { //using custom css for checkout display
	$u = StripeGate\Utils::GetUploadsUrl($this).'/'.str_replace('\\','/',$row['stylesfile']);
} else {
	$u = $baseurl.'/css/payplus.css';
}
//replace href attribute in existing stylesheet link, or append styles link
$t = <<<EOS
<script type="text/javascript">
//<![CDATA[
var styler = document.getElementById('stripestyles');
if (styler != null) {
 styler.setAttribute('href',"{$u}");
} else {
 var linkadd = '<link rel="stylesheet" type="text/css" href="{$u}" />',
  \$head = $('head'),
  \$linklast = \$head.find("link[rel='stylesheet']:last");
 if (\$linklast.length) {
  \$linklast.after(linkadd);
 } else {
  \$head.append(linkadd);
 }
}
//]]>
</script>
EOS;
echo $t;

$jsall = NULL;
StripeGate\Utils::MergeJS($jsincs,$jsfuncs,$jsloads,$jsall);
unset($jsincs);
unset($jsfuncs);
unset($jsloads);

echo StripeGate\Utils::ProcessTemplate($this,'payplus.tpl',$tplvars);
if ($jsall) {
	echo $jsall;
}
