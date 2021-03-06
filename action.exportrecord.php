<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!$this->CheckPermission('ModifyStripeAccount')) exit;

$funcs = new StripeGate\Export();
$res = $funcs->Export($this,FALSE,$params['record_id']);
if ($res === TRUE)
	exit;
unset($funcs);
$this->Redirect($id,'administer','',[
	'account_id'=>$params['account_id'],
	'message' => $this->Lang($res)]);
