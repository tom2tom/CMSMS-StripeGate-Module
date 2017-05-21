<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

$padm = $this->CheckPermission('ModifyStripeGateProperties');
$psee = $padm || $this->CheckPermission('UseStripeAccount');

if (!($padm || $psee)) {
	exit;
}

if (isset($params['close'])) {
	$this->Redirect($id, 'administer', '', ['account_id' => $params['account_id']]);
}

$aid = (int)$params['account_id'];

if (isset($params['export'])) {
	if (isset($params['sel'])) {
		$pref = cms_db_prefix();
		$row = $db->GetRow('SELECT currency,amountformat FROM '.
			$pref.'module_sgt_account WHERE account_id=?', [$aid]);
		$fmt = $row['amountformat'];
		$symbol = StripeGate\Utils::GetSymbol($row['currency']);
		$exports = [];
		$dt = new DateTime();
		$data = (array)json_decode(base64_decode($params['sdata']));

		foreach ($params['sel'] as $rid) {
			foreach ($data as $a => $one) {
				foreach ($one->charges as $pay) {
					if ($pay->id == $rid && !isset($pay->transferid)) {
						//this transfer includes a selected charge, so grab all its charges
						//notwithstanding the extra foreach, this overwrites 'outer' $pay, among others
						foreach ($one->charges as $pay) {
							$pay->transferid = $one->id;
							$dt->setTimestamp($a);
							$pay->available = $dt->format('Y-m-d G:i');
							$pay->gross = StripeGate\Utils::GetReportAmount($pay->gross, $fmt, $symbol);
							$pay->net = StripeGate\Utils::GetReportAmount($pay->net, $fmt, $symbol);
							$dt->setTimestamp($pay->when);
							$pay->when = $dt->format('Y-m-d G:i:s');
							$exports[] = (array)$pay;
						}
						break 2; //next sel
					}
				}
			}
		}
		if ($exports) {
			$funcs = new StripeGate\Export();
			$res = $funcs->ExportTransfers($this, $aid, $exports);
			if ($res === TRUE) {
				exit;
			}
			$params['message'] = $this->Lang($res);
		}
	}
}

$pref = cms_db_prefix();
$row = $db->GetRow('SELECT name,currency,amountformat,usetest,privtoken,testprivtoken FROM '.
	$pref.'module_sgt_account WHERE account_id=?', [$aid]);
if (0) { //$row['usetest']) {
	if ($row['testprivtoken']) {
		$cfuncs = new StripeGate\Crypter($this);
		$privkey = $cfuncs->decrypt_value($row['testprivtoken']);
	} else {
		$privkey = FALSE;
	}
} else {
	if ($row['privtoken']) {
		$cfuncs = new StripeGate\Crypter($this);
		$privkey = $cfuncs->decrypt_value($row['privtoken']);
	} else {
		$privkey = FALSE;
	}
}
if (!$privkey) {
	echo $this->Lang('err_parameter');
	return;
}

$dt = new DateTime();
$data = [];
$gets = [];
$days = (int)$params['duration'];

