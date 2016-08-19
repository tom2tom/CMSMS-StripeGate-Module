<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

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
	if (is_file($fp))
		unlink($fp);
 case '0.8.1':
	$base = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.';
	foreach (array('sgtUtils','sgtPayer','sgtExport') as $fn) {
		$fp = $base.$fn.'.php';
		if (is_file($fp))
			unlink($fp);
	}
}
