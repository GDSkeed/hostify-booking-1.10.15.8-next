<?php

if (!defined('WPINC')) die;

require_once HOSTIFYBOOKING_DIR . 'inc/lib.php';

$prm = hfy_get_vars_def();

$id = $prm->id && empty($id) ? $prm->id : $id;

$guests = $prm->guests;
$adults = $prm->adults;
$children = $prm->children;
$infants = $prm->infants;

if (empty($id)) {
	throw new Exception(__('No listing ID', 'hostifybooking'));
}

include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-listing.php';
include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/make-listing-tpl-vars.php';

include hfy_tpl('listing/listing-info-prices');
