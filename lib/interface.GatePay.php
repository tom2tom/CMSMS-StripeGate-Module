<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

interface GatePay
{
	/**
	Constructor:
	@caller: reference to the payment-initiator's module-object
	@worker reference to the gateway-interface module-object
	*/
	public function __construct(&$caller, &$worker);

	/**
	GetConverts:
	Get the 'standard' names which may be used for parameter-key translations
	between 'initiator-normal' and 'gateway-API-normal'. Specifically, some or all of:
	for dialog setup
	 'account' id of gateway account (NOT customer-account at the gateway)
	 'amount' headline amount, also for feedback what was actually paid
	 'cancel' whether to display cancel-button, also for feedback, whether user cancelled
	 'contact' c.f. stripe 'receipt_email'
	 'currency' 3-char country code
	 'customer' user-account identifier recognised by the gateway, also for feedback
	 'message' in-dialog message
	 'passthru' array of parameters to be passed as-is to the response-handler
		after the end of the gateway interaction (however that ends)
	 'payee' who is being paid - for part of dialog title
	 'payer' who is paying or being paid for
	 'payfor' description of what is being bought
	 'senddata' mixed extra data to be supplied to the gateway
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
	 'success' boolean
	 'successmsg'
	 'transactid'
	NOTE key 'preserve' is reserved for internal use
	Returns: array with keys for usable parameter names, all values FALSE
*/
	public function GetConverts();

	/**
	Furnish:
	Store interface parameters for conducting an online payment
	@alternates: array with keys being some or all of the 'standard' names (see
	 GetConverts) and values being the respective corresponding 'initiator-normal'
	 names, or TRUE to match the respective key
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
	@director: 3-member array of redirect-parameters for use upon completion:
	 [0] = id, [1] = (caller-module)action-name, [2] = returnid
	Returns: boolean representing acceptability of @handler
	*/
	public function Furnish($alternates, $handler, $director);

	/**
	ShowForm:
	Construct and display a payment 'form' for the user to populate and submit.
	@id: action-id specified by the initiator's module, probably 'cntnt01' or similar
	@returnid: the id of the page being displayed by the caller's module
	@params associative array of data to be applied to the form. Keys will be
		translated before use, consistent with settings supplied via Furnish()
	Returns: nope, it redirects
	*/
	public function ShowForm($id, $returnid, $params);

	/**
 	HandleResult:
	Interpret gateway feedback, give relevant data back to the initiator, then
	redirect to @params['action']
	@params: request-parameters to be used, including some from the gateway
	Returns: nope, it redirects
	*/
	public function HandleResult($params);
}
