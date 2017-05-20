<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

function rmdir_recursive($dir)
{
	foreach(scandir($dir) as $file) {
		if (!($file === '.' || $file === '..')) {
			$fp = $dir.DIRECTORY_SEPARATOR.$file;
			if (is_dir($fp)) {
				rmdir_recursive($fp);
			} else {
 				@unlink($fp);
			}
		}
	}
	rmdir($dir);
}

/*
$db = cmsms()->GetDb();
$dict = NewDataDictionary($db);
$pref = cms_db_prefix();
$taboptarray = array('mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
 'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci');
*/
switch ($oldversion) {
 case '0.8':
	$fp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'init.php';
	if (is_file($fp)) {
		unlink($fp);
	}
 case '0.8.1':
 case '0.9.0':
	//redundant files
	$base = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.';
	foreach (['sgtUtils','sgtPayer','sgtExport'] as $fn) {
		$fp = $base.$fn.'.php';
		if (is_file($fp)) {
			unlink($fp);
		}
	}
	//redundant directory
	$fp = __DIR__.DIRECTORY_SEPARATOR.'include';
	if (is_dir($fp)) {
		rmdir_recursive($fp);
	}

	$t = 'nQCeESKBr99A';
	$this->SetPreference($t, hash('sha256', $t.microtime()));
	$pw = $this->GetPreference('masterpass');
	if ($pw) {
		$s = base64_decode(substr($pw,5));
		$pw = substr($s,5);
	}
	if (!$pw) {
		$pw = base64_decode('RW50ZXIgYXQgeW91ciBvd24gcmlzayEgRGFuZ2Vyb3VzIGRhdGEh');
	}
	$cfuncs = new StripeGate\Crypter($this);
	$cfuncs->encrypt_preference('masterpass',$pw);
 case '0.10.0':
	$this->SetPreference('transfer_days',45);
}
