<?php
#-------------------------------------------------------------------------
# CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
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

//cURL needed by Stripe API at least
if (!function_exists('curl_init')) {
	echo '<h1 style="color:red;">StripeGate module error: the PHP cURL module is not available.</h1>';
	return;
}

class StripeGate extends CMSModule
{
	public $havecurl = TRUE;
	public $before20;
	public $oldtemplates;

	public function __construct()
	{
		parent::__construct();
		global $CMS_VERSION;
		$this->before20 = (version_compare($CMS_VERSION,'2.0') < 0);
		$this->oldtemplates = $this->before20 || 1; //TODO

		$fp = cms_join_path(__DIR__,'lib','Stripe','Stripe.php'); // Stripe singleton always
		require_once($fp);
		spl_autoload_register([$this,'stripe_spacedload']);
	}

	public function __destruct()
	{
		spl_autoload_unregister([$this,'stripe_spacedload']);
		if (function_exists('parent::__destruct'))
			parent::__destruct();
	}

	/**
	stripe_spacedload:
	Stripe library autoloader. Not for generic StripeGate\Stripe\...,
	cuz the lib can't cope with a 'StripeGate' dir in the file-path
	Suits namespaced Stripe-API-classes (as of library V.3.15.0)
	@classname: string like A[\B...]
	*/
	private function stripe_spacedload($classname)
	{
//		$prefix = get_class().'\\'; //our namespace prefix
		$o = ($classname[0] != '\\') ? 0:1;
/*		$p = strpos($classname, $prefix, $o);
		if ($p === 0 || ($p == 1 && $o == 1)) {
			// directory for the namespace
			$bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
		} else
*/
		if (($p = strpos($classname, 'Stripe\\', $o)) === 0 || ($p == 1 && $o == 1)) {
			$parts = explode('\\',$classname);
			$class = array_pop($parts);
			$bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR
				.implode(DIRECTORY_SEPARATOR,$parts).DIRECTORY_SEPARATOR;
			if (strpos($class,'Stripe_') !== 0) {
				$fn = $class;
			} else {
				$fn = substr($class, 7); //drop the prefix
			}
			//subdirs are hardcoded so we can specify their search-order
			foreach (['', 'Util', 'HttpClient', 'Error'] as $sub) {
				if ($sub) {
					$sub .= DIRECTORY_SEPARATOR;
				}
				$fp = $bp.$sub.$fn.'.php';
				if (@file_exists($fp)) {
					require $fp;
					if (class_exists($classname)) {
						return;
					}
				}
			}
		}
/*		else {
			$p = strpos($classname, '\\', 1);
			if ($p === FALSE) {
				return;
			}
			$prefix = substr($classname, $o, $p-$o);
			$bp = dirname(__DIR__).DIRECTORY_SEPARATOR.$prefix.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
		}
		// relative class name
		$len = strlen($prefix) + $o;
		$relative_class = trim(substr($classname, $len), '\\');

		if (($p = strrpos($relative_class, '\\', -1)) !== FALSE) {
			$relative_dir = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);
			$bp .= substr($relative_dir, 0, $p+1);
			$base = substr($relative_dir, $p+1);
		} else {
			$base = $relative_class;
		}

		$fp = $bp.'class.'.$base.'.php';
		if (file_exists($fp)) {
			include $fp;
			return;
		}
		$fp = $bp.$base.'.php';
		if (file_exists($fp)) {
			include $fp;
		}
 */
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
		return ''.@file_get_contents(cms_join_path(__DIR__,'lib','doc','modhelp.htm'));
	}

	public function GetVersion()
	{
		return '0.11';
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
		return ''.@file_get_contents(cms_join_path(__DIR__,'lib','doc','changelog.htm'));
	}

	public function IsPluginModule()
	{
		return TRUE;
	}

	public function HasCapability($capability, $params = [])
	{
		switch ($capability) {
			case 'plugin':
			case 'tasks':
			case 'GatePayer':
				return TRUE;
		}
		return FALSE;
	}

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
		return 'ecommerce';
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

	public function SuppressAdminOutput(&$request)
	{
		if (isset($_SERVER['QUERY_STRING'])) {
			if (strpos($_SERVER['QUERY_STRING'],'export') !== FALSE) {
				return TRUE;
			}
		}
		if (isset($request['mact'])) {
			if (strpos($request['mact'],'transfers',6)) {
				if (isset($request['m1_export'])) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	public function GetDependencies()
	{
		return [];
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
		//for showform action
		$this->SetParameterType('cancel',CLEAN_NONE);
//		$this->SetParameterType('contact',CLEAN_STRING);
		$this->SetParameterType('currency',CLEAN_STRING);
		$this->SetParameterType('customer',CLEAN_STRING);
		$this->SetParameterType('message',CLEAN_STRING);
		$this->SetParameterType('passthru',CLEAN_STRING);
//		$this->SetParameterType('payee',CLEAN_STRING);
		$this->SetParameterType('payer',CLEAN_STRING);
		$this->SetParameterType('payfor',CLEAN_STRING);
		$this->SetParameterType('preserve',CLEAN_STRING);
//		$this->SetParameterType('senddata',CLEAN_STRING);
		$this->SetParameterType('surrate',CLEAN_STRING);
		$this->SetParameterType('withcancel',CLEAN_INT);
		//for checkout template
		$this->SetParameterType('submit',CLEAN_STRING);
		//for all
		$this->SetParameterType(CLEAN_REGEXP.'/stg_.*/',CLEAN_NONE);
/* webhook reports not supported ATM
		$this->SetParameterType('showtemplate',CLEAN_STRING);

		$returnid = cmsms()->GetContentOperations()->GetDefaultPageID(); //any valid id will do ?
		$this->RegisterRoute('/[Ss]tripe[Gg]ate\/webhook$/',
		  array('action'=>'webhook',
				'showtemplate'=>'false', //not FALSE, or any of its equivalents !
				'returnid'=>$returnid));
*/
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
		switch ($eventname) {
		 case 'StripeDeliveryReported':
			return $this->Lang('event_desc_delivery');
		 default:
			return '';
		}
	}

	public function GetEventHelp($eventname)
	{
		switch ($eventname) {
		 case 'StripeDeliveryReported':
			return $this->Lang('event_help_delivery');
		 default:
			return '';
		}
	}

	public function get_tasks()
	{
		if ($this->before20) {
			return new sgtClearlogTask();
		} else {
			return new StripeGate\ClearlogTask();
		}
	}
*/
	//Support for GatePayer interface

	/**
	Get name of of StripeGate class which performs 'interface' duties in
	accord with interface GatePay
	Returns: string, not the file i.e. no leading 'class.' or trailing '.php'.
	May be namespaced like 'StripeGate\classname'
	*/
	public function GetPayer()
	{
		return 'StripeGate\Payer';
	}

	public function DoAction($name, $id, $params, $returnid='')
	{
		//diversions
		switch ($name) {
		 case 'default':
			$name = 'payplus';
			break;
		 case 'processrecords':
			if (isset($params['transfers'])) {
				$name = 'transfers';
			}
			break;
		}
		parent::DoAction($name,$id,$params,$returnid);
	}
}
