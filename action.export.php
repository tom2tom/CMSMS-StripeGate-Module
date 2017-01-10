<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!$this->CheckPermission('ModifyStripeAccount')) exit;

$funcs = new StripeGate\Export();
$res = $funcs->Export($this,$params['account_id']);
if ($res === TRUE)
	exit;
unset($funcs);
$this->Redirect($id,'defaultadmin',$returnid,['message' => $this->Lang($res)]);
