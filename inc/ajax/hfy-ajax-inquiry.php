<?php

if ( ! defined( 'WPINC' ) ) die;

require_once HOSTIFYBOOKING_DIR . 'inc/lib.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/ListingHelper.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/UrlHelper.php';

include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-settings.php';
include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-terms.php'; // todo

$direct_inquiry_email = $settings->direct_inquiry_email;

parse_str($_POST['data'], $pdata);

$listingId = intval($pdata['listingId'] ?? 0);
$adults = intval(is_numeric($pdata['adults']) ? $pdata['adults'] : 1);
$children = intval(is_numeric($pdata['children']) ? $pdata['children'] : 0);
$infants = intval(is_numeric($pdata['infants']) ? $pdata['infants'] : 0);
$pets = intval(is_numeric($pdata['pets']) ? $pdata['pets'] : 0);

$guests = $adults + $children;

$check_in = trim($pdata['check_in'] ?? '');
$check_out = trim($pdata['check_out'] ?? '');
$discount_code = trim($pdata['discount_code'] ?? '');
$listingNickname = trim($pdata['listingNickname'] ?? '');
$listingName = trim($pdata['listingName'] ?? '');

$first_name = trim($pdata['first_name'] ?? '');
$last_name = trim($pdata['last_name'] ?? '');
$email = trim($pdata['email'] ?? '');
$phone = trim($pdata['phone'] ?? '');
$message = trim($pdata['message'] ?? '');

$out = [
	'success' => false,
	'msg' => 'Incorrect data'
];

if ($direct_inquiry_email && $listingId && $check_in && $check_out) {

	$api = new HfyApi();
	$result = $api->getListingPrice($listingId, $check_in, $check_out, $adults, false, $discount_code, $adults, $children, $infants, $pets);
	if (!$result || isset($result->error)) {
		$out = ['success' => false, 'msg' => $result->error];
	} else {

		if ($result->price->totalAfterTax) {
			$totalPrice = $result->price->totalAfterTax;
		} else {
			$priceMarkup = !empty($settings->price_markup) ? $settings->price_markup : 0;
			$result->price->price = ListingHelper::calcPriceMarkup($result->price->price, $priceMarkup);
			$totalPrice = $result->price->price + $result->price->cleaning_fee + $result->price->extra_person_price;
		}

		if ($discount_code) {
			$message .= "\n\nDISCOUNT CODE: $discount_code";
		}

		$result = $api->postBookListing(
			$listingId,
			$check_in,
			$check_out,
			$guests,
			$totalPrice,
			$first_name . ' ' . $last_name,
			$email,
			$phone,
			$message,
			HfyApi::RESERVATION_STATUS_PENDING,
			$discount_code,
			isset($result->price->discount->id) ? $result->price->discount->id : 0,
			$adults,
			$children,
			$infants,
			$pets
		);

		if (!$result) {
			$out = ['success' => false, 'msg' => 'Unable to book listing'];
		} else {
			ob_start();
			include hfy_tpl('element/direct-inquiry-mail');
			$msg = ob_get_contents();
			ob_end_clean();
			$out = ['success' => true];
		}
	}
}
