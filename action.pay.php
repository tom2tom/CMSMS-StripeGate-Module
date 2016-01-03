<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------

if(1)
{
echo 'This action is not working yet';
return;
}

if(empty($params['amount']))
{
	echo $this->Lang('err_parameter');
	return;
}

if(empty($params['account']))
{
	$default = stripe_utils::GetAccount();
	if($default)
		$params['account'] = $default;
	else
	{
		echo $this->Lang('err_parameter');
		return;
	}
}
$pref = cms_db_prefix();
if(is_numeric($params['account']))
{
	$row = $db->GetRow('SELECT
account_id,
currency,
amountformat,
stylesfile,
iconfile
FROM '.$pref.'module_sgt_account WHERE account_id=? AND isactive=TRUE',array($params['account']));
}
else
{
	$row = $db->GetRow('SELECT
account_id,
currency,
amountformat,
stylesfile,
iconfile
FROM '.$pref.'module_sgt_account WHERE alias=? AND isactive=TRUE',array($params['account']));
}
if(!$row)
{
	echo $this->Lang('err_parameter');
	return;
}

//$privkey = $row['X'];
$privkey = 'sk_test_0e4iX1UFbH1vE2frvGaVGjd1';
$pubkey = 'pk_test_MTS0pDHowpa6NI2ZNHkR1AqV';

echo '<button id="customButton">Purchase</button>';

//image url in module uploads dir
//square image of your brand or product
//recommended minimum size is 128x128px, recommended image types are .gif, .jpeg, or .png.

$jsincs[] = <<<EOS
<script src="https://checkout.stripe.com/v1/checkout.js"></script> //or v2
EOS;

$jsloads[] = <<<EOS
 var handler = StripeCheckout.configure({
  key: 'pk_test_MTS0pDHowpa6NI2ZNHkR1AqV', //private key from row data
  image: 'https://s3.amazonaws.com/stripe-uploads/acct_1703eVGajAPEsyVFmerchant-icon-1449263031507-path3016.png',
  locale: 'auto', //?
  token: function(token) {
/* token = object:
{
  "id": "tok_17Q5EAGajAPEsyVFahrxVJnZ",
  "object": "token",
  "bank_account": {
  STUFF
  },
  "card": {
  STUFF
  },
  "client_ip": whatever,
  "created": 1452040358, (<< i.e. stamp)
  "livemode": false,
  "type": "card",
  "used": false
}
   // Use the token to create the charge with a server-side script.
   // You can access the token ID with `token.id`
*/
  }
 });

 $('#customButton').on('click', function(e) {
  // open Checkout with further options
   handler.open({
    name: 'Eaglemont Tennis Club', //func(row data)
    description: '2 widgets', //ditto
    currency: 'aud', //ditto
    amount: 2000 //func \$params['amount'] & row data
  });
  e.preventDefault();
 });
 // close Checkout on page-change
 $(window).on('popstate', function() {
   handler.close();
 });

EOS;


/*
require(dir_name(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'init.php');
Stripe::setApiKey($privkey);

echo <<<EOS
<form action="charge.php" method="post">
 <script src="https://checkout.stripe.com/v2/checkout.js"
  class="stripe-button"
  data-key="{$pubkey}"
  data-amount="5000"
  data-description="One year's subscription">
 </script>
</form>
EOS;

exit;

/*
The above displays a 'pay with card' button
clicking the button pops a modal dialog with various inputs, then back to charge.php:

<?php
require_once('./config.php');
$token  = $_POST['stripeToken'];
$customer = \Stripe\Customer::create(array(
  'email' => 'customer@example.com',
  'card'  => $token
));
$charge = \Stripe\Charge::create(array(
  'customer' => $customer->id,
  'amount'   => 5000,
  'currency' => 'usd'
));
echo '<h1>Successfully charged $50!</h1>';
?>
*/

//TODO SETUP label, styling
//echo $this->ProcessTemplate('button.tpl');

?>
