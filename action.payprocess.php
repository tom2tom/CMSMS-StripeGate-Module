<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
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

try {
	require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'init.php');
	$msg = '';	//default to 'successful-completion-signal'
} catch (Exception $e) {
	$msg = $this->Lang('err_system'); //TODO
}

if(!$msg)
{
	$pref = cms_db_prefix();
	$row = $db->GetRow('SELECT currency,usetest,privtoken,testprivtoken FROM '.
	$pref.'module_sgt_account WHERE account_id=?',array($params['stg_account']));
	if($row['usetest'])
	{
		if($row['testprivtoken'])
			$privkey = sgtUtils::decrypt_value($this,$row['testprivtoken']);
		else
			$privkey = FALSE;
	}
	else
	{
		if($row['privtoken'])
			$privkey = sgtUtils::decrypt_value($this,$row['privtoken']);
		else
			$privkey = FALSE;
	}
	if(!$privkey)
	{
		$msg = $this->Lang('err_parameter');
	}
}

if(!$msg)
{
	$data = array(
		'amount' => $params['stg_amount'],
		'currency' => $row['currency'],
		'description' => 'prescribed payment',
		'source' => $params['stg_token']
	);

	try
	{
		Stripe::setApiKey($privkey);
		$charge = Stripe_Charge::create($data);
		$response = $charge->__toArray(TRUE);
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
			$params['stg_amount'],
			'prescribed amount',
			'anonymous',
			$response['created'],
			$response['id']));
	}
	catch (Exception $e)
	{
		$msg = $e->getMessage();
	}
}

//clear all page content echoed before now
$handlers = ob_list_handlers();
if($handlers)
{
	$l = count($handlers);
	for ($cnt = 0; $cnt < $l; $cnt++)
		ob_end_clean();
}

die($msg);

?>
