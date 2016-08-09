<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
/*
This provides a generic interface for other modules to initiate a payment via
Stripe and get feedback from that payment process.
*/

class sgtPayer //implements GatePay
{
	private $callermod; //reference to the initiator's module-object
	private $workermod; //reference to a StripeGate-module-object
	private $translates = NULL;
	private $handler = NULL;
	private $type = 0; //enum for type of $handler

	public function __construct(&$caller,&$worker)
	{
		$this->callermod = $caller;
		$this->workermod = $worker;
	}

	/**
	Furnish:
	Store interface parameters
	@alternates: array with keys being some or all of the 'standard' names
	 and values being the respective corresponding 'initiator-normal' names, or
	 TRUE to match the respective key
	@handler: mixed, one of
	 an array (classname,methodname) where methodname is static and the method returns boolean for success
	 a string 'classname::methodname' where the method returns boolean for success
	 an array (modulename,actionname) AND the action should be a 'doer', not a 'shower', returns HTML code
	 an array (modulename,'method.whatever') to be included, the code must conclude with variable $res = T/F indicating success
	 an URL like <server-root-url>/index.php?mact=<modulename>,cntnt01,<actionname>,0
	 	- provided the PHP curl extension is available
	 NOT a closure in a static context (PHP 5.3+) OR static closure (PHP 5.4+)
	 cuz those aren't transferrable between requests
	See action.webhook.php for example of a hander-action fed by a HTTP request
	 In this case too, the action should be a 'doer', and return code 200 or 400+
	Returns: boolean representing acceptability of @handler
	*/
	public function Furnish($alternates, $handler)
	{
		foreach ($alternates as $key=>&$val) {
			if ($val === TRUE)
				$val = $key;
		}
		unset($val);
		$this->translates = array_filter(array_merge($this->GetConverts(),$alternates));

		return $this->SetResultHandler($handler);
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
	 'preserve'
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
		 'customer'=>FALSE,//also feedback (gateway customer-identifier)
		 'message'=>FALSE,
		 'payer'=>FALSE,
		 'payfor'=>FALSE,
		 'senddata'=>FALSE,
		 'surcharge'=>FALSE,
		 'preserve'=>FALSE,
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
	 an array (classname,methodname) where methodname is static and the method returns boolean for success
	 a string 'classname::methodname' where the method returns boolean for success
	 an array (modulename,actionname) AND the action should be a 'doer', not a 'shower', returns HTML code
	 an array (modulename,'method.whatever') to be included, the code must conclude with variable $res = T/F indicating success
	 an URL like <server-root-url>/index.php?mact=<modulename>,cntnt01,<actionname>,0
	 	- provided the PHP curl extension is available
	 NOT a closure in a static context (PHP 5.3+) OR static closure (PHP 5.4+)
	 cuz those aren't transferrable between requests
	See action.webhook.php for example of a hander-action fed by a HTTP request
	 In this case too, the action should be a 'doer', and return code 200 or 400+
	Returns: boolean representing acceptability of @handler
	*/
	private function SetResultHandler($handler)
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
					$type = 2;
				}
			}
