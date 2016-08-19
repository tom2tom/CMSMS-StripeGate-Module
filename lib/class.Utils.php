<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
namespace StripeGate;

class Utils
{
	const ENC_ROUNDS = 10000;

	/* *
	SafeGet:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command
	@args: array of arguments for @sql
	@mode: optional type of get - 'one','row','col','assoc' or 'all', default 'all'
	Returns: boolean indicating successful completion
	*/
/*	public static function SafeGet($sql,$args,$mode='all')
	{
		$db = cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			switch ($mode) {
			 case 'one':
				$ret = $db->GetOne($sql,$args);
				break;
			 case 'row':
				$ret = $db->GetRow($sql,$args);
				break;
			 case 'col':
				$ret = $db->GetCol($sql,$args);
				break;
			 case 'assoc':
				$ret = $db->GetAssoc($sql,$args);
				break;
			 default:
				$ret = $db->GetAll($sql,$args);
				break;
			}
			if ($db->CompleteTrans())
				return $ret;
			else {
				$nt--;
				usleep(50000);
			}
		}
		return FALSE;
	}
*/
	/* *
	SafeExec:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command, or array of them
	@args: array of arguments for @sql, or array of them
	Returns: boolean indicating successful completion
	*/
/*	public static function SafeExec($sql, $args)
	{
		$db = cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
			$db->StartTrans();
			if (is_array($sql)) {
				foreach ($sql as $i=>$cmd)
					$db->Execute($cmd,$args[$i]);
			} else
				$db->Execute($sql,$args);
			if ($db->CompleteTrans())
				return TRUE;
			else {
				$nt--;
				usleep(50000);
			}
		}
		return FALSE;
	}
*/
	/**
	ProcessTemplate:
	@mod: reference to current StripeGate module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@cache: optional boolean, default TRUE
	Returns: string, processed template
	*/
	public static function ProcessTemplate(&$mod, $tplname, $tplvars, $cache=TRUE)
	{
		global $smarty;
		if ($mod->before20) {
			$smarty->assign($tplvars);
			return $mod->ProcessTemplate($tplname);
		} else {
			if ($cache) {
				$cache_id = md5('sgt'.$tplname.serialize(array_keys($tplvars)));
				$lang = CmsNlsOperations::get_current_language();
				$compile_id = md5('sgt'.$tplname.$lang);
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname),$cache_id,$compile_id,$smarty);
				if (!$tpl->isCached())
					$tpl->assign($tplvars);
			} else {
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname),NULL,NULL,$smarty,$tplvars);
			}
			return $tpl->fetch();
		}
	}

	/**
	GetAccount:
	Returns: id of the default account, or else the first-and-only account, or FALSE
	*/
	public static function GetAccount()
	{
		$db = cmsms()->GetDb();
		$ids = $db->GetAll(
'SELECT account_id,isdefault FROM '.cms_db_prefix().'module_sgt_account ORDER BY isdefault DESC,account_id');
		if ($ids) {
			if ($ids[0]['isdefault'] == TRUE || count($ids) == 1)
				return (int)$ids[0]['account_id'];
		}
		return FALSE;
	}

	/**
	GetUploadsPath:
	@mod: reference to current StripeGate module object
	Returns: absolute path string or false
	*/
	public static function GetUploadsPath(&$mod)
	{
		$config = cmsms()->GetConfig();
		$up = $config['uploads_path'];
		if ($up) {
			$rp = $mod->GetPreference('uploads_dir');
			if ($rp)
				$up .= DIRECTORY_SEPARATOR.$rp;
			if (is_dir($up))
				return $up;
		}
		return FALSE;
	}

	/**
	GetUploadsUrl:
	@mod: reference to current StripeGate module object
	Returns: absolute url string or false
	*/
	public static function GetUploadsUrl(&$mod)
	{
		$config = cmsms()->GetConfig();
		$key = (empty($_SERVER['HTTPS'])) ? 'uploads_url':'ssl_uploads_url';
		$up = $config[$key];
		if ($up) {
			$rp = $mod->GetPreference('uploads_dir');
			if ($rp) {
				$rp = str_replace('\\','/',$rp);
				$up .= '/'.$rp;
			}
			return $up;
		}
		return FALSE;
	}

	/* *
	GetReportsUrl:
	Returns: webhook-reports URL (pretty or not)
	*/
