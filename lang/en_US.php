<?php
$lang['friendlyname'] = 'Stripe Gateway';
$lang['confirm_uninstall']='You\'re sure you want to uninstall the '.$lang['friendlyname'].' module?';
$lang['module_description'] = 'A module to facilitate communications between Stripe and a CMS Made Simple-powered website';
$lang['postinstall'] = $lang['friendlyname'].' module successfully installed, now please ensure that it is configured properly for use, and apply related permissions';
$lang['postuninstall'] = $lang['friendlyname']. ' module successfully removed';

$lang['missing_type'] = 'You must provide %s';
$lang['aamount'] = 'an amount';
$lang['aname'] = 'a name';
$lang['acvc'] = 'a security code';
$lang['amonth'] = 'a month';
$lang['aname'] = 'a suitable identifier';
$lang['anum'] = 'a card number';
$lang['apurpose'] = 'a desciption of the payment';
$lang['ayear'] = 'a year';

$lang['additem'] = 'Add new account';
$lang['any'] = 'Any';
$lang['all'] = 'All';
$lang['cancel'] = 'Cancel';
$lang['cardcvc'] = 'Security code';
$lang['cardexpiry'] = 'Expiry';
$lang['cardnumber'] = 'Card number';
$lang['close'] = 'Close';
$lang['currency_example'] = 'e.g. %s';
$lang['delete'] = 'Delete';
$lang['delitm_confirm'] = 'You\\\'re sure you want to delete \\\'%s\\\'?'; //double-escaped for js use
$lang['delsel_confirm'] = 'You\\\'re sure you want to delete selected account(s)?'; //double-escaped for js use
$lang['delsel_confirm2'] = 'You\\\'re sure you want to delete selected record(s)?'; //double-escaped for js use

$lang['err__contact'] = 'Please notify the system administrator.';
$lang['err_badmonth'] = 'Invalid month - enter number 1 to 12';
$lang['err_badnum'] = 'That doesn\\\'t look like a card-number'; //double-escaped for use in js
$lang['err_badyear'] = 'Invalid year - enter number %d or greater';
$lang['err_export'] = 'Export failed';
$lang['err_file'] = 'Invalid file';
$lang['err_noamount'] = sprintf($lang['missing_type'],$lang['aamount']);
$lang['err_nocvc'] = sprintf($lang['missing_type'],$lang['acvc']);
$lang['err_nomonth'] = sprintf($lang['missing_type'],$lang['amonth']);
$lang['err_noname'] = sprintf($lang['missing_type'],$lang['aname']);
$lang['err_nonum'] = sprintf($lang['missing_type'],$lang['anum']);
$lang['err_nopurpose'] = sprintf($lang['missing_type'],$lang['apurpose']);
$lang['err_nowho'] = sprintf($lang['missing_type'],$lang['aname']);
$lang['err_noyear'] = sprintf($lang['missing_type'],$lang['ayear']);
$lang['err_parameter'] = 'Parameter error.<br />'.$lang['err__contact'];
$lang['err_permission'] = 'File system authority lacking';
$lang['err_system'] = 'System error.<br />'.$lang['err__contact'];
$lang['err_toosmall'] = 'Minimum charge is %s';
$lang['err_upload'] = 'Upload failed';

$lang['export'] = 'Export';
$lang['first'] = 'first';

