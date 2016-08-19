<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

$padm = $this->CheckPermission('ModifyStripeGateProperties');
if ($padm) {
	$pmod = true;
	$padd = true;
	$pdel = true;
} else {
	$pmod = $this->CheckPermission('ModifyStripeAccount');
	$padd = $pmod;
	$pdel = $pmod;
}
$psee = $this->CheckPermission('UseStripeAccount');
if (!($padm || $pmod || $psee)) exit;

$pdev = $this->CheckPermission('Modify Any Page');
$mod = $padm || $pmod;

if (isset($params['submit'])) {
	if ($padm) {
		$oldpw = $this->GetPreference('masterpass');
		if ($oldpw)
			$oldpw = StripeGate\Utils::unfusc($oldpw);

		$newpw = trim($params['masterpass']);
		if ($oldpw != $newpw) {
			//update all data which uses current password
			$pre = cms_db_prefix();
			$sql = 'SELECT account_id,privtoken,testprivtoken FROM '.$pre.'module_sgt_account';
			$rst = $db->Execute($sql);
			if ($rst) {
				$sql = 'UPDATE '.$pre.'module_sgt_account SET privtoken=?,testprivtoken=? WHERE account_id=?';
				while (!$rst->EOF) {
					$t = StripeGate\Utils::decrypt_value($mod,$rst->fields[1],$oldpw);
					$t = ($newpw) ? StripeGate\Utils::encrypt_value($mod,$t,$newpw):StripeGate\Utils::fusc($t);
					$t2 = StripeGate\Utils::decrypt_value($mod,$rst->fields[2],$oldpw);
					$t2 = ($newpw) ? StripeGate\Utils::encrypt_value($mod,$t2,$newpw):StripeGate\Utils::fusc($t2);
					$db->Execute($sql,array($t,$t2,$rst->fields[0]));
					if (!$rst->MoveNext())
						break;
				}
				$rst->Close();
			}
			//TODO if record-table data is encrypted
			if ($newpw)
				$newpw = StripeGate\Utils::fusc($newpw);
			$this->SetPreference('masterpass',$newpw);
		}
	}
	$params['activetab'] = 'settings';
}

$tplvars = array(
	'padm'=>$padm,
	'padd'=>$padd,
	'pdel'=>$pdel,
	'pmod'=>$mod, //not $pmod
	'pdev'=>$pdev
);

$indx = 0;
if (isset($params['activetab'])) {
	switch ($params['activetab']) {
	 case 'settings':
		$indx = 1;
		break;
	}
}

$tplvars['tabsheader'] = $this->StartTabHeaders().
 $this->SetTabHeader('main',$this->Lang('title_maintab'),$indx==0).
 $this->SetTabHeader('settings',$this->Lang('title_settingstab'),$indx==1).
 $this->EndTabHeaders().$this->StartTabContent();

//NOTE CMSMS 2+ barfs if EndTab() is called before EndTabContent() - some craziness there !!!
$tplvars = $tplvars + array(
	'tabsfooter' => $this->EndTabContent(),
	'tab_end' => $this->EndTab(),
	'form_end' => $this->CreateFormEnd()
);

$jsincs = array();
$jsfuncs = array();
$jsloads = array();
$baseurl = $this->GetModuleURLPath();

if (!empty($params['message']))
	$tplvars['message'] = $params['message'];

//~~~~~~~~~~~~~~~ ACCOUNTS TAB ~~~~~~~~~~~~~~~~

$tplvars['tabstart_main'] = $this->StartTab('main');
$tplvars['formstart_main'] = $this->CreateFormStart($id,'process');

$theme = ($this->before20) ? cmsms()->get_variable('admintheme'):
	cms_utils::get_theme_object();

if ($mod) {
	$icon_open = $theme->DisplayImage('icons/system/edit.gif',$this->Lang('tip_edit'),'','','systemicon');
	$t = $this->Lang('tip_toggle');
	$icon_yes = $theme->DisplayImage('icons/system/true.gif',$t,'','','systemicon');
	$icon_no = $theme->DisplayImage('icons/system/false.gif',$t,'','','systemicon');
	$icon_export = $theme->DisplayImage('icons/system/export.gif',$this->Lang('tip_export'),'','','systemicon');
	$t = $this->Lang('tip_admin');
	$icon_admin = '<img src="'.$baseurl.'/images/administer.png" alt="'.$t.'" title="'.$t.'" class="systemicon" />';
} else {
	$icon_open = $theme->DisplayImage('icons/system/view.gif',$this->Lang('tip_view'),'','','systemicon');
	$yes = $this->Lang('yes');
	$no = $this->Lang('no');
}
if ($pdel)
	$icon_del = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('tip_delete'),'','','systemicon');

