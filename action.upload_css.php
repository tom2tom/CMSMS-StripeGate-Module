<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!($this->CheckPermission('ModifyStripeGateProperties')
  || $this->CheckPermission('ModifyStripeAccount'))) exit;

if (isset($params['upcancel']))
	$this->Redirect($id,'update',$returnid,['account_id'=>$params['account_id']]);

$pref = cms_db_prefix();

$fn = $id.'stylesfile';
if (isset($_FILES) && isset($_FILES[$fn])) {
	$file_data = $_FILES[$fn];
	$parts = explode('.',$file_data['name']);
	$ext = end($parts);
	if ($file_data['type'] != 'text/css'
	 || !($ext == 'css' || $ext == 'CSS')
	 || $file_data['size'] <= 0 || $file_data['size'] > 2048 //plenty big enough in this context
	 || $file_data['error'] != 0)
	{
		$message = $this->Lang('err_file');
	} else {
		$h = fopen($file_data['tmp_name'],'r');
		if ($h) {
			//basic validation of file-content
			$content = fread($h,512);
			fclose($h);
			if ($content == FALSE)
				$message = $this->Lang('err_permission');
			if (!preg_match('/#container.*\\n?{/',$content)) //TODO any actual newline
				$message = $this->Lang('err_file');
			unset($content);
		} else
			$message = $this->Lang('err_permission');
	}

	if (empty($message)) {
		$fp = StripeGate\Utils::GetUploadsPath($this);
		if ($fp) {
			$fp = cms_join_path($fp,$file_data['name']);
			if (!chmod($file_data['tmp_name'],0644) ||
				!cms_move_uploaded_file($file_data['tmp_name'],$fp))
				$message = $this->Lang('err_upload');
			else { //all good
				$sql = 'UPDATE '.$pref.'module_sgt_account SET stylesfile=? WHERE account_id=?';
				$db->Execute($sql,[$file_data['name'],$params['account_id']]);
			}
		} else
			$message = $this->Lang('err_upload');
	}
	if (empty($message))
		$message = FALSE;
	$this->Redirect($id,'update',$returnid,['account_id'=>$params['account_id'],'message'=>$message]);
}

$name = $db->GetOne('SELECT name FROM '.$pref.'module_sgt_account WHERE account_id=?',[$params['account_id']]);

$fn = cms_join_path(__DIR__,'css','payplus.css');
$styles = @file_get_contents($fn);
if ($styles) {
	$example = preg_replace(['~\s?/\*(.*)?\*/~Usm','~\s?//.*$~m'],['',''],$styles);
	$example = str_replace([PHP_EOL.PHP_EOL,PHP_EOL,"\t"],['<br />','<br />',' '],trim($example));
} else
	$example = $this->Lang('missing');

$tplvars = [
	'start_form' => $this->CreateFormStart($id,'upload_css',$returnid,'post','multipart/form-data'),
	'end_form' => $this->CreateFormEnd(),
	'hidden' => $this->CreateInputHidden($id,'account_id',$params['account_id']),
	'title' => $this->Lang('title_cssfile',$name),
	'chooser' => $this->CreateInputFile($id,'stylesfile','text/css',48,64),
	'apply' => $this->CreateInputSubmit($id,'upstart',$this->Lang('upload')),
	'cancel' => $this->CreateInputSubmit($id,'upcancel',$this->Lang('cancel')),
	'help' => $this->Lang('help_cssupload',$example)
];

echo StripeGate\Utils::ProcessTemplate($this,'chooser.tpl',$tplvars);
