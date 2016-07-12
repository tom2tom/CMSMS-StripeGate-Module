<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!$this->CheckPermission('ModifyStripeAccount')) exit;

$rid = (int)$params['record_id'];
$pref = cms_db_prefix();
$sql = 'DELETE FROM '.$pref.'module_sgt_record WHERE record_id=?';
$db->Execute($sql,array($rid));
$more = $db->GetOne('SELECT record_id FROM '.$pref.'module_sgt_record');
if ($more)
	$this->Redirect($id,'administer','',array('account_id'=>$params['account_id']));
else
	$this->Redirect($id,'defaultadmin');
