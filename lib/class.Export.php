<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
namespace StripeGate;

class Export
{
	private function GetAccountIDForRecord($record_id)
	{
		global $db;
		$sql = 'SELECT account_id FROM '.cms_db_prefix().'module_sgt_record WHERE record_id=?';
		return $db->GetOne($sql,array($record_id));
	}

	private function GetAccountNameFromID($account_id)
	{
		global $db;
		$sql = 'SELECT name FROM '.cms_db_prefix().'module_sgt_account WHERE account_id=?';
		return $db->GetOne($sql,array($account_id));
	}

	/**
	ExportName:
	@mod: reference to current StripeGate module object
	@account_id: index of the account to process, or array of such, or FALSE if @record_id is provided
	@record_id: index of the record to process, or array of such, or FALSE if @account_id is provided
	*/
	private function ExportName(&$mod, $account_id=FALSE, $record_id=FALSE)
	{
		if ($account_id) {
			if (is_array($account_id)) {
				$c = count($account_id);
				if ($c == 1)
					$aname = self::GetAccountNameFromID($account_id[0]);
				else
					$aname = 'Multi('.$c.')Accounts';
			} else
				$aname = self::GetAccountNameFromID($account_id);
		} else {
			if (is_array($record_id))
				$rid = reset($record_id);
			else
				$rid = $record_id;
			$account_id = self::GetAccountIDForRecord($rid);
			$aname = self::GetAccountNameFromID($account_id);
		}

		$sname = preg_replace('/\W/','_',$aname);
		$datestr = date('Y-m-d-H-i');
		return $mod->GetName().$mod->Lang('export').'-'.$sname.'-'.$datestr.'.csv';
	}

	/**
	CSV:
	@mod: reference to current StripeGate module object
	@account_id: index of account to process, or array of such indices,
		or FALSE if @record_id is provided
	@record_id: index of record to process, or array of such indices,
		or FALSE to process @account_id, default=FALSE
	@fp: handle of open file, if writing data to disk, or FALSE if constructing in memory, default = FALSE
	@$sep: field-separator in output data, assumed single-byte ASCII, default = ','

	Constructs a CSV string for specified/all records belonging to @account_id,
	and returns the string or writes it progressively to the file associated with @fp
	(which must be opened and closed upstream)
	To avoid field-corruption, existing separators in headings or data are converted
	to something else, generally like &#...;
	(except when the separator is '&', '#' or ';', those become %...%)
	Returns: TRUE/string, or FALSE on error
	*/
	private function CSV(&$mod, $account_id=FALSE, $record_id=FALSE, $fp=FALSE, $sep=',')
	{
		global $db;
		$pref = cms_db_prefix();
		$adata = $db->GetAssoc('SELECT account_id,name,currency,amountformat FROM '.$pref.'module_sgt_account');
		if (!$adata)
			return FALSE;

		if ($account_id) {
			if (is_array($account_id)) {
				$sql = 'SELECT record_id FROM '.$pref.
				'module_sgt_record WHERE account_id IN('.
				implode('?,',count($account_id)-1).'?) ORDER BY account_id,recorded';
				$all = $db->GetCol($sql,$account_id);
			} else {
				$sql = 'SELECT record_id FROM '.$pref.
				'module_sgt_record WHERE account_id=? ORDER BY recorded';
				$all = $db->GetCol($sql,array($account_id));
			}
		} elseif ($record_id) {
			if (is_array($record_id))
				$all = $record_id;
			else
				$all = array($record_id);
		} else
			return FALSE;

		foreach ($adata as $id=>&$row)
			$row['symbol'] = StripeGate\Utils::GetSymbol($row['currency']);
		unset($row);

		if ($fp && ini_get('mbstring.internal_encoding') !== FALSE) { //send to file, and conversion is possible
			$config = cmsms()->GetConfig();
			if (!empty($config['default_encoding']))
				$defchars = trim($config['default_encoding']);
			else
				$defchars = 'UTF-8';
			$expchars = $mod->GetPreference('export_file_encoding','ISO-8859-1');
			$convert = (strcasecmp ($expchars,$defchars) != 0);
		} else
			$convert = FALSE;

		$sep2 = ($sep != ' ')?' ':',';
		switch ($sep) {
		 case '&':
			$r = '%38%';
			break;
		 case '#':
			$r = '%35%';
			break;
		 case ';':
			$r = '%59%';
			break;
		 default:
			$r = '&#'.ord($sep).';';
			break;
		}

		$strip = $mod->GetPreference('strip_on_export',FALSE);

		//header line
		$outstr = implode($sep,array(
			'account',
			'amount',
			'recorded',
			'stripe identifier',
			'paywhat',
			'payfor'
		));
		$outstr .= PHP_EOL;

		if ($all) {
			$sql = 'SELECT * FROM '.$pref.
			'module_sgt_record WHERE record_id IN('.implode(',',$all).')';
			$all = $db->GetAll($sql);
			//data lines(s)
			foreach ($all as &$row) {
				unset($row['record_id']);
				$aid = (int)$row['account_id'];
				unset($row['account_id']);
			 	$fv = $adata[$aid]['name'];
				if ($strip)
					$fv = strip_tags($fv);
				$fv = str_replace($sep,$r,$fv);
				$outstr .= preg_replace('/[\n\t\r]/',$sep2,$fv);
				foreach ($row as $fn=>$fv) {
					switch ($fn) {
					 case 'amount':
					 	$outstr .= $sep.StripeGate\Utils::GetPublicAmount($fv,$adata[$aid]['amountformat'],$adata[$aid]['symbol']);
						break;
					 case 'recorded':
						$outstr .= $sep.date('Y-m-d H:i:s',$fv);
						break;
					 default:
						if ($strip)
							$fv = strip_tags($fv);
						$fv = str_replace($sep,$r,$fv);
						$outstr .= $sep.preg_replace('/[\n\t\r]/',$sep2,$fv);
					}
				}
				$outstr .= PHP_EOL;
				if ($fp) {
					if ($convert) {
						$conv = mb_convert_encoding($outstr, $expchars, $defchars);
						fwrite($fp, $conv);
						unset($conv);
					} else {
						fwrite($fp, $outstr);
					}
					$outstr = '';
				}
			}
			unset($row);

			if ($fp)
				return TRUE;
			else
				return $outstr; //encoding conversion upstream
		} else {
			//no data, produce just a header line
			if ($fp) {
				if ($convert) {
					$conv = mb_convert_encoding($outstr, $expchars, $defchars);
					fwrite($fp, $conv);
					unset($conv);
				} else {
					fwrite($fp, $outstr);
				}
				return TRUE;
			}
			return $outstr; //encoding conversion upstream
		}
	}

