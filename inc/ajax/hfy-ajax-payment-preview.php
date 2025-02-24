<?php

// update payment preview (block with prices)

if (!defined('WPINC')) die;

require_once HOSTIFYBOOKING_DIR . 'inc/lib.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/ListingHelper.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/UrlHelper.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/HfyHelper.php';

$prm = (object) (!empty($_POST['data']) ? $_POST['data'] : []);

$prm->listing_id = $prm->listing_id ?? 0;
$prm->start_date = $prm->start_date ?? null;
$prm->end_date = $prm->end_date ?? null;
$prm->guests = $prm->guests ?? 1;
$prm->adults = $prm->adults ?? 1;
$prm->children = $prm->children ?? 0;
$prm->infants = $prm->infants ?? 0;
$prm->pets = $prm->pets ?? 0;
$prm->discount_code = $prm->discount_code ?? null;
$prm->extrasOptional = $prm->extrasOptional ?? null;

$listing_id = intval(empty($id) ? $prm->listing_id : $id);

if (empty($listing_id)) {
	throw new Exception(__('No listing ID', 'hostifybooking'));
}

$guests = $prm->guests;
$adults = $prm->adults;
$children = $prm->children;
$infants = $prm->infants;
$pets = $prm->pets;

$startDate = isset($start_date) ? $start_date : ($prm->start_date ? $prm->start_date : null);
$endDate = isset($end_date) ? $end_date : ($prm->end_date ? $prm->end_date : null);

$id = $listing_id;
$payment_flag = true;

include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-listing.php';
include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-settings.php';
include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-payment-settings.php';

$listingData = $listing->listing;
$listingDescription = $listing->description;

if (isset($settings->direct_inquiry_email) && $settings->direct_inquiry_email) {
	include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-terms.php';
}

$listingPrice = null;

$extrasSet = (string) trim($prm->extrasSet ?? '');
$extrasSetArray = empty($extrasSet) ? [] : explode(',', $extrasSet);

$extrasAll = [];

if (isset($listing->extras)) {
	$extrasAll = $listing->extras;
} else {
	$getExtrasResult = $api->getExtras($id);
	$getExtrasError = isset($getExtrasResult->error) ? $getExtrasResult->error : null;
	if (!$getExtrasError) {
		if ($getExtrasResult && $getExtrasResult->success) {
			$extrasAll = $getExtrasResult->extras->items ?? null;
		}
	}
}

$extrasOptional = (string) trim($prm->extrasOptional ?? ''); // "id:0|1,..."
$extrasOptionalSelected = [];
$extrasOptionalSelectedIds = [];

if (HFY_SHOW_PAYMENT_EXTRAS) {
	foreach ($extrasAll as $item) {
		$extrasOptionalSelected[$item->fee_id] = false;
	}
}

$extrasOptionalArray_ = explode(',', $extrasOptional);
foreach ($extrasOptionalArray_ as $eItem) {
	$x = explode(':', $eItem);
	$item_id = (int) ($x[0] ?? 0);
	if ($item_id > 0) {
		$item_is_selected = ($x[1] ?? 0) == 1;
		$extrasOptionalSelected[$item_id] = $item_is_selected;
		if ($item_is_selected) {
			$extrasOptionalSelectedIds[] = $item_id;
		}
	}
}

$feesToSend = array_unique(array_merge($extrasSetArray, $extrasOptionalSelectedIds));

$api = new HfyApi();
$pres = $api->getListingPrice($listing_id, $startDate, $endDate, $guests, false, $prm->discount_code, $adults, $children, $infants, $pets, $feesToSend);

if ($pres && $pres->success) {
	$listingPrice = $pres->price;
} else {
	throw new Exception(isset($pres->error) ? $pres->error : __('Listing price is not available', 'hostifybooking'));
}

if (!(
	$listing
	&& $listing->success
	&& is_object($listingPrice)
	&& $paymentSettings
	&& $paymentSettings->success
)) {
	throw new Exception(isset($listing->error) ? $listing->error : __('Incorrect parameters', 'hostifybooking'));
	// return $this->redirect(['listing', 'id' => $listing_id]);
}

$accountingActive = ($listingPrice->accounting_module ?? 0) == 1;

$selectedPaymentService = $paymentSettings->services->service ?? '';

if ($selectedPaymentService != 'netpay') {
	include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-payment-token.php';
}

