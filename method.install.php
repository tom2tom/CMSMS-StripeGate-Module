<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
//check key depedencies
if (!function_exists('curl_init'))
	return 'Stripe Gateway needs the PHP cURL extension';
if (!function_exists('json_decode'))
	return 'Stripe Gateway needs the PHP json extension.';
if (!function_exists('mb_detect_encoding'))
	return 'Stripe Gateway needs the PHP mbstring extension.';

$taboptarray = ['mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
 'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
$dict = NewDataDictionary($db);

/* private API tokens are encrypted, suitable field-type is (postgres supported pre-1.11) */
$ftype = (preg_match('/mysql/i',$config['dbms'])) ? 'VARBINARY(512)':'BIT VARYING(4096)';
/* ADODB converts F() to DOUBLE() on mysql at least, so we use FLOAT() here */
$flds = "
account_id I(4) KEY AUTO,
name C(128),
alias C(16),
title C(64),
currency C(3) DEFAULT 'usd',
amountformat C(8) DEFAULT 'S.00',
minpay FLOAT(5.2) DEFAULT 0,
surchargerate FLOAT(6.4) DEFAULT 0,
owner I DEFAULT 0,
usetest I(1) DEFAULT 0,
pubtoken C(64),
privtoken ".$ftype.",
testpubtoken C(64),
testprivtoken ".$ftype.",
stylesfile C(64),
iconfile C(64),
isdefault I(1) DEFAULT 0,
isactive I(1) DEFAULT 1
";
$pref = cms_db_prefix();
$sqlarray = $dict->CreateTableSQL($pref.'module_sgt_account',$flds,$taboptarray);
$ares = $dict->ExecuteSQLArray($sqlarray);

//'amount' is in cents, 'recorded' is a timestamp, 'identifier' is Stripe key
//
$flds = "
record_id I KEY AUTO,
account_id I(4),
amount I,
recorded I,
identifier C(48),
paywhat C(64),
payfor C(64)
";
$sqlarray = $dict->CreateTableSQL($pref.'module_sgt_record',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$ud = $config['uploads_path'];
if ($ud && is_dir($ud)) {
	$name = $this->GetName();
	$ud = cms_join_path($ud,$name);
	if (!is_dir($ud)) {
		mkdir($ud,0755);
	}
	$this->SetPreference('uploads_dir',$name); //path relative to host uploads dir
} else {
	$this->SetPreference('uploads_dir',FALSE);
}

$this->SetPreference('transfer_days',45);

$t = 'nQCeESKBr99A';
$this->SetPreference($t, hash('sha256', $t.microtime()));
$cfuncs = new StripeGate\Crypter($this);
$cfuncs->encrypt_preference('masterpass',base64_decode('RW50ZXIgYXQgeW91ciBvd24gcmlzayEgRGFuZ2Vyb3VzIGRhdGEh'));

$this->CreatePermission('UseStripeAccount',$this->Lang('perm_use'));
$this->CreatePermission('ModifyStripeAccount',$this->Lang('perm_mod'));
$this->CreatePermission('ModifyStripeGateProperties',$this->Lang('perm_adm'));
//$this->CreateEvent('');
