<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!$params['selitems'])
	$this->Redirect($id,'defaultadmin');

if (isset($params['delete'])) {
	if (!($this->CheckPermission('ModifyStripeGateProperties')
	  || $this->CheckPermission('ModifyStripeAccount'))) exit;
	$pref = cms_db_prefix();
	$sql = 'DELETE FROM '.$pref.'module_sgt_account WHERE account_id=?';
	$sql2 = 'DELETE FROM '.$pref.'module_sgt_record WHERE account_id=?';
	foreach ($params['selitems'] as $aid) {
		//TODO delete 'no-longer-needed' uploaded css|icon files
		$db->Execute($sql,array($aid));
		$db->Execute($sql2,array($aid));
	}
}
if (isset($params['export'])) {
	if (!$this->CheckPermission('ModifyStripeAccount')) exit;
	$funcs = new sgtExport();
	$res = $funcs->Export($this,$params['selitems']);
	if ($res === TRUE)
		exit;
	unset($funcs);
	$this->Redirect($id,'defaultadmin','',array('message' => $this->Lang($res)));
}

$this->Redirect($id,'defaultadmin');