if ($days == 0) { //process selected item(s)
	if (!empty($params['sel'])) {
		$pref = cms_db_prefix();
		$fillers = str_repeat('?,', count($params['sel']) - 1);
		$sel = $db->GetArray('SELECT recorded,identifier FROM '.$pref.'module_sgt_record WHERE record_id IN('.$fillers.'?)', $params['sel']);
		$whens = array_column($sel, 'recorded');
		$stamp = (int)min($whens) - 604800; //near enough to -7 actual days
		$ndstamp = max($whens) + 604800; // for related transfer to happen
		$ids = array_column($sel, 'identifier');

		Stripe\Stripe::setApiKey($privkey);
		$trans = Stripe\BalanceTransaction::all(['created' => ['gte' => $stamp, 'lte' => $ndstamp]]);
		if ($trans) {
			//accumulate related data
			$td = $trans->__toArray(TRUE);
			foreach ($td['data'] as $one) {
				if ($one['status'] == 'available') {
					if ($one['type'] == 'charge' && in_array($one['source'], $ids)) {
						$myat = $one['available_on'];
						if (!isset($data[$myat])) {
							//record transfer & all charges for $one['available_on']
							$data[$myat] = [];
							$data[$myat]['charges'] = [];
							foreach ($td['data'] as $one) {
								if ($one['available_on'] == $myat) {
									if ($one['type'] == 'transfer') {
										$data[$myat]['id'] = $one['id'];
										$data[$myat]['net'] = $one['net']; //integer -10283
									} elseif ($one['type'] == 'charge') {
										$data[$myat]['charges'][] = [
											'id' => $one['source'], //string ch_1AGo1DGajAPEsyVFYNzsVStx
											'gross' => $one['amount'], //integer	10496
											'net' => $one['net'],	//integer	10283
											'when' => $one['created'], //integer timestamp
											'who' => '', //from local table, later
											'what' => ''
										];
										$gets[] = $one['source'];
									}
								}
							}
						}
					}
				}
			}
		}
	}
	$days = $this->GetPreference('transfer_days', 15); //for local picker
} else {
	$dt->setTime(0, 0, 0);
	$dt->modify('-'.$days.'days');
	$stamp = $dt->getTimestamp();
/* https://stripe.com/docs/api#balance_history
API-doc says that a balance_history call will return all balance transactions,
but if provided with a payout parameter (id), it returns the transactions for
that payout only.
>> BUT it doesn't work as such !!
Stripe\BalanceTransaction::all(array('created'=>array('gte'=>$stamp),'type'=>'transfer'); //or 'payout'
Stripe\BalanceTransaction::all(array('created'=>array('gte'=>$stamp),'payout'=>'ID','type'=>'charge'));
*/
	Stripe\Stripe::setApiKey($privkey);
	$trans = Stripe\BalanceTransaction::all(['created' => ['gte' => $stamp]]);
	//accumulate related data
	if ($trans) {
		$td = $trans->__toArray(TRUE);
		foreach ($td['data'] as $one) {
			if ($one['status'] == 'available') {
				$a = $one['available_on'];
				if ($one['type'] == 'transfer') {
					if (!isset($data[$a])) {
						$data[$a] = [];
					}
					$data[$a]['id'] = $one['id']; //for grouping, but not otherwise publicly evident
					$data[$a]['net'] = $one['net']; //integer -10283
				} elseif ($one['type'] == 'charge') {
					if (!isset($data[$a]['charges'])) {
						$data[$a]['charges'] = [];
					}
					$data[$a]['charges'][] = [
						'id' => $one['source'], //string ch_1AGo1DGajAPEsyVFYNzsVStx
						'gross' => $one['amount'], //integer	10496
						'net' => $one['net'],	//integer	10283
						'when' => $one['created'], //integer timestamp
						'who' => '', //from local table, later
						'what' => ''
					];
					$gets[] = $one['source'];
				}
			}
		}
	}
}

if ($gets) {
	$pref = cms_db_prefix();
	$fillers = str_repeat('?,', count($gets) - 1);
	$sql = 'SELECT identifier,paywhat,payfor FROM '.$pref.'module_sgt_record WHERE account_id=? and identifier IN ('.$fillers.'?)';
	array_unshift($gets, $aid);
	$xdata = $db->GetAssoc($sql, $gets);
	$none = $this->Lang('missing');

	foreach ($data as $a => &$one) {
		if (!empty($one['charges'])) {
			foreach ($one['charges'] as &$pay) {
				$rid = $pay['id'];
				$pay['who'] = (!empty($xdata[$rid]['payfor'])) ? $xdata[$rid]['payfor'] : $none;
				$pay['what'] = (!empty($xdata[$rid]['paywhat'])) ? $xdata[$rid]['paywhat'] : $none;
			}
			unset ($pay);
		} else {
			unset($data[$a]);
		}
	}
	unset($one);
}
$cdata = base64_encode(json_encode($data));