$items = array();

$pre = cms_db_prefix();
$test = ($padm) ? '1':'A.owner=-1 OR A.owner='.get_userid(FALSE);
$sql = <<<EOS
SELECT A.account_id,A.name,A.alias,A.isdefault,A.isactive,U.first_name,U.last_name,COALESCE (R.count,0) AS record_count
FROM {$pre}module_sgt_account A
LEFT JOIN {$pre}users U ON A.owner = U.user_id
LEFT JOIN (SELECT account_id,COUNT(*) as count FROM {$pre}module_sgt_record GROUP BY account_id) R ON A.account_id = R.account_id
WHERE {$test}
ORDER BY A.name
EOS;

$rows = $db->GetAll($sql);
if ($rows) {
	foreach ($rows as $row) {
		$thisid	= (int)$row['account_id'];
		$oneset = new stdClass();
//		$oneset->id = $thisid; //may be hidden
		if ($mod)
			$oneset->name = $this->CreateLink($id,'update',$returnid,$row['name'],array('account_id'=>$thisid));
		else
			$oneset->name = $row['name'];

		if ($pdev) {
			if ($row['alias'])
				$oneset->alias = '{StripeGate account=\''.$row['alias'].'\'}';
			else
				$oneset->alias = '{StripeGate account='.$thisid.'}';
		} else
			$oneset->alias = $row['alias'];

		$name = trim($row['first_name'].' '.$row['last_name']);
		if ($name == '') $name = '<'.$this->Lang('noowner').'>';
		$oneset->ownername	= $name;

		if ($mod) {
			if ($row['isdefault']) //it's active so create a deactivate-link
				$oneset->default = $this->CreateLink($id,'toggledeflt',$returnid,$icon_yes,
					array('account_id'=>$thisid,'current'=>true));
			else //it's inactive so create an activate-link
				$oneset->default = $this->CreateLink($id,'toggledeflt',$returnid,$icon_no,
					array('account_id'=>$thisid,'current'=>false));
			if ($row['isactive'])
				$oneset->active = $this->CreateLink($id,'toggleactive',$returnid,$icon_yes,
					array('account_id'=>$thisid,'current'=>true));
			else
				$oneset->active = $this->CreateLink($id,'toggleactive',$returnid,$icon_no,
					array('account_id'=>$thisid,'current'=>false));
			if ($row['record_count'] > 0) {
				$oneset->adminlink = $this->CreateLink($id,'administer',$returnid,$icon_admin,
					array('account_id'=>$thisid));
				$oneset->exportlink = $this->CreateLink($id,'export',$returnid,$icon_export,
					array('account_id'=>$thisid));
			} else {
				$oneset->adminlink = NULL;
				$oneset->exportlink = NULL;
			}
		} else {
			$oneset->default = ($row['isdefault']) ? $yes : $no;
			$oneset->active = ($row['isactive']) ? $yes : $no;
		}

		//view or edit
		$oneset->editlink = $this->CreateLink($id,'update',$returnid,$icon_open,
			array('account_id'=>$thisid));

		if ($pdel)
			$oneset->deletelink = $this->CreateLink($id,'delete',$returnid,$icon_del,
				array('account_id'=>$thisid),
				$this->Lang('delitm_confirm',$row['name']));
		else
			$oneset->deletelink = NULL;

		if ($padm || $pdel)
			$oneset->selected = $this->CreateInputCheckbox($id,'selitems[]',$thisid,-1);
		else
			$oneset->selected = NULL;

		$items[] = $oneset;
	}
}