$lang['help_alias'] = 'For identifying the account in page-tags. If none is supplied, one will be derived from the account name.';
$lang['help_amountformat'] = 'Examples: S.00 S.0 S S,0 0S00<br />
The local currency symbol will be substituted for \'S\',
a separator (\'.\' or otherwise) indicating the start of part-units is optional,
the number of trailing 0\'s dictates the number of decimal-places expressed in the part-unit';
$lang['help_cssupload'] = '<h3>File Format Information</h3>
<p>The file must be in ASCII stylesheet format. For example, the following represents the default settings:
<pre>%s</pre>
<h3>Problems</h3>
<p>The upload process will fail if:<ul>
<li>the file does not look like a relevant stylesheet</li>
<li>the file-size is bigger than about 2 kB</li>
<li>filesystem permissions are insufficient</li>
<li>no uploads directory is set</li>
</ul></p>';
$lang['help_iconfile'] = 'Icon representing account-holder brand, or purchased product. Module help includes details of file content and location';
$lang['help_iconupload'] = '<h3>File Format Information</h3>
<p>Recommended image types are .gif, .jpeg, or .png. Recommended format is square, at least 128X128px.
It will be cropped to a circle for presentation.
<h3>Problems</h3>
<p>The upload process will fail if:<ul>
<li>the file is not an image</li>
<li>filesystem permissions are insufficient</li>
<li>no uploads directory is set</li>
</ul></p>';
$lang['help_minpay'] = 'If 0 or empty, no minimum applies, duh. Otherwise, the amount should coform with the format above (except no currency symbol).';
$lang['help_owner'] = 'If one is chosen, only that user will be able to access account data and settings';
$lang['help_owner'] = 'Registered site user authorised for account maintenance';
$lang['help_stylesfile'] = 'If none is supplied, default styles will be used. Otherwise, module help includes details of file content and location';
$lang['help_surchargerate'] = 'If 0 or empty, no surcharge applies, duh. Otherwise, enter a decimal value, or a percentage value followed by \'%\'';
$lang['help_title'] = 'If none is supplied, a title will be derived from the account name at runtime';
$lang['help_updir'] = 'Filesystem path relative to website-host uploads directory. No leading or trailing path-separator, and any intermediate path-separator must be host-system-specific e.g. \'\\\' on Windows. If left blank, the default will be used. Directory could contain .css files for specific checkouts, among others.';

$lang['last'] = 'last';
$lang['missing'] = '&lt;Missing&gt;';
$lang['month_template'] = 'MM';
$lang['name'] = 'Name';
$lang['next'] = 'next';
$lang['no'] = 'No';
$lang['nodata'] = 'No account is registered';
$lang['none'] = 'None';
$lang['noowner'] = 'No owner';

$lang['param_account'] = 'Override the default account, use this (id-number or alias) instead';
$lang['param_action'] = 'Type of interaction with Stripe. At this time, \'pay\' and \'payplus\' are supported';
$lang['param_formed'] = 'Whether the output is to be displayed inside another form';
$lang['param_nosur'] = 'Override the default payplus surcharge rate, use 0 instead';
$lang['param_title'] = 'Override the default payplus \'form\' title, use this instead';

$lang['pageof'] = 'showing page %s of %s';
$lang['pagerows'] = 'rows-per-page';
$lang['payamount'] = 'Amount to pay';
$lang['payfor'] = 'Payment on behalf of';
$lang['payment_submitted'] = 'The payment has been submitted for processing';
$lang['paywhat'] = 'Reason for payment';
$lang['percent'] = 'percent';
$lang['perm_adm'] = 'Modify Stripe Gateway Settings';
$lang['perm_mod'] = 'Modify Stripe Accounts';
$lang['perm_use'] = 'Use Stripe Accounts';
$lang['previous'] = 'previous';
$lang['submit'] = 'Submit';
$lang['surcharge'] = 'A small surcharge (%s) will be applied, to help cover the transaction costs.';

$lang['tip_admin'] = 'administer account records';
$lang['tip_delete'] = 'delete account';
$lang['tip_deletesel'] = 'delete selected accounts';
$lang['tip_deletesel2'] = 'delete selected records';
$lang['tip_edit'] = 'edit account data';
$lang['tip_export'] = 'export account records';
$lang['tip_exportsel'] = 'export selected records';
$lang['tip_exportsel2'] = 'export records for selected accounts';
$lang['tip_toggle'] = 'toggle value';
$lang['tip_upload'] = 'upload selected file to website host';
$lang['tip_view'] = 'inspect account details';

$lang['title_account'] = 'Account records: %s';
$lang['title_active'] = 'Active';
$lang['title_alias'] = 'Alias';
$lang['title_alias2'] = 'Account alias';
$lang['title_amount'] = 'Amount';
$lang['title_amountformat'] = 'Format for displaying monetary values';
$lang['title_checkout'] = 'Payment to %s';
$lang['title_cssfile'] = 'Upload CSS file for \'%s\' checkout form';
$lang['title_currency'] = 'Currency to be used';
$lang['title_default'] = 'Default';
$lang['title_defaultlong'] = 'Default account';
$lang['title_iconfile'] = 'Checkout-form icon';
$lang['title_iconfile2'] = 'Upload brand/product image file for \'%s\' checkout form';
$lang['title_mainpage'] = 'Module main page';
$lang['title_maintab'] = 'Accounts';
$lang['title_minpay'] = 'Minimum acceptable payment amount';
$lang['title_name'] = 'Account name';
$lang['title_owner'] = 'Account manager';
$lang['title_password'] = 'Password for securing sensitive data';
$lang['title_privtoken'] = 'Live-mode secret key';
$lang['title_pubtoken'] = 'Live-mode public key';
$lang['title_settingstab'] = 'Settings';
$lang['title_stylesfile'] = 'CSS file with custom styling';
$lang['title_surchargerate'] = 'Surcharge rate';
$lang['title_tag'] = 'Page tag';
$lang['title_testprivtoken'] = 'Test-mode secret key';
$lang['title_testpubtoken'] = 'Test-mode public key';
$lang['title_title'] = 'Checkout-form title';
$lang['title_token'] = 'Identifier';
$lang['title_updir'] = 'Sub-directory for module-specific file uploads';
$lang['title_what'] = 'Description';
$lang['title_when'] = 'Submitted';
$lang['title_who'] = 'For';
$lang['title_usetest'] = 'Use test keys';

$lang['updated'] = 'Settings updated';
$lang['year_template'] = 'YYYY';
$lang['yes'] = 'Yes';
$lang['upload']='Upload';
//$lang['help_import'] = <<<EOS EOS;
//$lang['import'] = 'Import';
//$lang['reporting_url'] = 'URL to which Stripe can send webhook reports';

$lang['help_module'] = <<<EOS
<h3>What does it do?</h3>
This module provides an interface for some simple types of 'checkout' which involve
making an online payment to somebody, using <a href="https://stripe.com">Stripe</a> as the intermediary.
<h3>How do I use it?</h3>
Change the default pass-phrase.<br /><br />
Apply module permissions, which are
<ul>
<li>Use Stripe Accounts</li>
<li>Modify Stripe Accounts</li>
<li>Modify Stripe Gateway Settings</li>
</ul>
At least, create a user-group with the second of these permissions,
add to that group all users permitted to manage the Stripe account(s) recorded in the module.<br /><br />
Set up one or more accounts, by activating the Stripe item in the admin 'extensions' menu. If more than one, make one of them the default.<br /><br />
Put into a relevant page's content block, or into a 'form-builder' field:
<pre>
{StripeGate}
</pre>
or
<pre>
{StripeGate account='account-alias'}
</pre>
Adjust the page theme to include the default, or some other relevant, css file, or if instance-specific styling is to be supported,
put into the page's 'Page Specific Metadata' field (so it can be modified at runtime):
<pre>
&lt;link rel="stylesheet" type="text/css" id="stripestyles" href="{the-correct-site-root-url}/modules/StripeGate/css/checkout.css" media="all" /&gt;
</pre>
TODO finish and document the 'pay' action - displays just a button, pre-determined payment amount, no metadata
<pre>
{StripeGate action='pay' amount=19.99}
</pre>
A low-level API is available, enabling things like:<br /><br />
<code>Stripe::setApiKey('d8e8fca2dc0f896fd7cb4cb0031ba249');<br />
\$myCard = array('number' => '4242424242424242', 'exp_month' => 8, 'exp_year' => 2018);<br />
\$result = Stripe_Charge::create(array('card' => \$myCard, 'amount' => 2000, 'currency' => 'usd'));<br />
do_something_with(\$result);</code><br /><br />
<a href="https://stripe.com/docs/api">Visit Stripe</a> for up-to-date documentation.
Note that this module uses Stripe's older, non-namespaced, interface library (to support some earlier PHP's).
<h3>Requirements:</h3>
<ul>
<li>CMS Made Simple 1.9+</li>
<li>PHP 5.2+</li>
<li>PHP extensions
<ul>
<li>cURL</li>
<li>json</li>
<li>mbstring</li>
</ul>
</li>
</ul>
<h3>Support</h3>
<p>This module is provided as-is. Please read the text of the license for the full disclaimer.</p>
<p>For help:</p>
<ul>
<li>discussion may be found in the <a href="http://forum.cmsmadesimple.org">CMS Made Simple Forums</a>; or</li>
<li>you may have some success emailing the author directly.</li>
</ul>
<p>For the latest version of the module, or to report a bug, visit the module's <a href="http://dev.cmsmadesimple.org/projects/stripegate">forge-page</a>.</p>
<h3>Copyright and license</h3>
<p>Copyright &copy; 2016 Tom Phane &lt;tpgww@onepost.net&gt;. All rights reserved.</p>
<p>This module is free software. It may be redistributed and/or modified
under the terms of the GNU Affero General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.</p>
<p>This module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
<a href="http://www.gnu.org/licenses/licenses.html#AGPL">GNU Affero General Public License</a> for more details.</p>
EOS;

?>
