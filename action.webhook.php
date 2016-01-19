<?php
#----------------------------------------------------------------------
# This file is part of CMS Made Simple module: StripeGate
# Copyright (C) 2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file StripeGate.module.php
# More info at http://dev.cmsmadesimple.org/projects/stripegate
#----------------------------------------------------------------------
# This action handles webhook-reports from upstream Stripe

//TODO process & record stuff

//clear all page content echoed before now
$handlers = ob_list_handlers();
if($handlers)
{
	$l = count($handlers);
	for ($c = 0; $c < $l; $c++)
		ob_end_clean();
}

exit('NOT YET SUPPORTED');

?>