	/**
	Export:
	@mod: reference to current StripeGate module object
	@account_id: optional account id, or array of such id's, default FALSE
	@record_id: optional record_id, or array of such id's, default FALSE
	@sep: optional field-separator for exported content, default ','
	At least one of @account_id or @record_id must be provided.
	Returns: TRUE on success, or lang key for error message upon failure
	*/
	public function Export(&$mod, $account_id=FALSE, $record_id=FALSE, $sep=',')
	{
		if (!($account_id || $record_id))
			return 'err_parameter';
		$fname = self::ExportName($mod,$account_id,$record_id);

		if ($mod->GetPreference('export_file',FALSE)) {
			$updir = StripeGate\Utils::GetUploadsPath($mod);
			if ($updir) {
				$filepath = $updir.DIRECTORY_SEPARATOR.$fname;
				$fp = fopen($filepath,'w');
				if ($fp) {
					$success = self::CSV($mod,$account_id,$record_id,$fp,$sep);
					fclose($fp);
					if ($success) {
						$url = StripeGate\Utils::GetUploadsUrl($mod).'/'.$fname;
						@ob_clean();
						@ob_clean();
						header('Location: '.$url);
						return TRUE;
					}
				}
			}
		} else {
			$csv = self::CSV($mod,$account_id,$record_id,FALSE,$sep);
			if ($csv) {
				$config = cmsms()->GetConfig();
				if (!empty($config['default_encoding']))
					$defchars = trim($config['default_encoding']);
				else
					$defchars = 'UTF-8';

				if (ini_get('mbstring.internal_encoding') !== FALSE) { //conversion is possible
					$expchars = $mod->GetPreference('export_file_encoding','ISO-8859-1');
					$convert = (strcasecmp ($expchars,$defchars) != 0);
				} else {
					$expchars = $defchars;
					$convert = FALSE;
				}

				@ob_clean();
				@ob_clean();
				header('Pragma: public');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Cache-Control: private',FALSE);
				header('Content-Description: File Transfer');
		//note: some older HTTP/1.0 clients did not deal properly with an explicit charset parameter
				header('Content-Type: text/csv; charset='.$expchars);
				header('Content-Length: '.strlen($csv));
				header('Content-Disposition: attachment; filename='.$fname);
				if ($convert)
					echo mb_convert_encoding($csv,$expchars,$defchars);
				else
					echo $csv;
				return TRUE;
			}
		}
		return 'err_export';
	}
}
