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

$fn = $id.'iconfile';
if (isset($_FILES) && isset($_FILES[$fn])) {
	$file_data = $_FILES[$fn];
	if ($file_data['error'] != 0
	 || strpos($file_data['type'],'image/') !== 0) {
		$message = $this->Lang('err_file');
	} else {
		$img = FALSE;
		$tmpname = $file_data['tmp_name'];
		if (function_exists('finfo_fopen')) {
			$finfo = new finfo();
			$mtype = $finfo->file($tmpname,FILEINFO_MIME_TYPE);
			$img = (strpos($mtype,'image/') === 0);
		} elseif (function_exists('getimagesize')) {
			$img = getimagesize($tmpname) ? TRUE:FALSE;
		} elseif (function_exists('exif_imagetype')) {
			$img = (exif_imagetype($tmpname) !== FALSE);
		} elseif (function_exists('mime_content_type')) {
			$mtype = mime_content_type($tmpname);
			$img = (strpos($mtype,'image/') === 0);
		}
		if (!$img)
			$message = $this->Lang('err_file');
	}

	if (empty($message)) {
		$fp = StripeGate\Utils::GetUploadsPath($this);
		if ($fp) {
			$fp = cms_join_path($fp,$file_data['name']);
			if (!chmod($file_data['tmp_name'],0644) ||
				!cms_move_uploaded_file($file_data['tmp_name'],$fp))
				$message = $this->Lang('err_upload');
			else { //all good
				$sql = 'UPDATE '.$pref.'module_sgt_account SET iconfile=? WHERE account_id=?';
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

$tplvars = [
	'start_form' => $this->CreateFormStart($id,'upload_icon',$returnid,'post','multipart/form-data'),
	'end_form' => $this->CreateFormEnd(),
	'hidden' => $this->CreateInputHidden($id,'account_id',$params['account_id']),
	'title' => $this->Lang('title_iconfile2',$name),
	'chooser' => $this->CreateInputFile($id,'iconfile','image/*',48,64),
	'apply' => $this->CreateInputSubmit($id,'upstart',$this->Lang('upload')),
	'cancel' => $this->CreateInputSubmit($id,'upcancel',$this->Lang('cancel')),
	'help' => $this->Lang('help_iconupload')
];

echo StripeGate\Utils::ProcessTemplate($this,'chooser.tpl',$tplvars);
