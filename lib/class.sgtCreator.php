<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
# This provides a generic interface for other modules to initiate a payment via
# Stripe and get feedback from that payment process. Other gateways could readily
# employ the analogous interface

class sgtCreator //implements ePayInterface
{
	private $mod; //reference to a module-object, generally the initiator's or briefly StripeGate's
	private $translates = NULL;
	private $handler = NULL;
	private $type = 0; //enum for type of $handler
	private $ready = FALSE;

	public function __construct(&$mod)
	{
		$this->mod = $mod;
	}

	/**
	GetConverts:
	Get the 'standard' names which may be used for parameter-key translations
	between 'initiator-normal' and 'gateway-API-normal'
	Returns: array with keys for all the parameter names, all values FALSE
	Those are: for dialog setup
	 'account' id of gateway account (NOT customer-account at the gateway)
	 'amount' headline amount also for feedback what actually paid
	 'cancel' whether to display cancel-button also for feedback (user cancelled)
	 'contact' c.f. stripe 'receipt_email'
	 'currency' 3-char country code
	 'customer' user-account identifier recognised by the gateway also for feedback
	 'message' in-dialog message
	 'payer' who is paying or being paid for
	 'payfor' description of what is being bought
	 'senddata' for Stripe, a json object with key:value pairs attached to charge
	 'surcharge' rate (as bare decimal or decimal with appended '%')
	and for feedback
	 'cardcvc'
	 'cardmonth'
	 'cardname'
	 'cardnumber'
	 'cardyear'
	 'customer'
	 'errmsg'
	 'receivedata'
	 'success'
	 'successmsg'
	 'transactid'

	NOTE key 'passthru' is reserved for internal use
	*/
	public function GetConverts()
	{
		return array(
	//for dialog setup
		 'account'=>FALSE,
		 'amount'=>FALSE, //also feedback (actually paid)
		 'cancel'=>FALSE, //also feedback (user cancelled)
		 'contact'=>FALSE,
		 'currency'=>FALSE,
		 'customer'=>FALSE,
		 'message'=>FALSE,
		 'payer'=>FALSE,
		 'payfor'=>FALSE,
		 'senddata'=>FALSE,
		 'surcharge'=>FALSE,
	//for feedback from dialog
		 'cardcvc'=>FALSE,
		 'cardmonth'=>FALSE,
		 'cardname'=>FALSE,
		 'cardnumber'=>FALSE,
		 'cardyear'=>FALSE,
		 'customer'=>FALSE,
		 'errmsg'=>FALSE,
		 'receivedata'=>FALSE,
		 'success'=>FALSE,
		 'successmsg'=>FALSE,
		 'transactid'=>FALSE
		);
	}

	/**
	SetConverts:
	Store array of key-translations, whereby the parameter-identifiers used by
	the initiator can be recognised and replaced by Stripe-specific identifiers
	used for communications to/from Stripe via its API.
	Must call this before ShowForm()
	@alternates: array with keys being some or all of the 'standard' names
	 and values being the respective corresponding 'initiator-normal' names
	Returns: nothing
	*/
	public function SetConverts($alternates)
	{
		$this->translates = array_merge($this->GetConverts(),$alternates);
		$this->ready = ($this->handler !== NULL);
	}

	/*
	per http://stackoverflow.com/questions/28047640/determining-if-a-closure-is-static-in-php
	if binding works, a closure will have a bound $this, so bind
	something and check for $this - if NULL, the closure is static
	*/
/*	private function isStatic(Closure $closure)
	{
		return (new ReflectionFunction(@Closure::bind($closure, new stdClass)))->getClosureThis() == NULL;
	}
*/
	/**
	SetResultHandler:
	Store information about how to provide feedback to the initiator, when available
	Must call this before ShowForm()
	@handler: mixed, one of
	 an array (classname,methodname) where methodname is static
	 a string 'classname::methodname'
	 an array (modulename,actionname)
	 an URL like <server-root-url>/index.php?mact=<modulename>,cntnt01,<actionname>,0
	 	- provided the PHP curl extension is available
	 NOT a closure in a static context (PHP 5.3+) OR static closure (PHP 5.4+)
	 cuz those aren't transferrable between requests
	See action.webhook.php for example of a hander-action fed by a HTTP request
	Returns: boolean representing acceptability of @handler
	*/
	public function SetResultHandler($handler)
	{
		$type = FALSE;
		if (is_callable($handler)) { //BUT the class may have a __call() method
			if (is_array($handler && count($handler) == 2)) {
				$method = new ReflectionMethod($handler);
				if ($method && $method->isStatic()) {
					$type = 1;
				}
			} elseif (is_string($handler) && strpos($handler,'::') !== FALSE) {
				//PHP 5.2.3+, supports passing 'ClassName::methodName'
				$method = new ReflectionMethod($handler);
				if ($method && $method->isStatic()) {
					$type = 1;
				}
			} /* elseif (is_object($handler) && ($handler instanceof Closure)) {
				if ($this->isStatic($handler)) {
					$type = 3;
				}
			}
*/
		} elseif (is_array($handler) && count($handler) == 2) {
			$ob = cms_utils::get_module($handler[0]);
			if ($ob) {
				$fp = $ob->GetModulePath();
				$offs = strpos($fp,$ob->GetName());
				unset($ob);
				$fp = substr($fp,0,$offs).'action.'.$handler[1].'.php';
				if (@is_file($fp)) {
					$type = 4;
				}
			}
		} elseif (is_string($handler)) {
			$ob = cms_utils::get_module('StripeGate');
			if ($ob->havecurl) { //curl is installed
				$config = cmsms()->GetConfig();
				$u = (empty($_SERVER['HTTPS'])) ? $config['root_url'] : $config['ssl_url'];
				$u .= '/index.php?mact=';
				$len = strlen($u);
				if (strncasecmp($u,$handler,$len) == 0) {
					$type = 5;
				}
			}
		}

		if ($type !== FALSE) {
			$this->type = $type;
			$this->handler = $handler;
			$this->ready = ($this->translates !== NULL);
			return TRUE;
		}
		$this->ready = FALSE;
		return FALSE;
	}

