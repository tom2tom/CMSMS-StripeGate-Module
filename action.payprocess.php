<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
# This action handles ajax-processing after a pay-button is clicked
# It sends back an empty string on successful completion, or else an error message string

/*supplied $params[]
 'stg_account' => account id
 'stg_amount' => private amount
 'stg_token' => stripe token
*/

//clear all page-content echoed before now
$handlers = ob_list_handlers();
if ($handlers) {
	$l = count($handlers);
	for ($c = 0; $c < $l; $c++)
		ob_end_clean();
}

//spl_autoload_register(array('StripeGate\Utils','stripe_classload'));

$row = $db->GetRow('SELECT currency,usetest,privtoken,testprivtoken FROM '.
	cms_db_prefix().'module_sgt_account WHERE account_id=?',[$params['stg_account']]);
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
	die($this->Lang('err_parameter'));
}

$data = [
	'amount' => $params['stg_amount'],
	'currency' => $row['currency'],
	'description' => 'prescribed payment',
	'source' => $params['stg_token']
];

try {
	Stripe\Stripe::setApiKey($privkey);
	$charge = Stripe\Charge::create($data);
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
		$params['stg_amount'],
		'prescribed amount',
		$this->Lang('anonymous'),
		$response['created'],
		$response['id']]);

	die(); //no message = success
} catch (Exception $e) {
	die($e->getMessage());
}
