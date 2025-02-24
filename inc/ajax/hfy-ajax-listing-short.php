<?php

if ( ! defined( 'WPINC' ) ) die;

require_once HOSTIFYBOOKING_DIR . 'inc/lib.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/ListingHelper.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/UrlHelper.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/HfyHelper.php';

$id = (int) $_POST['id'];

$out = [
	'success' => false,
	'info' => null,
];

if ($id <= 0) return;

$prm = hfy_get_vars_def();

try {
	include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-listing.php';
	// include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-settings.php';
	include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/make-listing-tpl-vars.php';
} catch (\Exception $e) {
	return;
}

$listingUrl = ['id' => $id];
if (isset($issetDates) && $issetDates && isset($guests)) {
    $listingUrl = array_merge($listingUrl, [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'guests' => $guests
    ]);
}

$out['success'] = true;
$out['info'] = [
    'title' => $listingTitle,
    'text' => '',
	'url' => UrlHelper::listing($listingUrl),
    'img' => $listingData->thumbnail_file,
	'price' => ListingHelper::withSymbol($listing->price, $listingCurrencyData),
];
