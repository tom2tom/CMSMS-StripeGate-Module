<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
/*
$result = json_decode('
{
	"id": "ch_17Olb7GajAPEsyVFKbv1Xj0H",
	"object": "charge",
	"amount": 2000,
	"amount_refunded": 0,
	"application_fee": null,
	"balance_transaction": "txn_17Olb7GajAPEsyVFymoUOx75",
	"captured": true,
	"created": 1451726573,
	"currency": "usd",
	"customer": null,
	"description": null,
	"destination": null,
	"dispute": null,
	"failure_code": null,
	"failure_message": null,
	"fraud_details": [],
	"invoice": null,
	"livemode": false,
	"metadata": [],
	"paid": true,
	"receipt_email": null,
	"receipt_number": null,
	"refunded": false,
	"refunds": {
		"object": "list",
		"data": [],
		"has_more": false,
		"total_count": 0,
		"url": "\/v1\/charges\/ch_17Olb7GajAPEsyVFKbv1Xj0H\/refunds"
	},
	"shipping": null,
	"source": {
		"id": "card_17Olb7GajAPEsyVFeIZ6PDc1",
		"object": "card",
		"address_city": null,
		"address_country": null,
		"address_line1": null,
		"address_line1_check": null,
		"address_line2": null,
		"address_state": null,
		"address_zip": null,
		"address_zip_check": null,
		"brand": "Visa",
		"country": "US",
		"customer": null,
		"cvc_check": null,
		"dynamic_last4": null,
		"exp_month": 8,
		"exp_year": 2018,
		"fingerprint": "fkrclzqDN7Vm2zs9",
		"funding": "credit",
		"last4": "4242",
		"metadata": [],
		"name": null,
		"tokenization_method": null
	},
	"statement_descriptor": null,
	"status": "succeeded"
}'
);

$id = $result->id;
unset($result->source);
$this->Crash();
*/

$padm = $this->CheckPermission('ModifyStripeGateProperties');
if($padm)
{
	$padd = true;
	$pdel = true;
	$pmod = true;
}
else
{
	$padd = $this->CheckPermission('UseStripeGateAccount');
	$pdel = $padd;
	$pmod = $padd;
}
if(!($padm || $pmod)) exit;

$pdev = $this->CheckPermission('Modify Any Page');
$mod = $padm || $pmod;

if(isset($params['submit']))
{
	if($padm)
	{
		$oldpw = $this->GetPreference('masterpass');
		if($oldpw)
			$oldpw = stripe_utils::unfusc($oldpw);

		$newpw = trim($params['masterpass']);
		if($oldpw != $newpw)
		{
			//update all data which uses current password
			$pre = cms_db_prefix();
			$sql = 'SELECT account_id,privtoken,testprivtoken FROM '.$pre.'module_sgt_account';
			$rst = $db->Execute($sql);
			if($rst)
			{
				$sql = 'UPDATE '.$pre.'module_sgt_account SET privtoken=?,testprivtoken=? WHERE account_id=?';
				while(!$rst->EOF)
				{
					$t = stripe_utils::decrypt_value($mod,$rst->fields[1],$oldpw);
					$t = ($newpw) ? stripe_utils::encrypt_value($mod,$t,$newpw):stripe_utils::fusc($t);
					$t2 = stripe_utils::decrypt_value($mod,$rst->fields[2],$oldpw);
					$t2 = ($newpw) ? stripe_utils::encrypt_value($mod,$t2,$newpw):stripe_utils::fusc($t2);
					$db->Execute($sql,array($t,$t2,$rst->fields[0]));
					if(!$rst->MoveNext())
						break;
				}
				$rst->Close();
			}
			//TODO if record-table data is encrypted
			if($newpw)
				$newpw = stripe_utils::fusc($newpw);
			$this->SetPreference('masterpass',$newpw);
		}
	}
	$params['activetab'] = 'settings';
}

$smarty->assign('padm',$padm);
$smarty->assign('padd',$padd);
$smarty->assign('pdel',$pdel);
$smarty->assign('pmod',$mod); //not $pmod
$smarty->assign('pdev',$pdev);

$indx = 0;
if(isset($params['activetab']))
{
	switch($params['activetab'])
	{
	 case 'settings':
		$indx = 1;
		break;
	}
}