$tplvars = [
	'pmod' => $padm,
	'backtomod_nav' => $this->CreateLink($id, 'defaultadmin', $returnid, '&#171; '.$this->Lang('title_mainpage'))
	.' '.$this->CreateLink($id, 'administer', $returnid, '&#171; '.$this->Lang('records'), ['account_id' => $aid]),
	'start_form' => $this->CreateFormStart($id, 'transfers', $returnid, 'POST', '', '', '', ['account_id' => $aid, 'duration' => $days, 'sdata' => $cdata]),
	'end_form' => $this->CreateFormEnd(),
	'title' => $this->Lang('title_transfers2', $row['name'])
];

if (!empty($params['message'])) {
	$tplvars['message'] = $params['message'];
}

//script accumulators
$jsfuncs = [];
$jsincs = [];
$jsloads = [];
$baseurl = $this->GetModuleURLPath();

$theme = ($this->before20) ? cmsms()->get_variable('admintheme') :
	cms_utils::get_theme_object();
$link_export = '<a href="javascript:exptrans(\'%s\')">'.
	$theme->DisplayImage('icons/system/export.gif', $this->Lang('tip_exportsel3'), '', '', 'systemicon').'</a>';

$fmt = $row['amountformat'];
$symbol = StripeGate\Utils::GetSymbol($row['currency']);

$rows = [];

foreach ($data as $a => &$one) {
	if (!empty($one['charges'])) {
		$dt->setTimestamp($a);
		$when = $dt->format('Y-m-d G:i');
		foreach ($one['charges'] as &$pay) {
			$rid = $pay['id'];
			$oneset = new stdClass();
			$oneset->token = $one['id'];
			$oneset->when = $when;
			$dt->setTimestamp($pay['when']);
			$oneset->paidat = $dt->format('Y-m-d G:i');
			$oneset->gross = StripeGate\Utils::GetReportAmount($pay['gross'], $fmt, $symbol);
			$oneset->net = StripeGate\Utils::GetReportAmount($pay['net'], $fmt, $symbol);
			$oneset->what = $pay['what'];
			$oneset->who = $pay['who'];
			$oneset->export = sprintf($link_export, $one['id']);
			$oneset->selected = $this->CreateInputCheckbox($id, 'sel[]', $rid, -1);
			$rows[] = $oneset;
		}
		unset($pay);
	}
}
unset($one);

$pagerows = 15; //arbitrary initial page-length

