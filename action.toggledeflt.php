<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!$this->CheckPermission('ModifyStripeGateProperties')) exit;

$newval = ($params['current']) ? 0:1;
$pref = cms_db_prefix();
if ($newval)
	$db->Execute('UPDATE '.$pref.'module_sgt_account SET isdefault=0');
$db->Execute('UPDATE '.$pref.'module_sgt_account SET isdefault='.$newval.' WHERE account_id=?',[$params['account_id']]);

$this->Redirect($id,'defaultadmin');