*/
		} elseif (is_array($handler) && count($handler) == 2) {
			$ob = cms_utils::get_module($handler[0]);
			if ($ob) {
				$dir = $ob->GetModulePath();
				unset($ob);
				$fp = $dir.DIRECTORY_SEPARATOR.'action.'.$handler[1].'.php';
				if (@is_file($fp)) {
					$type = 3;
				} elseif (strpos($handler[1],'method.') === 0) {
					$fp = $dir.DIRECTORY_SEPARATOR.$handler[1].'.php';
					if (@is_file($fp)) {
						$type = 4;
					}
				}
			}
		} elseif (is_string($handler)) {
			if ($this->workermod->havecurl) { //curl is installed
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
			return TRUE;
		}
		return FALSE;
	}

	/**
	ShowForm:
	Construct and display a payment 'form' for the user to populate and submit.
	@id: action-id specified by the initiator's module, probably 'cntnt01' or similar
	@returnid: the id of the page being displayed by the caller's module
	@params associative array of data to be applied to the form. Keys will be
		translated before use, consistent with settings supplied via Furnish()
	Returns: nope, it redirects
	*/
	public function ShowForm($id,$returnid,$params)
	{
		//name-translations from standard to StripeGate
		$locals = array(
		 'account'=>TRUE,
		 'amount'=>TRUE,
		 'cancel'=>'withcancel',
		 'contact'=>FALSE,
		 'currency'=>TRUE,
		 'message'=>TRUE,
		 'payee'=>TRUE,
		 'payer'=>TRUE,
		 'payfor'=>TRUE,
		 'senddata'=>FALSE, //TODO if used metadata check format json'ish
		 'surcharge'=>'surrate',
		 'preserve'=>TRUE //blended into ''passthru', NOT sent as-is to the gateway
		);
		//translate relevant supplied $params to StripeGate-recognised names, ditch the others
		foreach ($params as $key=>$value) {
			$standard = array_search($key,$this->translates);
			if ($standard !== FALSE) {
				if (array_key_exists($standard,$locals)) {
					$newk = $locals[$standard];
					if ($newk) {
						if ($newk === TRUE)
							$newk = $standard;
						if ($newk === $key)
							continue;
						$params[$newk] = $value;
					}
				}
			}
			unset($params[$key]);
		}
		//preserve class properties & perhaps some caller data across requests
		$this->callermod = $this->callermod->GetName();
		$ob = $this->workermod;
		$this->workermod = $this->workermod->GetName(); //StripeGate
		$value = get_object_vars($this);
		$key = 'preserve';
		if (!empty($params[$key])) {
			$value[$key] = $params[$key];
			unset ($params[$key]);
		}
		$params['passthru'] = base64_encode(json_encode($value));

		$ob->Redirect($id,'showform',$returnid,$params);
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
		$props = json_decode(base64_decode($params['stg_passthru']));
		if ($props !== NULL) {
			$arr = (array)$props;
			foreach ($arr as $key=>$val) {
				if ($key == 'callermod' || $key == 'workermod') {
					$this->$key = cms_utils::get_module($val); //no namespace
				} elseif ($key == 'preserve') {
					$params['preserve'] = $val;
				} elseif (is_object($val)) {
					$this->$key = (array)$val;
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
		 'amount'=>TRUE, //in smallest currency-units
		 'cancel'=>TRUE, //cancel-button clicked
//		 'preserve'=>'passthru',
		 'cardcvc'=>'stg_cvc',
		 'cardmonth'=>'exp_month',
		 'cardname'=>'stg_name',
		 'cardnumber'=>'stg_number',
		 'cardyear'=>'exp_year',
		 'contact'=>'name',
		 'currency'=>TRUE,
		 'customer'=>TRUE,
		 'description'=>TRUE,
		 'errmsg'=>'failure_message',
		 'receivedata'=>FALSE, //'metadata',
		 'success'=>'paid', //boolean
//		 'successmsg'=>,
		 'transactid'=>'id'
		);

		foreach ($params as $key=>$value) {
			$standard = array_search($key,$locals);
			if ($standard) {
				if ($standard === TRUE) {
					$standard = $key;
				}
				if (array_key_exists($standard,$key)) {
					$newk = $this->translates[$standard];
					if ($newk) {
						if ($newk === $key) {
							continue;
						}
						$params[$newk] = $value;
					}
				} else {
				 $c = 43; //DEBUG placeholder TODO
				}
			} else {
			 $c = 43; //DEBUG placeholder TODO
			}
			unset($params[$key]);
		}
		//NULL values in $params for unused keys in $this->translates
		$allprops = array_fill_keys(array_keys($this->translates),NULL);
		$params = array_merge($allprops,$params);

		switch ($this->type) {
		 case 1: //callable, 2-member array or string like 'ClassName::methodName'
			$res = call_user_func_array($this->handler,$params);
 			//TODO handle $res == FALSE
			break;
/*		 case 2: //static closure
			$res = $this->handler($params);
			break; */
		 case 3: //module action
			$ob = cms_utils::get_module($this->handler[0]);
			$res = $ob->DoAction($this->handler[1],'cntnt01',$params); //the $id is default CMSMS action-id //CHECKME allowed $params
			unset($ob);
			//TODO handle $res == 400+
			break;
		 case 4: //code inclusion
			$ob = cms_utils::get_module($this->handler[0]);
			$fp = $ob->GetModulePath().DIRECTORY_SEPARATOR.$this->handler[1].'.php';
			unset($ob);
			$res = FALSE;
			require $fp;
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
			$res = curl_exec($ch);
			//TODO handle $res == 400+
			curl_close($ch);
			break;
		}

		$this->callermod->Redirect('cntnt01',$params['action'],$params['returnid'],$params); //TODO check action, returnid
	}

}