$tplvars['rows'] = $rows;
$rcount = count($rows);
if ($rcount) {
	$tplvars += [
	'title_token' => $this->Lang('title_token'),
	'title_transferred' => $this->Lang('title_transferred'),
	'title_paidat' => $this->Lang('recorded'),
	'title_grossamount' => $this->Lang('title_paygross'),
	'title_netamount' => $this->Lang('title_paynet'),
	'title_for' => $this->Lang('title_who'),
	'title_what' => $this->Lang('title_what')
	];

	$jsfuncs[] = <<<EOS
function exptrans(id) {
 var onesel,
  anysel = false;
 $('#transferdata > tbody > tr').each(function() {
  if (this.cells[0].innerHTML === id) {
   onesel = true;
   anysel = true;
  } else {
   onesel = false;
  }
  $(this).find('input[type="checkbox"]').attr('checked',onesel);
 });
 if (anysel) {
  $('#{$id}export').click();
 } else {
  event.preventDefault();
 }
}
EOS;
	if ($rcount > 1) {
		$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.metadata.min.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.SSsort.min.js"></script>
EOS;
		$jsloads[] = <<<EOS
 $('#transferdata').addClass('table_sort').SSsort({
  sortClass: 'SortAble',
  ascClass: 'SortUp',
  descClass: 'SortDown',
  oddClass: 'row1',
  evenClass: 'row2',
  oddsortClass: 'row1s',
  evensortClass: 'row2s',
  paginate: true,
  pagesize: {$pagerows},
  currentid: 'cpage',
  countid: 'tpage'
 });
EOS;
		$jsfuncs[] = <<<EOS
function select_all(cb) {
 $('#transferdata > tbody').find('input[type="checkbox"]').attr('checked',cb.checked);
}
EOS;
		$tplvars['header_checkbox'] =
			$this->CreateInputCheckbox($id, 'selectall', true, false, 'onclick="select_all(this);"');
	} else { //$rcount == 1
		$tplvars['header_checkbox'] = NULL;
	}

	if ($pagerows && $rcount > $pagerows) {
		//more setup for SSsort
		$choices = [strval($pagerows) => $pagerows];
		$f = ($pagerows < 4) ? 5 : 2;
		$n = $pagerows * $f;
		if ($n < $rcount) {
			$choices[strval($n)] = $n;
		}
		$n *= 2;
		if ($n < $rcount) {
			$choices[strval($n)] = $n;
		}
		$choices[$this->Lang('all')] = 0;
		$curpg = '<span id="cpage">1</span>';
		$totpg = '<span id="tpage">'.ceil($rcount / $pagerows).'</span>';

		$tplvars += [
			'hasnav' => 1,
			'first' => '<a href="javascript:pagefirst()">'.$this->Lang('first').'</a>',
			'prev' => '<a href="javascript:pageback()">'.$this->Lang('previous').'</a>',
			'next' => '<a href="javascript:pageforw()">'.$this->Lang('next').'</a>',
			'last' => '<a href="javascript:pagelast()">'.$this->Lang('last').'</a>',
			'pageof' => $this->Lang('pageof', $curpg, $totpg),
			'rowchanger' => $this->CreateInputDropdown($id, 'pagerows', $choices, -1, $pagerows,
				'onchange="pagerows(this);"').'&nbsp;&nbsp;'.$this->Lang('pagerows')
		];

		$jsfuncs[] = <<<EOS
function pagefirst() {
 $.SSsort.movePage($('#transferdata')[0],false,true);
}
function pagelast() {
 $.SSsort.movePage($('#transferdata')[0],true,true);
}
function pageforw() {
 $.SSsort.movePage($('#transferdata')[0],true,false);
}
function pageback() {
 $.SSsort.movePage($('#transferdata')[0],false,false);
}
function pagerows(cb) {
 $.SSsort.setCurrent($('#transferdata')[0],'pagesize',parseInt(cb.value));
}
EOS;
	} else {
		$tplvars['hasnav'] = 0;
	}

	$jsfuncs[] = <<<EOS
function sel_count() {
 var cb = $('input[name="{$id}sel[]"]:checked');
 return cb.length;
}
function any_selected() {
 return (sel_count() > 0);
}
function dayschange(cb) {
 $(cb).closest('form').submit();
}
EOS;
	$s = $this->Lang('dayscount');
	$choices = [];
	foreach ([15, 45, 60] as $n) {
		$i = sprintf($s, $n);
		$choices[$i] = $n;
	}
	$tplvars['duration'] = $this->CreateInputDropdown($id, 'duration', $choices, -1,
		$days, 'onchange="dayschange(this);"');
	//TODO on-change js for duration
	$tplvars['export'] = $this->CreateInputSubmit($id, 'export', $this->Lang('export'),
	'title="'.$this->Lang('tip_exportsel').'" onclick="return any_selected();"');
} else { //$rcount == 0
	$tplvars['norecords'] = $this->Lang('norecords');
}

$tplvars['close'] = $this->CreateInputSubmit($id, 'close', $this->Lang('close'));

$jsall = NULL;
StripeGate\Utils::MergeJS($jsincs, $jsfuncs, $jsloads, $jsall);
unset($jsincs);
unset($jsfuncs);
unset($jsloads);

echo StripeGate\Utils::ProcessTemplate($this, 'transfers.tpl', $tplvars);
if ($jsall) {
	echo $jsall;
}