	/**
	HandleResult:
	Interpret @json and send relevant data back to the initiator, then go
	to originating page
	@params: request-parameters to be used, including some from Stripe
	Returns: nope, it redirects
	*/
	public function HandleResult($params)
	{
		//decode $params['stg_passthru'] to revert object-properties
		$props = json_decode($params['stg_passthru']);
		if ($props !== NULL) {
			$arr = (array)$props;
			foreach ($arr as $key=>$val) {
				if ($key == 'mod') {
					$this->mod = cms_utils::get_module($val);
				} else {
					$this->$key = $val;
				}
			}
		} else {
			echo 'TODO error message';
			exit;
		}
	
		$locals = array(
//		 'account'=>,
		 'amount'=>'amount', //in smallest currency-units
		 'cancel'=>'cancel', //cancel-button clicked
		 'cardcvc'=>'stg_cvc',
		 'cardmonth'=>'exp_month',
		 'cardname'=>'stg_name',
		 'cardnumber'=>'stg_number',
		 'cardyear'=>'exp_year',
		 'contact'=>'name',
		 'currency'=>'currency',
		 'customer'=>'customer',
		 'description'=>'description',
		 'errmsg'=>'failure_message',
		 'receivedata'=>FALSE, //'metadata',
		 'success'=>'paid', //boolean
//		 'successmsg'=>,
		 'transactid'=>'id'
		);

		foreach ($params as $key=>$value) {
			$standard = array_search($key,$locals);
			if ($standard !== FALSE) {
				if (array_key_exists($standard,$this->translates)) {
					$newk = $this->translates[$standard];
					$params[$newk] = $value;
				}
			}
			unset($params[$key]);
		}

		switch ($this->type) {
		 case 1: //callable, 2-member array or string like 'ClassName::methodName'
			call_user_func_array($this->handler,$params);
			break;
/*		 case 3: //static closure
			$this->handler($params);
			break; */
		 case 4: //module action
			$ob = cms_utils::get_module($this->handler[0]);
			$ob->DoAction($this->handler[1],'cntnt01',$params); //the $id is default CMSMS action-id
			unset($ob);
			break;
		 case 5: //URL
			$ch = curl_init();
			//can't be bothered with GET URL construction
			curl_setopt_array($ch,array(
			 CURLOPT_RETURNTRANSFER => 1,
			 CURLOPT_URL => $this->handler,
			 CURLOPT_POST => 1,
			 CURLOPT_POSTFIELDS => $params
			));
			$res = curl_exec($ch); //TODO handle error
			curl_close($ch);
			break;
		}

		$this->mod->Redirect('cntnt01',$params['action'],$params['returnid'],$params); //TODO check action, returnid
	}

	/**
	ShowForm:
	Construct and display a payment 'form' for the user to populate and submit.
	@id: action-id specified by the initiator's module, probably 'cntnt01' or similar
	@returnid: the id of the page being displayed by the caller's module
	@params associative array of data to be applied to the form. Keys will be
		translated before use, consistent with settings supplied via SetConverts()
	Returns: FALSE if translations not ready, otherwise it redirects
	*/
	public function ShowForm($id,$returnid,$params)
	{
		if (!$this->ready) {
			return FALSE;
		}
		$locals = array(
		 'account'=>'account',
		 'amount'=>'amount',
		 'cancel'=>'cancel',
		 'contact'=>FALSE,
		 'currency'=>'currency',
		 'message'=>'message',
		 'payer'=>'payer',
		 'payfor'=>'payfor',
		 'senddata'=>FALSE, //TODO metadata check format json'ish
		 'surcharge'=>'surrate'
		);

		foreach ($params as $key=>$value) {
			$standard = array_search($key,$this->translates);
			if ($standard !== FALSE) {
				if (array_key_exists($standard,$locals)) {
					$newk = $locals[$standard];
					$params[$newk] = $value;
				}
			}
			unset($params[$key]);
		}
		//preserve class properties across requests
		$mod = $this->mod;
		$this->mod = $mod->GetName();
		$params['passthru'] = json_encode(get_object_vars($this));

		$mod->Redirect($id,'showform',$returnid,$params);
	}
}
