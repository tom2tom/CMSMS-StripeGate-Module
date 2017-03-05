<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if (!function_exists('get_report_amount')) {
 function get_report_amount($units,$format,$char)
 {
	if (preg_match('/^(.*)?(S)(\W+)?(\d*)$/',$format,$matches)) {
		$places = strlen($matches[4]);
		$times = pow(10,$places);
		$num = number_format($units/$times,$places);
		if ($matches[1]) {
			if (strpos('.',$num) !== FALSE)
				$num = str_replace('.',$char,$num); //workaround PHP<5.4
		} else {
			if ($matches[3] != '.')
				$num = str_replace('.',$matches[3],$num);
		}
		return $num;
	} else
		return number_format($units/100,2);
 }
}

$padm = $this->CheckPermission('ModifyStripeGateProperties');
$pmod = $padm || $this->CheckPermission('ModifyStripeAccount');
$psee = $padm || $this->CheckPermission('UseStripeAccount');

if (!($padm || $pmod || $psee)) exit;

$aid = (int)$params['account_id'];
$pref = cms_db_prefix();
$row = $db->GetRow('SELECT name,currency,amountformat FROM '.$pref.'module_sgt_account WHERE account_id=?',[$aid]);
$symbol = StripeGate\Utils::GetSymbol($row['currency']);

$tplvars = [
	'pmod' => $pmod,
	'backtomod_nav' => $this->CreateLink($id,'defaultadmin',$returnid,'&#171; '.$this->Lang('title_mainpage')),
	'start_form' => $this->CreateFormStart($id,'processrecords',$returnid,'POST','','','',['account_id'=>$aid]),
	'end_form' => $this->CreateFormEnd(),

	'title' => $this->Lang('title_account',$row['name']),
	'title_submitted' => $this->Lang('title_when'),
	'title_amount' => $this->Lang('title_amount'),
	'title_what' => $this->Lang('title_what'),
	'title_for' => $this->Lang('title_who'),
	'title_token' => $this->Lang('title_token'),
];

//script accumulators
$jsfuncs = [];
$jsincs = [];
$jsloads = [];
$baseurl = $this->GetModuleURLPath();

if (!empty($params['message']))
	$tplvars['message'] = $params['message'];

$sql = 'SELECT * FROM '.$pref.'module_sgt_record WHERE account_id=? ORDER BY recorded DESC, payfor ASC';
$data = $db->GetArray($sql,[$aid]);

$theme = ($this->before20) ? cmsms()->get_variable('admintheme'):
	cms_utils::get_theme_object();

$icon_delete = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('delete'),'','','systemicon');
$icon_export = $theme->DisplayImage('icons/system/export.gif',$this->Lang('export'),'','','systemicon');

foreach ($data as &$one) {
	$rid = (int)($one['record_id']);
	$oneset = new stdClass();
	$oneset->submitted =  date('Y-m-d H:i:s',$one['recorded']);
	$oneset->amount = get_report_amount($one['amount'],$row['amountformat'],$symbol);
	$oneset->what = $one['paywhat'];
	$oneset->who = $one['payfor'];
	$oneset->token = $one['identifier'];
	$oneset->export = $this->CreateLink($id,'exportrecord','',
		$icon_export,['record_id'=>$rid,'account_id'=>$aid]);
	if ($pmod)
	  $oneset->delete = $this->CreateLink($id,'deleterecord','',
		$icon_delete,['record_id'=>$rid,'account_id'=>$aid],
		$this->Lang('delitm_confirm',$oneset->token));
	$oneset->selected = $this->CreateInputCheckbox($id,'sel[]',$rid,-1);
	$rows[] = $oneset;
}
unset($one);

$pagerows = 15; //arbitrary initial page-length

