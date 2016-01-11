<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

$pmod = $this->CheckPermission('ModifyStripeGateProperties')
	|| $this->CheckPermission('ModifyStripeAccount');
$puse = $this->CheckPermission('UseStripeAccount');
if(!($pmod || $puse)) exit;

if(isset($params['cancel']))
	$this->Redirect($id,'defaultadmin');

$pref = cms_db_prefix();
if(isset($params['submit']) && $pmod)
{
	$alias = sgtUtils::ConstructAlias($params['alias'],$params['name']);
	$privatetoken = ($params['privtoken']) ? sgtUtils::encrypt_value($this,$params['privtoken']) : '';
	$privatetesttoken = ($params['testprivtoken']) ? sgtUtils::encrypt_value($this,$params['testprivtoken']) : '';
	if(strpos($params['surchargerate'],'%') !== FALSE)
	{
		$sur = str_replace('%','',$params['surchargerate']);
		$sur = (float)$sur / 100.0;
	}
	else
		$sur = $params['surchargerate'] + 0.0;
	$test = !empty($params['usetest']);
	$default = !empty($params['isdefault']);
	if($default)
	{
		//clear old default
		$db->Execute('UPDATE '.$pref.'module_sgt_account SET isdefault=FALSE WHERE isdefault=TRUE');
	}
	$active = !empty($params['isactive']);
	$fmt = trim($params['amountformat']);
	if(!$fmt || !preg_match('/^(.*)?S(\W+)?(\d*)$/',$fmt))
		$fmt = 'S.00';

	if($params['account_id'] > 0)
	{
		$db->Execute('UPDATE '.$pref.'module_sgt_account SET
name=?,
alias=?,
title=?,
currency=?,
amountformat=?,
minpay=?,
surchargerate=?,
owner=?,
usetest=?,
pubtoken=?,
privtoken=?,
testpubtoken=?,
testprivtoken=?,
stylesfile=?,
iconfile=?,
isdefault=?,
isactive=?
WHERE account_id=?',array(
$params['name'],
$alias,
$params['title'],
$params['currency'],
$fmt,
(float)$params['minpay'],
$sur,
(int)$params['owner'],
$test,
$params['pubtoken'],
$privatetoken,
$params['testpubtoken'],
$privatetesttoken,
$params['stylesfile'],
$params['iconfile'],
$default,
$active,
$params['account_id']
));
	}
	else
	{
		$db->Execute('INSERT INTO '.$pref.'module_sgt_account (
name,
alias,
title,
currency,
amountformat,
minpay,
surchargerate,
owner,
usetest,
pubtoken,
privtoken,
testpubtoken,
testprivtoken,
stylesfile,
iconfile,
isdefault,
isactive
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
		array(
$params['name'],
$alias,
$params['title'],
$params['currency'],
$fmt,
(float)$params['minpay'],
$sur,
(int)$params['owner'],
$test,
$params['pubtoken'],
$privatetoken,
$params['testpubtoken'],
$privatetesttoken,
$params['stylesfile'],
$params['iconfile'],
$default,
$active
		));
	}
	$this->Redirect($id,'defaultadmin','',array('message'=>$this->Lang('updated')));
}

if(isset($params['upstyles']))
	$this->Redirect($id,'upload_css','',array('account_id'=>$params['account_id']));
if(isset($params['upicon']))
	$this->Redirect($id,'upload_icon','',array('account_id'=>$params['account_id']));

