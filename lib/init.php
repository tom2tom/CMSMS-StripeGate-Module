<?php

// Tested on PHP 5.2, 5.3

// This snippet (and some of the curl code) due to the Facebook SDK.
if (!function_exists('curl_init')) {
  throw new Exception('Stripe needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Stripe needs the JSON PHP extension.');
}
if (!function_exists('mb_detect_encoding')) {
  throw new Exception('Stripe needs the Multibyte String PHP extension.');
}

$base = dirname(__FILE__).DIRECTORY_SEPARATOR.'Stripe'.DIRECTORY_SEPARATOR;

// Stripe singleton
require($base.'Stripe.php');

// Utilities
require($base.'Util.php');
require($base.'Util/Set.php');

// Errors
require($base.'Error.php');
require($base.'ApiError.php');
require($base.'ApiConnectionError.php');
require($base.'AuthenticationError.php');
require($base.'CardError.php');
require($base.'InvalidRequestError.php');
require($base.'RateLimitError.php');

// Plumbing
require($base.'Object.php');
require($base.'ApiRequestor.php');
require($base.'ApiResource.php');
require($base.'SingletonApiResource.php');
require($base.'AttachedObject.php');
require($base.'List.php');
require($base.'RequestOptions.php');

// Stripe API Resources
require($base.'Account.php');
require($base.'Card.php');
require($base.'Balance.php');
require($base.'BalanceTransaction.php');
require($base.'Charge.php');
require($base.'Customer.php');
require($base.'FileUpload.php');
require($base.'Invoice.php');
require($base.'InvoiceItem.php');
require($base.'Plan.php');
require($base.'Subscription.php');
require($base.'Token.php');
require($base.'Coupon.php');
require($base.'Event.php');
require($base.'Transfer.php');
require($base.'Recipient.php');
require($base.'Refund.php');
require($base.'ApplicationFee.php');
require($base.'ApplicationFeeRefund.php');
require($base.'BitcoinReceiver.php');
require($base.'BitcoinTransaction.php');

?>