$tplvars['items'] = $items;
if ($items) {
	//table titles
	$tplvars = $tplvars + array(
		'title_id' => $this->Lang('title_id'),
		'title_name' => $this->Lang('name'),
		'title_alias' => (($pdev)?$this->Lang('title_tag'):$this->Lang('title_alias')),
		'title_owner' => $this->Lang('title_owner'),
		'title_default' => $this->Lang('title_default'),
		'title_active' => $this->Lang('title_active')
	);

	if ($padm || $pdel) {
		if (count($items) > 1) {
			$tplvars['selectall'] =
				$this->CreateInputCheckbox($id,'selectall',true,false,'onclick="select_all(this);"');
			$jsfuncs[] = <<<EOS
function select_all(cb)
{
 var st = $(cb).attr('checked');
 if (! st) st = false;
 $('input[name="{$id}selitems[]"]').attr('checked',st);
}

EOS;
		}

		$jsfuncs[] = <<<EOS
function sel_count()
{
 var cb = $('input[name="{$id}selitems[]"]:checked');
 return cb.length;
}

EOS;
	}
	if ($mod) {
		$tplvars['export'] = $this->CreateInputSubmit($id,'export',$this->Lang('export'),
			'title="'.$this->Lang('tip_exportsel2').'" onclick="return confirm_sel_count();"');
		$jsfuncs[] = <<<EOS
function confirm_sel_count()
{
 return (sel_count() > 0);
}

EOS;
	}
	if ($pdel) {
		$tplvars['delete'] = $this->CreateInputSubmit($id,'delete',$this->Lang('delete'),
		'title="'.$this->Lang('tip_deletesel').'" onclick="return confirm_delete();"');
		$t = $this->Lang('delsel_confirm');
		$jsfuncs[] = <<<EOS
function confirm_delete()
{
 if (sel_count() > 0)
  return confirm('{$t}');
 return false;
}

EOS;
	}

	if (count($items) > 1) {
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
  pagesize: 20,
  currentid: 'cpage',
  countid: 'tpage'
 });

EOS;
	}
} else
	$tplvars['nodata'] = $this->Lang('nodata');

if ($padd)
	$tplvars['add'] =
	 $this->CreateLink($id,'update',$returnid,
		 $theme->DisplayImage('icons/system/newobject.gif',$this->Lang('additem'),'','','systemicon'),
		 array('account_id'=>-1),'',false,false,'')
	 .' '.
	 $this->CreateLink($id,'update',$returnid,
		 $this->Lang('additem'),
		 array('account_id'=>-1),'',false,false,'class="pageoptions"');

//~~~~~~~~~~~~~~~ SETTINGS TAB ~~~~~~~~~~~~~~~~

$tplvars['tabstart_settings'] = $this->StartTab('settings');
$tplvars['formstart_settings'] = $this->CreateFormStart($id,'defaultadmin');

//URL for running action.webhook, with dummy returnid
$url = $this->CreateLink ('_','webhook',1,'',array(),'',TRUE);
//strip the fake returnid, so that the default will be used
$sep = strpos($url,'&amp;');
$newurl = substr($url,0,$sep);
$tplvars = $tplvars + array(
	'title_hook' => $this->Lang('reports_url'),
	'info_hook' => $this->Lang('help_reports_url'),
	'url_hook' => $newurl
);

$tplvars['title_updir'] = $this->Lang('title_updir');
$tplvars['input_updir'] = $this->CreateInputText($id,'uploads_dir',$this->GetPreference('uploads_dir'),30,60)
.'<br />'.$this->Lang('help_updir');

$tplvars['title_password'] = $this->Lang('title_password');
$pw = $this->GetPreference('masterpass');
if ($pw)
	$pw = StripeGate\Utils::unfusc($pw);

$tplvars['input_password'] =
	$this->CreateTextArea(false,$id,$pw,'masterpass','cloaked',
		$id.'passwd','','',40,2);

$jsincs[] = '<script type="text/javascript" src="'.$baseurl.'/include/jquery-inputCloak.min.js"></script>';
$jsloads[] = <<<EOS
 $('#{$id}passwd').inputCloak({
  type:'see4',
  symbol:'\u25CF'
 });

EOS;

if ($padm) {
	$tplvars['submit'] = $this->CreateInputSubmit($id,'submit',$this->Lang('submit'));
	$tplvars['cancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'));
}

if ($jsloads) {
	$jsfuncs[] = '$(document).ready(function() {
';
	$jsfuncs = array_merge($jsfuncs,$jsloads);
	$jsfuncs[] = '});
';
}
$tplvars['jsfuncs'] = $jsfuncs;
$tplvars['jsincs'] = $jsincs;

echo StripeGate\Utils::ProcessTemplate($this,'adminpanel.tpl',$tplvars);
