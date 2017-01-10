<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
/*
This action handles webhook-reports from upstream Stripe. It generates no
displayable output except possibly an echoed error message, and sets html
response code 200 to placate Stripe
*/
if (!function_exists('http_response_code')) { //PHP<5.4
 function http_response_code($code)
 {
	switch ($code) {
		case 200: $text = 'OK'; break;
		default: $code = NULL; break;
	}
	if ($code !== NULL) {
		$protocol = ((!empty($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
		header($protocol.' '.$code.' '.$text);
		$GLOBALS['http_response_code'] = $code;
	}
 }
}

//clear all page content echoed before now
$handlers = ob_list_handlers();
if ($handlers) {
	$l = count($handlers);
	for ($c = 0; $c < $l; $c++)
		ob_end_clean();
}
//set relevant secret key
if (isset($params['account']))
	$row = $db->GetRow('SELECT usetest,privtoken,testprivtoken FROM '.
		cms_db_prefix().'module_sgt_account WHERE account_id=?',[$params['account']]);
else
	$row = $db->GetRow('SELECT usetest,privtoken,testprivtoken FROM '.
		cms_db_prefix().'module_sgt_account WHERE isdefault>0 AND isdefault>0');
if ($row) {
	if ($row['usetest']) {
		if ($row['testprivtoken'])
			$privkey = StripeGate\Utils::decrypt_value($this,$row['testprivtoken']);
		else
			$privkey = FALSE;
	} else {
		if ($row['privtoken'])
			$privkey = StripeGate\Utils::decrypt_value($this,$row['privtoken']);
		else
			$privkey = FALSE;
	}
} else
	$privkey = FALSE;
if ($privkey) {
	Stripe\Stripe::setApiKey($privkey);
	//retrieve the request's body and parse it as JSON
	$input = @file_get_contents("php://input");
	$event_json = json_decode($input);
	//TODO do something with $event_json
	echo 'WEBHOOK PROCESSING NOT YET SUPPORTED';
} else {
	echo $this->Lang('err_parameter');
}
//acknowledge receipt
http_response_code(200);
exit;
