<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
namespace StripeGate;

class CryptInit Extends Crypter
{
	/**
	constructor:
	@mod: reference to current module object
	@method: optional openssl cipher type to use, default 'BF-CBC'
	@stretches: optional number of extension-rounds to apply, default 8192
	*/
	public function __construct(&$mod, $method='BF-CBC', $stretches=parent::STRETCHES)
	{
		parent::__construct($mod, $method, 'default', $stretches);
	}

	/**
	init_crypt:
	Must be called ONCE (during installation and/or after any localisation change)
	before any hash or preference-crypt
	@s: optional 'localisation' string, default ''
	*/
	final public function init_crypt($s='')
	{
		if (!$s) {
			$s = cmsms()->GetConfig()['ssl_url'].$this->mod->GetModulePath().parent::SKEY;
		}
		$value = str_shuffle(openssl_random_pseudo_bytes(9).microtime(TRUE));
		$value = $this->encrypt($value,
			hash_hmac('sha256', $s, parent::SKEY));
		$this->mod->SetPreference(hash('tiger192,3', $s),
			base64_encode($value));
	}
}