$tplvars['rows'] = $rows;
$rcount = count($rows);
if ($rcount) {
	if ($rcount > 1) {
		$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/include/jquery.metadata.min.js"></script>
<script type="text/javascript" src="{$baseurl}/include/jquery.SSsort.min.js"></script>
EOS;
		$jsloads[] = <<<EOS
 $('#itemdata').addClass('table_sort').SSsort({
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

		$tplvars['header_checkbox'] =
			$this->CreateInputCheckbox($id,'selectall',true,false,'onclick="select_all(this);"');

		$jsfuncs[] = <<<EOS
function select_all(cb) {
 $('#itemdata > tbody').find('input[type="checkbox"]').attr('checked',cb.checked);
}

EOS;

/*	$jsfuncs[] = <<< EOS
 $.SSsort.addParser({
  id: 'textinput',
  is: function(s,node) {
   var n = node.childNodes[0];
   return (n && n.nodeName.toLowerCase() == 'input' && n.type.toLowerCase() == 'text');
  },
  format: function(s,node) {
   return $.trim(node.childNodes[0].value);
  },
  watch: true,
  type: 'text'
 });

EOS;
*/
	} //$rcount > 1
	else
		$tplvars['header_checkbox'] = NULL;

	if ($pagerows && $rcount>$pagerows) {
		//more setup for SSsort
		$choices = [strval($pagerows) => $pagerows];
		$f = ($pagerows < 4) ? 5 : 2;
		$n = $pagerows * $f;
		if ($n < $rcount)
			$choices[strval($n)] = $n;
		$n *= 2;
		if ($n < $rcount)
			$choices[strval($n)] = $n;
		$choices[$this->Lang('all')] = 0;
		$curpg='<span id="cpage">1</span>';
		$totpg='<span id="tpage">'.ceil($rcount/$pagerows).'</span>';

		$tplvars = $tplvars + [
			'hasnav' => 1,
			'first' => '<a href="javascript:pagefirst()">'.$this->Lang('first').'</a>',
			'prev' => '<a href="javascript:pageback()">'.$this->Lang('previous').'</a>',
			'next' => '<a href="javascript:pageforw()">'.$this->Lang('next').'</a>',
			'last' => '<a href="javascript:pagelast()">'.$this->Lang('last').'</a>',
			'pageof' => $this->Lang('pageof',$curpg,$totpg),
			'rowchanger' => $this->CreateInputDropdown($id,'pagerows',$choices,-1,$pagerows,
			'onchange="pagerows(this);"').'&nbsp;&nbsp;'.$this->Lang('pagerows')
		];

		$jsfuncs[] = <<<EOS
function pagefirst() {
 $.SSsort.movePage($('#itemdata')[0],false,true);
}
function pagelast() {
 $.SSsort.movePage($('#itemdata')[0],true,true);
}
function pageforw() {
 $.SSsort.movePage($('#itemdata')[0],true,false);
}
function pageback() {
 $.SSsort.movePage($('#itemdata')[0],false,false);
}
function pagerows(cb) {
 $.SSsort.setCurrent($('#itemdata')[0],'pagesize',parseInt(cb.value));
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
function confirm_selected(msg) {
 if (sel_count() > 0) {
  return confirm(msg);
 } else {
  return false;
 }
}

EOS;
	//if ($X) ? perm
		$tplvars['export'] = $this->CreateInputSubmit($id,'export',$this->Lang('export'),
		'title="'.$this->Lang('tip_exportsel').'" onclick="return any_selected();"');
	if ($pmod)
		$tplvars['delete'] = $this->CreateInputSubmit($id,'delete',$this->Lang('delete'),
		'title="'.$this->Lang('tip_deletesel2').
		'" onclick="return confirm_selected(\''.$this->Lang('delsel_confirm2').'\');"');
} //$rcount
/*should never happen, in this context
else {
	$tplvars['norecords'] = $this->Lang('norecords');
}
*/

$jsall = NULL;
StripeGate\Utils::MergeJS($jsincs,$jsfuncs,$jsloads,$jsall);
unset($jsincs);
unset($jsfuncs);
unset($jsloads);

echo StripeGate\Utils::ProcessTemplate($this,'administer.tpl',$tplvars);
if ($jsall) {
	echo $jsall;
}