$smarty->assign('tabsheader',$this->StartTabHeaders().
 $this->SetTabHeader('main',$this->Lang('title_maintab'),$indx==0).
 $this->SetTabHeader('settings',$this->Lang('title_settingstab'),$indx==1).
 $this->EndTabHeaders().$this->StartTabContent());

//NOTE CMSMS 2+ barfs if EndTab() is called before EndTabContent() - some craziness there !!!
$smarty->assign('tabsfooter',$this->EndTabContent());
$smarty->assign('tab_end',$this->EndTab());
$smarty->assign('form_end',$this->CreateFormEnd());

$jsincs = array();
$jsfuncs = array();
$jsloads = array();
$baseurl = $this->GetModuleURLPath();

if(!empty($params['message']))
	$smarty->assign('message',$params['message']);

//~~~~~~~~~~~~~~~ ACCOUNTS TAB ~~~~~~~~~~~~~~~~

$smarty->assign('tabstart_main',$this->StartTab('main'));
$smarty->assign('formstart_main',$this->CreateFormStart($id,'processitems'));

$theme = ($this->before20) ? cmsms()->get_variable('admintheme'):
	cms_utils::get_theme_object();

if($mod)
{
	$iconopen = $theme->DisplayImage('icons/system/edit.gif',$this->Lang('edititem'),'','','systemicon');
	$t = $this->Lang('tip_toggle');
	$iconyes = $theme->DisplayImage('icons/system/true.gif',$t,'','','systemicon');
	$iconno = $theme->DisplayImage('icons/system/false.gif',$t,'','','systemicon');
	$t = $this->Lang('admin_records');
	$iconadmin = '<img src="'.$baseurl.'/images/administer.png" alt="'.$t.'" title="'.$t.'" class="systemicon" />';
}
else
{
	$iconopen = $theme->DisplayImage('icons/system/view.gif',$this->Lang('viewitem'),'','','systemicon');
	$yes = $this->Lang('yes');
	$no = $this->Lang('no');
}
if($pdel)
	$icondel = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('deleteitem'),'','','systemicon');

$items = array();

$pre = cms_db_prefix();
$sql = <<<EOS
SELECT A.account_id,A.name,A.alias,A.isdefault,A.isactive,U.first_name,U.last_name,COALESCE (R.count,0) AS record_count
FROM {$pre}module_sgt_account A
LEFT JOIN {$pre}users U ON A.owner = U.user_id
LEFT JOIN (SELECT account_id,COUNT(*) as count FROM {$pre}module_sgt_record GROUP BY account_id) R ON A.account_id = R.account_id
ORDER BY A.name
EOS;

