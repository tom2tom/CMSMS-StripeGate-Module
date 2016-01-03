<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

class stripe_utils
{
	const ENC_ROUNDS = 10000;
	/**
	encrypt_value:
	@mod: reference to current module object
	@value: string to encrypted, may be empty
	@passwd: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_encode the encrypted value, default FALSE
	Returns: encrypted @value, or just @value if it's empty
	*/
	public static function encrypt_value(&$mod,$value,$passwd=FALSE,$based=FALSE)
	{
		if($value)
		{
			if(!$passwd)
			{
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if($passwd && $mod->havemcrypt)
			{
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->encrypt($value,$passwd);
				if($based)
					$value = base64_encode($value);
			}
			else
				$value = self::fusc($passwd.$value);
		}
		return $value;
	}

	/**
	decrypt_value:
	@mod: reference to current module object
	@value: string to decrypted, may be empty
	@passwd: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_decode the value, default FALSE
	Returns: decrypted @value, or just @value if it's empty
	*/
	public static function decrypt_value(&$mod,$value,$passwd=FALSE,$based=FALSE)
	{
		if($value)
		{
			if(!$passwd)
			{
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if($passwd && $mod->havemcrypt)
			{
				if($based)
					$value = base64_decode($value);
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->decrypt($value,$passwd);
			}
			else
				$value = substr(strlen($passwd),self::unfusc($value));
		}
		return $value;
	}

	/**
	fusc:
	@str: string or FALSE
	obfuscate @str
	*/
	public static function fusc($str)
	{
		if($str)
		{
			$s = substr(base64_encode(md5(microtime())),0,5);
			return $s.base64_encode($s.$str);
		}
		return '';
	}

	/**
	unfusc:
	@str: string or FALSE
	de-obfuscate @str
	*/
	public static function unfusc($str)
	{
		if($str)
		{
			$s = base64_decode(substr($str,5));
			return substr($s,5);
		}
		return '';
	}

	//Returns id of the default account, or the first-and-only account, or FALSE
	public static function GetAccount()
	{
		$db = cmsms()->GetDb();
		$ids = $db->GetAll(
'SELECT account_id,isdefault FROM '.cms_db_prefix().'module_sgt_account ORDER BY isdefault DESC,account_id');
		if($ids)
		{
			if($ids[0]['isdefault'] == TRUE || count($ids) == 1)
				return (int)$ids[0]['account_id'];
		}
		return FALSE;
	}

	public static function GetSupportedCurrencies()
	{
		return array(
	'Afghan Afghani'=>'afn',
	'Albanian Lek'=>'all',
	'Algerian Dinar'=>'dzd',
	'Angolan Kwanza'=>'aoa',
	'Argentine Peso'=>'ars',
	'Armenian Dram'=>'amd',
	'Aruban Florin'=>'awg',
	'Australian Dollar'=>'aud',
	'Azerbaijani Manat'=>'azn',
	'Bahamian Dollar'=>'bsd',
	'Bangladeshi Taka'=>'bdt',
	'Barbadian Dollar'=>'bbd',
	'Belize Dollar'=>'bzd',
	'Bermudian Dollar'=>'bmd',
	'Bolivian Boliviano'=>'bob',
	'Bosnia &amp; Herzegovina Convertible Mark'=>'bam',
	'Botswana Pula'=>'bwp',
	'Brazilian Real'=>'brl',
	'British Pound'=>'gbp',
	'Brunei Dollar'=>'bnd',
	'Bulgarian Lev'=>'bgn',
	'Burundian Franc'=>'bif',
	'Cambodian Riel'=>'khr',
	'Canadian Dollar'=>'cad',
	'Cape Verdean Escudo'=>'cve',
	'Cayman Islands Dollar'=>'kyd',
	'Central African CFA Franc'=>'xaf',
	'Cfp Franc'=>'xpf',
	'Chilean Peso'=>'clp',
	'Chinese Renminbi Yuan'=>'cny',
	'Colombian Peso'=>'cop',
	'Comorian Franc'=>'kmf',
	'Congolese Franc'=>'cdf',
	'Costa Rican Colón'=>'crc',
	'Croatian Kuna'=>'hrk',
	'Czech Koruna'=>'czk',
	'Danish Krone'=>'dkk',
	'Djiboutian Franc'=>'djf',
	'Dominican Peso'=>'dop',
	'East Caribbean Dollar'=>'xcd',
	'Egyptian Pound'=>'egp',
	'Ethiopian Birr'=>'etb',
	'Euro'=>'eur',
	'Falkland Islands Pound'=>'fkp',
	'Fijian Dollar'=>'fjd',
	'Gambian Dalasi'=>'gmd',
	'Georgian Lari'=>'gel',
	'Gibraltar Pound'=>'gip',
	'Guatemalan Quetzal'=>'gtq',
	'Guinean Franc'=>'gnf',
	'Guyanese Dollar'=>'gyd',
	'Haitian Gourde'=>'htg',
	'Honduran Lempira'=>'hnl',
	'Hong Kong Dollar'=>'hkd',
	'Hungarian Forint'=>'huf',
	'Icelandic Króna'=>'isk',
	'Indian Rupee'=>'inr',
	'Indonesian Rupiah'=>'idr',
	'Israeli New Sheqel'=>'ils',
	'Jamaican Dollar'=>'jmd',
	'Japanese Yen'=>'jpy',
	'Kazakhstani Tenge'=>'kzt',
	'Kenyan Shilling'=>'kes',
	'Kyrgyzstani Som'=>'kgs',
	'Lao Kip'=>'lak',
	'Lebanese Pound'=>'lbp',
	'Lesotho Loti'=>'lsl',
	'Liberian Dollar'=>'lrd',
	'Macanese Pataca'=>'mop',
	'Macedonian Denar'=>'mkd',
	'Malagasy Ariary'=>'mga',
	'Malawian Kwacha'=>'mwk',
	'Malaysian Ringgit'=>'myr',
	'Maldivian Rufiyaa'=>'mvr',
	'Mauritanian Ouguiya'=>'mro',
	'Mauritian Rupee'=>'mur',
	'Mexican Peso'=>'mxn',
	'Moldovan Leu'=>'mdl',
	'Mongolian Tögrög'=>'mnt',
	'Moroccan Dirham'=>'mad',
	'Mozambican Metical'=>'mzn',
	'Namibian Dollar'=>'nad',
	'Nepalese Rupee'=>'npr',
	'Netherlands Antillean Gulden'=>'ang',
	'New Taiwan Dollar'=>'twd',
	'New Zealand Dollar'=>'nzd',
	'Nicaraguan Córdoba'=>'nio',
	'Nigerian Naira'=>'ngn',
	'Norwegian Krone'=>'nok',
	'Pakistani Rupee'=>'pkr',
	'Panamanian Balboa'=>'pab',
	'Papua New Guinean Kina'=>'pgk',
	'Paraguayan Guaraní'=>'pyg',
	'Peruvian Nuevo Sol'=>'pen',
	'Philippine Peso'=>'php',
	'Polish Złoty'=>'pln',
	'Qatari Riyal'=>'qar',
	'Romanian Leu'=>'ron',
	'Russian Ruble'=>'rub',
	'Rwandan Franc'=>'rwf',
	'Saint Helenian Pound'=>'shp',
	'Salvadoran Colón'=>'svc',
	'Samoan Tala'=>'wst',
	'São Tomé &amp; Príncipe Dobra'=>'std',
	'Saudi Riyal'=>'sar',
	'Serbian Dinar'=>'rsd',
	'Seychellois Rupee'=>'scr',
	'Sierra Leonean Leone'=>'sll',
	'Singapore Dollar'=>'sgd',
	'Solomon Islands Dollar'=>'sbd',
	'Somali Shilling'=>'sos',
	'South African Rand'=>'zar',
	'South Korean Won'=>'krw',
	'Sri Lankan Rupee'=>'lkr',
	'Surinamese Dollar'=>'srd',
	'Swazi Lilangeni'=>'szl',
	'Swedish Krona'=>'sek',
	'Swiss Franc'=>'chf',
	'Tajikistani Somoni'=>'tjs',
	'Tanzanian Shilling'=>'tzs',
	'Thai Baht'=>'thb',
	'Tongan Paʻanga'=>'top',
	'Trinidad &amp; Tobago Dollar'=>'ttd',
	'Turkish Lira'=>'try',
	'Ugandan Shilling'=>'ugx',
	'Ukrainian Hryvnia'=>'uah',
	'United Arab Emirates Dirham'=>'aed',
	'United States Dollar'=>'usd',
	'Uruguayan Peso'=>'uyu',
	'Uzbekistani Som'=>'uzs',
	'Vanuatu Vatu'=>'vuv',
	'Vietnamese Đồng'=>'vnd',
	'West African CFA Franc'=>'xof',
	'Yemeni Rial'=>'yer',
	'Zambian Kwacha'=>'zmw'
		);
	}

	public static function GetCurrency($code)
	{
		$symbols = array(
		'afn'=>'؋',
		'all'=>'Lek',
		'dzd'=>'د.ج',
		'aoa'=>'Kz',
		'ars'=>'$',
		'amd'=>'֏',
		'awg'=>'ƒ',
		'aud'=>'$',
		'azn'=>'ман',
		'bsd'=>'$',
		'bdt'=>'৳',
		'bbd'=>'$',
		'bzd'=>'BZ$',
		'bmd'=>'$',
		'bob'=>'$b',
		'bam'=>'KM',
		'bwp'=>'P',
		'brl'=>'R$',
		'gbp'=>'£',
		'bnd'=>'$',
		'bgn'=>'лв',
		'bif'=>'FBu',
		'khr'=>'៛',
		'cad'=>'$',
		'cve'=>'$',
		'kyd'=>'$',
		'xaf'=>'FCFA',
		'xpf'=>'F',
		'clp'=>'$',
		'cny'=>'¥',
		'cop'=>'$',
		'kmf'=>'CF',
		'cdf'=>'FC',
		'crc'=>'₡',
		'hrk'=>'kn',
		'czk'=>'Kč',
		'dkk'=>'kr',
		'djf'=>'Fdj',
		'dop'=>'RD$',
		'xcd'=>'$',
		'egp'=>'£',
		'etb'=>'Br',
		'eur'=>'€',
		'fkp'=>'£',
		'fjd'=>'$',
		'gmd'=>'D',
		'gel'=>'₾',
		'gip'=>'£',
		'gtq'=>'Q',
		'gnf'=>'FG',
		'gyd'=>'$',
		'htg'=>'G',
		'hnl'=>'L',
		'hkd'=>'$',
		'huf'=>'Ft',
		'isk'=>'kr',
		'inr'=>'₹',
		'idr'=>'Rp',
		'ils'=>'₪',
		'jmd'=>'J$',
		'jpy'=>'¥',
		'kzt'=>'лв',
		'kes'=>'KSh',
		'kgs'=>'лв',
		'lak'=>'₭',
		'lbp'=>'£',
		'lsl'=>'L',
		'lrd'=>'$',
		'mop'=>'MOP$',
		'mkd'=>'ден',
		'mga'=>'Ar',
		'mwk'=>'MK',
		'myr'=>'RM',
		'mvr'=>'Rf',
		'mro'=>'UM',
		'mur'=>'₨',
		'mxn'=>'$',
		'mdl'=>'L',
		'mnt'=>'₮',
		'mad'=>'.د.م',
		'mzn'=>'MT',
		'nad'=>'$',
		'npr'=>'₨',
		'ang'=>'ƒ',
		'twd'=>'NT$',
		'nzd'=>'$',
		'nio'=>'C$',
		'ngn'=>'₦',
		'nok'=>'kr',
		'pkr'=>'₨',
		'pab'=>'B/.',
		'pgk'=>'K',
		'pyg'=>'Gs',
		'pen'=>'S/.',
		'php'=>'₱',
		'pln'=>'zł',
		'qar'=>'﷼',
		'ron'=>'lei',
		'rub'=>'₽',
		'rwf'=>'FRw',
		'shp'=>'£',
		'svc'=>'$',
		'wst'=>'WS$',
		'std'=>'Db',
		'sar'=>'﷼',
		'rsd'=>'Дин.',
		'scr'=>'₨',
		'sll'=>'Le',
		'sgd'=>'$',
		'sbd'=>'$',
		'sos'=>'S',
		'zar'=>'R',
		'krw'=>'₩',
		'lkr'=>'₨',
		'srd'=>'$',
		'szl'=>'L',
		'sek'=>'kr',
		'chf'=>'Fr.',
		'tjs'=>'',
		'tzs'=>'TSh',
		'thb'=>'฿',
		'top'=>'T$',
		'ttd'=>'TT$',
		'try'=>'₺',
		'ugx'=>'USh',
		'uah'=>'₴',
		'aed'=>'د.إ',
		'usd'=>'$',
		'uyu'=>'$U',
		'uzs'=>'лв',
		'vuv'=>'Vt.',
		'vnd'=>'₫',
		'xof'=>'CFA',
		'yer'=>'﷼',
		'zmw'=>'ZK'
		);

		if(array_key_exists($code,$symbols))
			return $symbols[$code];
		return '<?>';
	}


//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	/**
	Construct js for custom integration with Stripe Checkout js API.
	Returns: 3-member array - jsincs[],$jsfuncs[],$jsloads[]
	*/
	public static function setup($pubkey,$imgurl,$name,$desc,$amount)
	{
		$jsincs[] = <<<EOS
<script src="https://checkout.stripe.com/checkout.js"></script>

EOS;
		$jsfuncs[] = <<<EOS
 var handler = StripeCheckout.configure({key:'{$pubkey}'});

EOS;
		$code = $row['currency']; //TODO
		$units = (int)$amount*100;
/*
See https://stripe.com/docs/checkout#integration-custom
image	A relative or absolute URL pointing to a square image of your brand or product.
  The recommended minimum size is 128x128px. The recommended image types are .gif, .jpeg, and .png.
name	The name of your company or website.
description	A description of the product or service being purchased.
amount	The amount (in cents) that's shown to the user.
 Note that you will still have to explicitly include it when you create a charge using the API.
 (You will also need to set a data-currency value to change the default of USD.)

see demo file charge.php

token - callback invoked when the Checkout process is complete
  token.id can be used to create a charge or customer. 
  token.email contains the email address entered by the user. 
  >> cache token.client_ip
  >> cache token.created
  args is an object containing billing and shipping addresses if enabled.
See https://stripe.com/docs/api#tokens

*/
		// open Checkout
		$jsloads[] = <<<EOS
 $('#PayButton').click(function(ev) {
  handler.open({
   name: '{$name}',
   description: '{$desc}',
   currency: '{$code}',
   amount: {$units},
   image: '{$imgurl}',
   locale: 'auto',
   token: function(token) {
    var dbg = 1;
   },
  });
  ev.preventDefault();
  ev.stopPropogation(); //CHECKME
 });
EOS;
		// close Checkout on page navigation
		$jsloads[] = <<<EOS
 window.onpopstate = function(ev) {
  handler.close();
 };

EOS;
		$jsfuncs = array();
		return array($jsincs,$jsfuncs,$jsloads);
	}

	//this is a varargs function, 2nd argument (if it exists) is either a
	//Lang key or one of the sms_gateway_base::STAT_* constants
	public static function get_msg(&$module)
	{
		$ip = getenv('REMOTE_ADDR');
		if(func_num_args() > 1)
		{
			$tmp = $module->Lang('_'); //ensure relevant lang is loaded
			$parms = array_slice(func_get_args(),1);
			$key = $parms[0];
			$langdata = ( $module->curlang) ?
				$module->langhash[$module->curlang]:
				reset($module->langhash);
			if(isset($langdata[$key]) || array_key_exists($key,$langdata))
			{
				$txt = $module->Lang($key,array_slice($parms,1));
				if($ip)
					$txt .= ','.$ip;
			}
			else
			{
				$txt = implode(',',$parms);
				if($ip && $parms[0] != sms_gateway_base::STAT_NOTSENT)
					$txt .= ','.$ip;
			}
			return $txt;
		}
		return $ip;
	}

	//this is a varargs function, 2nd argument (if it exists) may be a Lang key
	public static function get_delivery_msg(&$module)
	{
		$ip = getenv('REMOTE_ADDR');
		if(func_num_args() > 1)
		{
			$tmp = $module->Lang('_'); //ensure relevant lang is loaded
			$parms = array_slice(func_get_args(),1);
			$key = $parms[0];
			$langdata = ($module->curlang) ?
				$module->langhash[$module->curlang]:
				reset($module->langhash);
			if(isset($langdata[$key]) || array_key_exists($key,$langdata))
				$txt = $module->Lang($key,array_slice($parms,1));
			else
				$txt = implode(',',$parms);
			if($ip)
				$txt .= ','.$ip;
			return $txt;
		}
		return $ip;
	}

	public static function clean_log(&$module=NULL,$time=0)
	{
		if($module->GetPreference('logsends'))
		{
			if(!$time)
				$time = time();
			if($module === NULL)
				$module = cms_utils::get_module(StripeGate::MODNAME);
			$days = $module->GetPreference('logdays',1);
			if($days < 1)
				$days = 1;
			$time -= $days*86400;
			$db = cmsms()->GetDb();
			$pref = cms_db_prefix();
			$limit = $db->DbTimeStamp($time);
			$db->Execute('DELETE FROM '.$pref.'module_stpg_sent WHERE sdate<'.$limit);
		}
	}

}

?>
