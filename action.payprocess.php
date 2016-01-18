<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
# This action handles ajax-processing after a pay-button is clicked
/*supplied $params[]
 'stg_account' => account id
 'stg_amount' => private amount 
 'stg_token' => stripe token
*/
try {
	require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'init.php');
} catch (Exception $e) {
	echo $this->Lang('err_system');
	exit;
}
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
	echo $this->Lang('err_parameter');
	exit;
}

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

	echo 0; //success
}
catch (Exception $e)
{
	echo $e->getMessage();
}
exit;

?>