/*	public static function GetReportsUrl()
	{
		$returnid = cmsms()->GetContentOperations()->GetDefaultContent();
		//CMSMS 1.10+ has ->create_url();
		return $this->CreateLink('m1_','webhook',$returnid,'',array(),'',
			TRUE,FALSE,'',FALSE,'stripegate/webhook');
	}
*/

	/**
	encrypt_value:
	@mod: reference to current module object
	@value: string to encrypted, may be empty
	@passwd: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_encode the encrypted value, default FALSE
	Returns: encrypted @value, or just @value if it's empty
	*/
	public static function encrypt_value(&$mod, $value, $passwd=FALSE, $based=FALSE)
	{
		if ($value) {
			if (!$passwd) {
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if ($passwd && $mod->havemcrypt) {
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->encrypt($value,$passwd);
				if ($based)
					$value = base64_encode($value);
			} else
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
	public static function decrypt_value(&$mod, $value, $passwd=FALSE, $based=FALSE)
	{
		if ($value) {
			if (!$passwd) {
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if ($passwd && $mod->havemcrypt) {
				if ($based)
					$value = base64_decode($value);
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->decrypt($value,$passwd);
			} else
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
		if ($str) {
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
		if ($str) {
			$s = base64_decode(substr($str,5));
			return substr($s,5);
		}
		return '';
	}

	/**
	ConstructAlias:
	@alias: current alias
	@fullname: account name
	Returns: alias-suitable string, max 15 chars
	*/
	public static function ConstructAlias($alias, $fullname)
	{
		$alias = mb_convert_case(trim($alias),MB_CASE_LOWER); //TODO encoding
		if (!$alias)
			$alias = mb_convert_case(trim($fullname,"\t\n\r\0 _"),MB_CASE_LOWER);
		$alias = preg_replace('/\W+/','_',$alias); //TODO mb_
		$parts = array_slice(explode('_',$alias),0,5);
		$alias = substr(implode('_',$parts),0,15);
		return trim($alias,'_');
	}

	/**
	GetSupportedCurrencies:
	Returns: array
	*/
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

	/**
	GetSymbol:
	@$code: one of the values returned by GetSupportedCurrencies()
	Returns: matching currency symbol, or '<?>'
	*/
	public static function GetSymbol($code)
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

		if (array_key_exists($code,$symbols))
			return $symbols[$code];
		return '<?>';
	}

	/**
	GetPublicAmount:
	@units: the amount to process, in 'indivisible' units as used internally by Stripe e.g. cents
	@format: currency format template e.g. 'S.00'
	@symbol: currency symbol string
	Returns: formatted string representing amount e.g. $19.99
	*/
	public static function GetPublicAmount($units, $format, $symbol)
	{
		if (preg_match('/^(.*)?(S)(\W+)?(\d*)$/',$format,$matches)) {
			$places = strlen($matches[4]);
			$times = pow(10,$places);
			$num = number_format($units/$times,$places);
			if ($matches[1]) {
				if (strpos('.',$num) !== FALSE)
					$num = str_replace('.',$symbol,$num); //workaround PHP<5.4
				else
					$num .= $symbol;
			} else {
				if ($matches[3] != '.')
					$num = str_replace('.',$matches[3],$num);
				$num = $symbol.$num;
			}
			return $num;
		} else
			return $symbol.number_format($units/100,2);
	}

	/**
	CleanPublicAmount:
	@units: the amount to process, in any format but nominally consistent with a 'public' amount
	@format: currency format template e.g. 'S.00'
	@symbol: currency symbol string
	Returns: formatted string representing amount e.g. $19.99
	*/
	public static function CleanPublicAmount($units, $format, $symbol)
	{
		$a = self::GetPrivateAmount($units,$format,$symbol);
		return self::GetPublicAmount($a,$format,$symbol);
	}

	/**
	GetPrivateAmount:
	@amount: publicly presentable monetary amount e.g. $19.99
	@format: currency format template e.g. 'S.00'
	@symbol: currency symbol string
	Returns: number, in 'indivisible' units as used internally by Stripe
	*/
	public static function GetPrivateAmount($amount, $format, $symbol)
	{
		if (preg_match('/^(.*)?(S)(\W+)?(\d*)$/',$format,$matches)) {
			if ($matches[1])
				$num = str_replace($symbol,'.',$amount);
			else
				$num = str_replace(array($symbol,$matches[3]),array('','.'),$amount);
			$places = strlen($matches[4]);
			$times = pow(10,$places);
			return (int)($num * $times);
		} else
			return preg_replace('/\D/','',$amount) + 0; //assume 'raw' is good enough, in this context
	}
}