$rows = $db->GetAll($sql);
if($rows)
{
	foreach($rows as $row)
	{
		$thisid	= (int)$row['account_id'];
		$oneset = new stdClass();
//		$oneset->id = $thisid; //may be hidden
		if($mod)
			$oneset->name = $this->CreateLink($id,'update',$returnid,$row['name'],array('account_id'=>$thisid));
		else
			$oneset->name = $row['name'];
		
		if($pdev)
		{
			if($row['alias'])
				$oneset->alias = '{StripeGate account=\''.$row['alias'].'\'}';
			else
				$oneset->alias = '{StripeGate account='.$thisid.'}';
		}
		else
			$oneset->alias = $row['alias'];

		$name = trim($row['first_name'].' '.$row['last_name']);
		if($name == '') $name = '<'.$this->Lang('noowner').'>';
		$oneset->ownername	= $name;

		if($mod)
		{
			if($row['isdefault']) //it's active so create a deactivate-link
				$oneset->default = $this->CreateLink($id,'toggledeflt',$returnid,$iconyes,
					array('account_id'=>$thisid,'current'=>true));
			else //it's inactive so create an activate-link
				$oneset->default = $this->CreateLink($id,'toggledeflt',$returnid,$iconno,
					array('account_id'=>$thisid,'current'=>false));
			if($row['isactive'])
				$oneset->active = $this->CreateLink($id,'toggleactive',$returnid,$iconyes,
					array('account_id'=>$thisid,'current'=>true));
			else
				$oneset->active = $this->CreateLink($id,'toggleactive',$returnid,$iconno,
					array('account_id'=>$thisid,'current'=>false));
			if($row['record_count'] > 0)
				$oneset->adminlink = $this->CreateLink($id,'administer',$returnid,$iconadmin,
					array('account_id'=>$thisid));
			else
				$oneset->adminlink = NULL;
		}
		else
		{
			$oneset->default = ($row['isdefault']) ? $yes : $no;
			$oneset->active = ($row['isactive']) ? $yes : $no;
		}
		
		//view or edit
		$oneset->editlink = $this->CreateLink($id,'update',$returnid,$iconopen,
			array('account_id'=>$thisid));

		if($pdel)
			$oneset->deletelink = $this->CreateLink($id,'delete',$returnid,$icondel,
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

$smarty->assign('items',$items);
if($items)
{
	//table titles
	$smarty->assign('title_id',$this->Lang('title_id'));
	$smarty->assign('title_name',$this->Lang('name'));
	if($pdev)
		$smarty->assign('title_alias',$this->Lang('title_tag'));
	else
		$smarty->assign('title_alias',$this->Lang('title_alias'));
	$smarty->assign('title_owner',$this->Lang('title_owner'));
	$smarty->assign('title_default',$this->Lang('title_default'));
	$smarty->assign('title_active',$this->Lang('title_active'));

	if($padm || $pdel)
	{
		if(count($items) > 1)
		{
			$smarty->assign('selectall',
				$this->CreateInputCheckbox($id,'selectall',true,false,'onclick="select_all(this);"'));
			$jsfuncs[] = <<<EOS
function select_all(cb)
{
 var st = $(cb).attr('checked');
 if(! st) st = false;
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
	if($padm)
	{
		$smarty->assign('export',$this->CreateInputSubmit($id,'export',$this->Lang('export'),
			'title="'.$this->Lang('tip_exportsel').'" onclick="return confirm_sel_count();"'));
		$jsfuncs[] = <<<EOS
function confirm_sel_count()
{
 return (sel_count() > 0);
}

EOS;
	}
	if($pdel)
	{
		$smarty->assign('delete',$this->CreateInputSubmit($id,'delete',$this->Lang('delete'),
		'title="'.$this->Lang('tip_deletesel').'" onclick="return confirm_delete();"'));
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

	if(count($items) > 1)
	{
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
}
else
	$smarty->assign('nodata',$this->Lang('nodata'));

if($padd)
	$smarty->assign('add',
	 $this->CreateLink($id,'update',$returnid,
		 $theme->DisplayImage('icons/system/newobject.gif',$this->Lang('additem'),'','','systemicon'),
		 array('account_id'=>-1),'',false,false,'')
	 .' '.
	 $this->CreateLink($id,'update',$returnid,
		 $this->Lang('additem'),
		 array('account_id'=>-1),'',false,false,'class="pageoptions"'));

//~~~~~~~~~~~~~~~ SETTINGS TAB ~~~~~~~~~~~~~~~~

$smarty->assign('tabstart_settings',$this->StartTab('settings'));
$smarty->assign('formstart_settings',$this->CreateFormStart($id,'defaultadmin'));

$smarty->assign('title_updir',$this->Lang('title_updir'));
$smarty->assign('input_updir',$this->CreateInputText($id,'uploads_dir',$this->GetPreference('uploads_dir'),30,60)
.'<br />'.$this->Lang('help_updir'));

$smarty->assign('title_password',$this->Lang('title_password'));
$pw = $this->GetPreference('masterpass');
if($pw)
	$pw = stripe_utils::unfusc($pw);

$smarty->assign('input_password',
	$this->CreateTextArea(false,$id,$pw,'masterpass','cloaked',
		$id.'passwd','','',40,2));

$jsincs[] = '<script type="text/javascript" src="'.$baseurl.'/include/jquery-inputCloak.min.js"></script>';
$jsloads[] = <<<EOS
 $('#{$id}passwd').inputCloak({
  type:'see4',
  symbol:'\u2022'
 });

EOS;

if($padm)
{
	$smarty->assign('submit',$this->CreateInputSubmit($id,'submit',$this->Lang('submit')));
	$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));
}

if($jsloads)
{
	$jsfuncs[] = '$(document).ready(function() {
';
	$jsfuncs = array_merge($jsfuncs,$jsloads);
	$jsfuncs[] = '});
';
}
$smarty->assign('jsfuncs',$jsfuncs);
$smarty->assign('jsincs',$jsincs);

echo $this->ProcessTemplate('adminpanel.tpl');

?>