if(!is_numeric($params['account_id']) || $params['account_id'] > 0)
{
	if(is_numeric($params['account_id']))
		$row = $db->GetRow('SELECT * FROM '.$pref.'module_sgt_account WHERE account_id=?',array($params['account_id']));
	else
		$row = $db->GetRow('SELECT * FROM '.$pref.'module_sgt_account WHERE alias=?',array($params['account_id']));

	if(!$row)
		$this->Redirect($id,'defaultadmin','',array('message'=>$this->Lang('err_system')));

	$account_id = $params['account_id'];
}
else
{
	$row = array(
'name'=>'',
'alias'=>'',
'title'=>'',
'currency'=>'usd',
'amountformat'=>'',
'minpay'=>0.0,
'surchargerate'=>0.0,
'owner'=>0,
'usetest'=>false,
'pubtoken'=>'',
'privtoken'=>'',
'testpubtoken'=>'',
'testprivtoken'=>'',
'stylesfile'=>'',
'iconfile'=>'',
'isdefault'=>false,
'isactive'=>true
	);
	$account_id = -1;
}

$smarty->assign('backtomod_nav',$this->CreateLink($id,'defaultadmin',$returnid,
'&#171; '.$this->Lang('title_mainpage')));
if(!empty($params['message']))
	$smarty->assign('message',$params['message']);

$smarty->assign('form_start',$this->CreateFormStart($id,'update'));
$smarty->assign('form_end',$this->CreateFormEnd());
$smarty->assign('hidden',$this->CreateInputHidden($id,'account_id',$params['account_id']));

$jsincs = array();
$jsfuncs = array();
$jsloads = array();
$baseurl = $this->GetModuleURLPath();
$settings = array();
if(!$pmod)
	$empty = '&lt;'.$this->Lang('none').'&gt;';

$oneset = new stdClass();
$oneset->title = $this->Lang('title_name');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'name',$row['name'],48,128);
elseif($row['name'])
	$oneset->input = $row['name'];
else
	$oneset->input = $empty;
//$oneset->help = $this->Lang('');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_alias2');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'alias', $row['alias'],16,16);
elseif($row['alias'])
	$oneset->input = $row['alias'];
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('help_alias');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_title');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'title',$row['title'],48,64);
elseif($row['title'])
	$oneset->input = $row['title'];
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('help_title');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_active');
if($pmod)
	$oneset->input = $this->CreateInputCheckbox($id,'isactive',1,$row['isactive']);
else
	$oneset->input = ($row['isactive'])?$this->Lang('yes'):$this->Lang('no');
//$oneset->help = $this->Lang('');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_defaultlong');
if($pmod)
	$oneset->input = $this->CreateInputCheckbox($id,'isdefault',1,$row['isdefault']);
else
	$oneset->input = ($row['isdefault'])?$this->Lang('yes'):$this->Lang('no');
//$oneset->help = $this->Lang('');
$settings[] = $oneset;

$choices = sgtUtils::GetSupportedCurrencies();
$oneset = new stdClass();
$oneset->title = $this->Lang('title_currency');
if($pmod)
	$oneset->input = $this->CreateInputDropdown($id,'currency',$choices,-1,$row['currency']);
else
	$oneset->input = array_search($row['currency'],$choices);
//$oneset->help = $this->Lang('');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_amountformat');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'amountformat',$row['amountformat'],8,8);
elseif($row['amountformat'])
	$oneset->input = $row['amountformat'];
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('help_amountformat');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_minpay');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'minpay',$row['minpay'],6,6);
elseif($row['minpay'])
	$oneset->input = $row['minpay'];
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('help_minpay');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_surchargerate');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'surchargerate',$row['surchargerate'],6,6);
elseif($row['surchargerate'])
	$oneset->input = $row['surchargerate'];
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('help_surchargerate');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_stylesfile');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'stylesfile',$row['stylesfile'],48,48).' '
	.$this->CreateInputSubmit($id,'upstyles',$this->Lang('upload'),
	'title="'.$this->Lang('tip_upload').'"');
