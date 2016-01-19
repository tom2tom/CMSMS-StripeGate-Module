<?php
#-------------------------------------------------------------------------
# CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# This module provides an interface to the Stripe payments gateway
#
# This module is free software. You can redistribute it and/or modify it under
# the terms of the GNU Affero General Public License as published by the Free
# Software Foundation, either version 3 of that License, or (at your option)
# any later version.
#
# This module is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
# Read the License online: http://www.gnu.org/licenses/licenses.html#AGPL
#-------------------------------------------------------------------------

class StripeGate extends CMSModule
{
	//whether password encryption is supported
	public $havemcrypt;
	public $before20;

	public function __construct()
	{
		parent::__construct();
		$this->havemcrypt = function_exists('mcrypt_encrypt');
		global $CMS_VERSION;
		$this->before20 = (version_compare($CMS_VERSION,'2.0') < 0);
	}

	public function AllowAutoInstall()
	{
		return FALSE;
	}

	public function AllowAutoUpgrade()
	{
		return FALSE;
	}

	//for 1.11+
	public function AllowSmartyCaching()
	{
		return FALSE;
	}

	public function GetName()
	{
		return 'StripeGate';
	}

	public function GetFriendlyName()
	{
		return $this->Lang('friendlyname');
	}

	public function GetHelp()
	{
		return $this->Lang('help_module');
	}

	public function GetVersion()
	{
		return '0.2';
	}

	public function GetAuthor()
	{
		return 'tomphantoo';
	}

	public function GetAuthorEmail()
	{
		return 'tpgww@onepost.net';
	}

	public function GetChangeLog()
	{
		return ''.@file_get_contents(cms_join_path(dirname(__FILE__),'include','changelog.inc'));
	}

	public function IsPluginModule()
	{
		return TRUE;
	}

/*	public function HasCapability($capability,$params = array())
	{
		switch($capability)
		{
			return TRUE;
		 default:
			return FALSE;
		}
	}
*/
	public function HasAdmin()
	{
		return TRUE;
	}

	public function LazyLoadAdmin()
	{
		return TRUE;
	}

	public function GetAdminSection()
	{
		return 'extensions';
	}

	public function GetAdminDescription()
	{
		return $this->Lang('module_description');
	}

	public function VisibleToAdminUser()
	{
		return
		 $this->CheckPermission('ModifyStripeGateProperties') ||
		 $this->CheckPermission('ModifyStripeAccount') ||
		 $this->CheckPermission('UseStripeAccount');
	}

/*	public function AdminStyle()
	{
	}
*/
	public function GetHeaderHTML()
	{
		$url = $this->GetModuleURLPath();
		//the 2nd link is for dynamic style-changes, via js at runtime
		return <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}/css/admin.css" />
<link rel="stylesheet" type="text/css" id="adminstyler" href="#" />
EOS;
	}

	function SuppressAdminOutput(&$request)
	{
		if(isset($_SERVER['QUERY_STRING']))
		{
			if(strpos($_SERVER['QUERY_STRING'],'export') !== FALSE)
				return TRUE;
		}
		return FALSE;
	}

	public function GetDependencies()
	{
		return array();
	}

	public function LazyLoadFrontend()
	{
		//support delivery-report processing at any time
		return FALSE;
	}

	public function MinimumCMSVersion()
	{
		return '1.9';
	}

/*	public function MaximumCMSVersion()
	{
	}
*/

	public function InstallPostMessage()
	{
		return $this->Lang('postinstall');
	}

	public function UninstallPreMessage()
	{
		return $this->Lang('confirm_uninstall');
	}

	public function UninstallPostMessage()
	{
		return $this->Lang('postuninstall');
	}

	//setup for pre-1.10
	public function SetParameters()
	{
		self::InitializeAdmin();
		self::InitializeFrontend();
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	public function InitializeFrontend()
	{
		$this->RegisterModulePlugin(TRUE);

		$this->RestrictUnknownParams();
		$this->SetParameterType('account',CLEAN_STRING);
		$this->SetParameterType('amount',CLEAN_STRING);
		$this->SetParameterType('title',CLEAN_STRING);
		$this->SetParameterType('nosur',CLEAN_INT);
		$this->SetParameterType('formed',CLEAN_INT);
		//for checkout template
		$this->SetParameterType('submit',CLEAN_STRING);
		$this->SetParameterType(CLEAN_REGEXP.'/stg_.*/',CLEAN_NONE);

		$returnid = cmsms()->GetContentOperations()->GetDefaultPageID(); //any valid id will do ?
		$this->RegisterRoute('/[Ss]tripe[Gg]ate\/devreport$/',
		  array('action'=>'devreport',
				'showtemplate'=>'false', //not FALSE, or any of its equivalents !
				'returnid'=>$returnid));
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	public function InitializeAdmin()
	{
		//document only the parameters relevant for external (page-tag) usage
		$this->CreateParameter('action','payplus',$this->Lang('param_action'));
		$this->CreateParameter('account','',$this->Lang('param_account'));
		$this->CreateParameter('amount','',$this->Lang('param_amount'));
		$this->CreateParameter('title','',$this->Lang('param_title'));
		$this->CreateParameter('nosur',0,$this->Lang('param_nosur'));
		$this->CreateParameter('formed',0,$this->Lang('param_formed'));
	}

/*	public function GetEventDescription($eventname)
	{
		switch($eventname)
		{
		 case 'StripeDeliveryReported':
			return $this->Lang('event_desc_delivery');
		 default:
			return '';
		}
	}

	public function GetEventHelp($eventname)
	{
		switch($eventname)
		{
		 case 'StripeDeliveryReported':
			return $this->Lang('event_help_delivery');
		 default:
			return '';
		}
	}

	public function get_tasks()
	{
		return new stripe_clearlog_task();
	}
*/
	function DoAction($name,$id,$params,$returnid='')
	{
		//diversions
		switch ($name)
		{
		 case 'default':
			$name = 'payplus';
			break;
		}
		parent::DoAction($name,$id,$params,$returnid);
	}

	//construct delivery-reports URL (pretty or not)
	public function get_reporturl()
	{
		$returnid = cmsms()->GetContentOperations()->GetDefaultContent();
		//CMSMS 1.10+ has ->create_url();
		return $this->CreateLink('m1_','devreport',$returnid,'',array(),'',
			TRUE,FALSE,'',FALSE,'stripegate/devreport');
	}

}

?>
