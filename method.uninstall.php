<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!$this->CheckPermission('ModifyStripeGateProperties')) return;

$dict = NewDataDictionary($db);
$pref = cms_db_prefix();

$fp = $config['uploads_path'];
if ($fp && is_dir($fp)) {
	$ud = $this->GetPreference('uploads_dir','');
	if ($ud) {
		$fp .= DIRECTORY_SEPARATOR.$ud;
		if ($fp && is_dir($fp))
			recursive_delete($fp);
	} else {
		$files = $db->GetCol("SELECT DISTINCT stylesfile FROM ".$pref.
		"module_sgt_account WHERE stylesfile<>''"); //also excludes NULL's
		if ($files) {
			foreach ($files as $fn) {
				$fn = cms_join_path($fp,$fn);
				if (is_file($fn))
					unlink($fn);
			}
		}
		$files = $db->GetCol("SELECT DISTINCT iconfile FROM ".$pref.
		"module_sgt_account WHERE iconfile<>''");
		if ($files) {
			foreach ($files as $fn) {
				$fn = cms_join_path($fp,$fn);
				if (is_file($fn))
					unlink($fn);
			}
		}
	}
}

$sqlarray = $dict->DropTableSQL($pref.'module_sgt_account');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_sgt_record');
$dict->ExecuteSQLArray($sqlarray);

$this->RemovePreference();

$this->RemovePermission('UseStripeAccount');
$this->RemovePermission('ModifyStripeAccount');
$this->RemovePermission('ModifyStripeGateProperties');

//$this->RemoveEvent($this->GetName(),'');