elseif($row['stylesfile'])
	$oneset->input = $row['stylesfile'];
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('help_stylesfile');
$settings[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_iconfile');
if($pmod)
	$oneset->input = $this->CreateInputText($id,'iconfile',$row['iconfile'],48,48).' '
	.$this->CreateInputSubmit($id,'upicon',$this->Lang('upload'),
	'title="'.$this->Lang('tip_upload').'"');
elseif($row['iconfile'])
	$oneset->input = $row['iconfile'];
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('help_iconfile');
$settings[] = $oneset;

if($pmod)
{
	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_pubtoken');
	$oneset->input = $this->CreateInputText($id,'pubtoken', $row['pubtoken'],32,48);
//	$oneset->help = $this->Lang('');
	$settings[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_privtoken');
	$oneset->input = $this->CreateInputText($id,'privtoken',sgtUtils::decrypt_value($this,$row['privtoken']),32,48);
//	$oneset->help = $this->Lang('');
	$settings[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_testpubtoken');
	$oneset->input = $this->CreateInputText($id,'testpubtoken', $row['testpubtoken'],32,48);
//	$oneset->help = $this->Lang('');
	$settings[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_testprivtoken');
	$oneset->input = $this->CreateInputText($id,'testprivtoken',sgtUtils::decrypt_value($this,$row['testprivtoken']),32,48);
	//$oneset->help = $this->Lang('');
	$settings[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_usetest');
	$oneset->input = $this->CreateInputCheckbox($id,'usetest',1,$row['usetest']);
//	$oneset->help = $this->Lang('');
	$settings[] = $oneset;

	$none = '&lt;'.$this->Lang('none').'&gt;';
	$choices = array($none=>0);
	//CMSMS function check_permission() is buggy, always returns false for
	//everyone other than the current user, so we replicate its backend operation here
	$pref = cms_db_prefix();
	$sql = <<<EOS
SELECT DISTINCT U.user_id,U.username,U.first_name,U.last_name FROM {$pref}users U
JOIN {$pref}user_groups UG ON U.user_id = UG.user_id
JOIN {$pref}group_perms GP ON GP.group_id = UG.group_id
JOIN {$pref}permissions P ON P.permission_id = GP.permission_id
JOIN {$pref}groups GR ON GR.group_id = UG.group_id
WHERE U.admin_access=1 AND U.active=1 AND GR.active=1 AND P.permission_name='ModifyStripeAccount'
ORDER BY U.last_name,U.first_name
EOS;
	$users = $db->GetAll($sql);
	if($users)
	{
		$any = '&lt;'.$this->Lang('any').'&gt;';
		$choices[$any] = -1;
		foreach($users as &$one)
		{
			$t = trim($one['first_name'].' '.$one['last_name']);
			if(!$t)
				$t = $one['username'];
			$choices[$t] = (int)$one['user_id'];
		}
		unset($one);
	}
	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_owner');
	if($pmod)
		$oneset->input = $this->CreateInputDropdown($id,'owner',$choices,-1,$row['owner']);
	else
		$oneset->input = array_search($row['owner'],$choices);
	$oneset->help = $this->Lang('help_owner');
	$settings[] = $oneset;
}

$jsincs[] = '<script type="text/javascript" src="'.$baseurl.'/include/jquery-inputCloak.min.js"></script>';
$jsloads[] = <<<EOS
 $('#{$id}passwd').inputCloak({
  type:'see4',
  symbol:'\u2022'
 });

EOS;
/*
$oneset = new stdClass();
$oneset->title = $this->Lang('');
if($pmod)
	$oneset->input = $this->CreateInput();
elseif()
	$oneset->input = 
else
	$oneset->input = $empty;
$oneset->help = $this->Lang('');
$settings[] = $oneset;
*/

$smarty->assign('settings',$settings);

if($pmod)
{
	$smarty->assign('submit',$this->CreateInputSubmit($id,'submit',$this->Lang('submit')));
	$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));
}
else
{
	$smarty->assign('submit',NULL);
	$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('close')));
}

if($jsloads)
{
	$jsfuncs[] = '$(document).ready(function() {
';
	$jsfuncs = array_merge($jsfuncs,$jsloads);
	$jsfuncs[] = '});
';
}
$smarty->assign('jsfuncs',$jsfuncs);
$smarty->assign('jsincs',$jsincs);

echo $this->ProcessTemplate('update.tpl');

?>
