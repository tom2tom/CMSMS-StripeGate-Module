<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!$this->CheckPermission('ModifyStripeAccount')) exit;

if (!$params['sel'])
	$this->Redirect($id,'administer','',['account_id'=>$params['account_id']]);

if (isset($params['delete'])) {
	$pref = cms_db_prefix();
	$sql = 'DELETE FROM '.$pref.'module_sgt_record WHERE record_id=?';
	foreach ($params['sel'] as $rid)
		$db->Execute($sql,[$rid]);
	$more = $db->GetOne('SELECT record_id FROM '.$pref.'module_sgt_record');
	if ($more)
		$this->Redirect($id,'administer','',['account_id'=>$params['account_id']]);
}
if (isset($params['export'])) {
	$funcs = new StripeGate\Export();
	$res = $funcs->Export($this,FALSE,$params['sel']);
	if ($res === TRUE)
		exit;
	unset($funcs);
	$this->Redirect($id,'administer','',[
		'account_id'=>$params['account_id'],
		'message' => $this->Lang($res)]);
}

$this->Redirect($id,'defaultadmin');
