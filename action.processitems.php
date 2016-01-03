<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if(!$this->CheckPermission('ModifyStripeGateProperties')) exit;

if(!$params['selitems'])
	$this->Redirect($id,'defaultadmin');

if(isset($params['delete']))
{
	foreach($params['selitems'] as $aid)
	{
		//TODO delete 'no-longer-needed' uploaded css|icon files
		$pref = cms_db_prefix();
		$sql = 'DELETE FROM '.$pref.'module_sgt_account WHERE account_id=?';
		$db->Execute($sql,array($aid));
		$sql = 'DELETE FROM '.$pref.'module_sgt_record WHERE account_id=?';
		$db->Execute($sql,array($aid));
	}
}
if(isset($params['export']))
{
$this->Crash();
	foreach($params['selitems'] as $aid)
	{
	}
	exit;
}

$this->Redirect($id,'defaultadmin');
?>