$price = $listingPrice->priceWithoutDiscount;
$listingPricePerNight = number_format($price / ($listingPrice->nights <> 0 ? $listingPrice->nights : 1), 2, '.', '');
$tax = isset($listingPrice->tax_amount) ? $listingPrice->tax_amount : 0;
$total = $listingPrice->totalAfterTax ?? $listingPrice->totalPrice ?? $listingPrice->total;
$monthlyDiscount = $listingPrice->monthlyPriceDiscount;
$monthlyDiscountPercent = $listingPrice->monthlyPriceDiscountPercent;
$weeklyDiscount = $listingPrice->weeklyPriceDiscount;
$weeklyDiscountPercent = $listingPrice->weeklyPriceDiscountPercent;

$totalPartial = !empty($pres->price->totalPartial) ? $pres->price->totalPartial : 0;
// $totalPartial = 0;
// if (!empty($settings->data->payment_percent)) {
// 	if ($settings->data->payment_percent > 0 && $settings->data->payment_percent < 100) {
// 		$totalPartial = $total * $settings->data->payment_percent / 100;
// 	}
// } else {
// 	if (!empty($listingPrice->offline)) {
// 		$totalPartial = $total - ($listingPrice->totalOfflineCalc ?? 0);
// 	}
// }

$_extrasSet = [];
$_extrasOptional = [];

foreach ($extrasAll as $e) {

	$e->total = $e->amount ?? 0;
	if ($e->fee_charge_type_id == 2) { // per night
		$e->total = $listingPrice->nights * $e->total;
	} else if ($e->fee_charge_type_id == 4) { // per guest
		$e->total = $guests * $e->total;
	}

	$e->fee_name = $e->name ?? $e->fee_name ?? '';
	if (in_array($e->fee_id, $extrasSetArray)) {
		$_extrasSet[] = $e;
	}
	if (isset($extrasOptionalSelected[$e->fee_id])) {
		$_extrasOptional[] = $e;
	}
}

$isExtrasOptional = !empty($_extrasOptional);
$sliderStepsCount = $isExtrasOptional ? 3 : 2;

$totalPrice = number_format($totalPartial > 0 ? $totalPartial : $total, 2, '.', '');

$nights = $listingPrice->nights;

$listingDescription = $listing->description;
$listingTitle = empty($listingDescription->name) ? $listing->listing->name : $listingDescription->name;
$listing->listing->name = $listingTitle;

$listingInfo = (object) [
	'id' => $listing->listing->id,
	'thumbnail_file' => $listing->listing->thumbnail_file,
	'name' => $listing->listing->name,
	'city' => $listing->listing->city,
	'country' => $listing->listing->country,
	'currency_symbol' => $listing->currency_data->symbol,
	'nights' => $listingPrice->nights,
	'cleaning_fee' => $listingPrice->cleaning_fee,
	'extra_person_price' => $listingPrice->extra_person_price,
	'tax' => $tax,
	'security_deposit' => $listingPrice->security_deposit,
];

$reserveInfo = (object) [
	'monthlyDiscount' => $monthlyDiscount,
	'monthlyDiscountPercent' => $monthlyDiscountPercent,
	'weeklyDiscount' => $weeklyDiscount,
	'weeklyDiscountPercent' => $weeklyDiscountPercent,
	'start_date' => $startDate,
	'end_date' => $endDate,
	'guests' => $guests,
	'adults' => $prm->adults,
	'children' => $prm->children,
	'infants' => $prm->infants,
	'pets' => $prm->pets,
	'listing_id' => $listing_id,
	'name' => $prm->pname,
	'email' => $prm->pemail,
	'phone' => $prm->pphone,
	'note' => $prm->note,
	'zip' => $prm->zip,
	'discount_code' => $prm->discount_code,
	'dcid' => $listingPrice->discount->id ?? null,
	'prices' => $listingPrice,
	'extrasSet' => $_extrasSet,
	'extrasOptional' => $_extrasOptional,
	'fees_ids' => implode(',', $feesToSend),
];

$currency =
	$reserveInfo->prices->price->iso_code
	?? $reserveInfo->prices->iso_code
	?? $listing->listing->currency
	?? 'USD';

$startDateFormatted = hfyDateFormatOpt($startDate);
$endDateFormatted = hfyDateFormatOpt($endDate);
$detailedAccomodation = false;
if (is_object($reserveInfo->prices)) {
	if (!empty($reserveInfo->prices->feesAccommodation)) {
		foreach ($reserveInfo->prices->feesAccommodation as $fee) {
			if (strtolower($fee->fee_charge_type) == 'per month') {
				$detailedAccomodation = true;
			}
		}
	}
}

$redirectOnSuccess = null;

ob_start();

include hfy_tpl('payment/preview');

$out = ob_get_contents();
ob_end_clean();
