<?php

if ( ! defined( 'WPINC' ) ) die;

require_once HOSTIFYBOOKING_DIR . 'inc/lib.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/ListingHelper.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/UrlHelper.php';

$prm = hfy_get_vars_([
	'start_date',
	'end_date',
	'guests',
	'bedrooms',
	'city_id',
	'long_term_mode',
	// advanced
	'pmin',
	'pmax',
]);

$prm->prop = hfy_get_('prop', []);
$prm->am = hfy_get_('am', []);

$longTermMode = hfy_ltm_fix_(!empty($monthly) ? $monthly : ($prm->long_term_mode ?? HFY_LONG_TERM_DEFAULT));

$city_id = $prm->city_id;
$start_date = $prm->start_date;
$end_date = $prm->end_date;
$guests = $prm->guests < 1 || !is_numeric($prm->guests) ? 1 : $prm->guests;
$bedrooms = $prm->bedrooms;

$api = new HfyApi();

$propTypes = $api->getAvailablePropertyTypes();
include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/dict-amenities.php';
include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/dict-properties.php';
include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-settings.php';

// $neighbourhoods = $api->getNeighbourhoods();

include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-data.php';

$showIcons = false;
$noBedroomFilter = false;
$noLocationFilter = false;

if (!empty($bookingEngine)) {
	$noBedroomFilter = empty($bookingEngine->bedroom_filter ?? null);
	$noLocationFilter = empty($bookingEngine->location_filter ?? null);
}

// $start_date = hfyDateFormatOpt($start_date);
// $end_date = hfyDateFormatOpt($end_date);

include hfy_tpl('element/booking-search-popup');
