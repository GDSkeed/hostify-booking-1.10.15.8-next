<?php

if ( ! defined( 'WPINC' ) ) die;

include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/dict-amenities.php';

$listingData = $listing->listing;
$listingData->is_listed = true; // deprecated, fallback

$listingDescription = $listing->description;

$listingTitle = empty($listingDescription->name) ? $listingData->name : $listingDescription->name;
$listingData->name = $listingTitle;

$listingDetails = $listing->details;
$listingAmenities = $listing->amenities;
$listingCurrencyData = $listing->currency_data;

$listingCurrencySymbol = isset($listingCurrencyData->symbol) ? $listingCurrencyData->symbol : $listingData->currency;
$listingPrice = $listing->price;
$priceTitle = '';

$listingPriceOnRequest = ($listingData->price_on_request ?? 0) == 1;

$minPrices = $listing->min_prices ?? null;
// $minPrice = $minPrices->min_price ?? null;
$minPrice = null;
if (is_object($listingPrice)) {
    $minPrice = $listingPrice->price && $listingPrice->nights
        ? intval($listingPrice->price / $listingPrice->nights)
        : $minPrices->min_price ?? null;
}

$minPriceMonthly = $minPrices->min_price_monthly ?? null;

if (($listingData->min_nights ?? 1) >= 28 && $minPriceMonthly > 0) {
    $showPrice = $minPriceMonthly;
    $showPricePer = __('month', 'hostifybooking');
} else {
    $showPrice = $minPrice;
    $showPricePer = __('night', 'hostifybooking');
}

if ($listingPriceOnRequest) {
    $priceTitle = __('Price on request', 'hostifybooking');
} else {
    if (!empty($listingPrice) && is_object($listingPrice)) {
        $price = isset($listingPrice->priceWithMarkup) ? $listingPrice->priceWithMarkup : $listingPrice->totalAfterTax;
        $priceFormatted = ListingHelper::formatPrice($price, $listingPrice);
        $priceTitle = sprintf('price <span class="h3">%s</span> for %s %s',
            $priceFormatted,
            $listingPrice->nights,
            ($listingPrice->nights > 1 ? 'nights' : 'night')
        );
    }
}

$calendarDisabledDates = [];

foreach (($listing->calendar ?? []) as $val) {
    $calendarDisabledDates[] = [
        'start' => $val->start,
        'end' => $val->end ?? $val->date_end,
    ];
}

// var_dump($listing->calendar);die;
// var_dump($calendarDisabledDates);die;

// $calendarDisabledDates2 = [];
// foreach ($calendarDisabledDates as $val) {
//     $calendarDisabledDates2[] = [
//         'start' => date('Y-m-d', strtotime($val->start . ' +1 day')),
//         'end' => $val->end,
//     ];
// }

$calendarCustomStay = $listing->custom_stay ?? [];

$calendarCustomMinStay = $listingMinStay ?? [];
$calendarCustomMinStay = array_filter($calendarCustomMinStay, function($x) { return !is_null($x); }); // filter nulls

$listingReviews = $listing->reviews;
if (is_array($listingReviews) && !empty($listingReviews[0]) && is_object($listingReviews[0])) {
    usort($listingReviews, function($a, $b){
        return $a->created < $b->created;
    });
}

$reviewsCount = count($listingReviews);

$rating = isset($listing->rating->rating) ? ListingHelper::getReviewRating($listing->rating->rating) : 0;

$reviewsRating = ListingHelper::getReviewStarRating($rating);

$accuracyRating = isset($listing->rating->accuracy_rating) ? ListingHelper::getReviewStarRating(ListingHelper::getReviewRating($listing->rating->accuracy_rating)) : 0;

$checkinRating = isset($listing->rating->checkin_rating) ? ListingHelper::getReviewStarRating(ListingHelper::getReviewRating($listing->rating->checkin_rating)) : 0;

$cleanRating = isset($listing->rating->clean_rating) ? ListingHelper::getReviewStarRating(ListingHelper::getReviewRating($listing->rating->clean_rating)) : 0;

$communicationRating = isset($listing->rating->communication_rating) ? ListingHelper::getReviewStarRating(ListingHelper::getReviewRating($listing->rating->communication_rating)) : 0;

$locationRating = isset($listing->rating->location_rating) ? ListingHelper::getReviewStarRating(ListingHelper::getReviewRating($listing->rating->location_rating)) : 0;

$valueRating = isset($listing->rating->value_rating) ? ListingHelper::getReviewStarRating(ListingHelper::getReviewRating($listing->rating->value_rating)) : 0;

$cancellationPolicy = $listing->cancel_policy_v2->name ?? null;
$paymentSchedule = $listing->payment_schedule ?? null;
$calendarv2 = $listing->calendar_v2 ?? null;

$overIn = [];
$overOut = [];
$overMinStay = [];

if (!empty($calendarv2)) foreach ($calendarv2 as $d => $item) {
    if ($item->cta == 1) $overIn[] = $d;
    if ($item->ctd == 1) $overOut[] = $d;
    if ($item->min > 1 && !isset($calendarCustomMinStay[$d])) $overMinStay[$d] = $item->min;
}

// $this->title = $listingData->name . ' - ' . ListingHelper::getRoomTypeForTitle($listingData->room_type) . ' Rent in ' . $listingData->city . ', ' . $listingData->country;

// add_action('wp_head', function() use (
//     $calendarDisabledDates,
//     $calendarCustomStay,
//     $calendarCustomMinStay,
//     $listingData
// ) {

// if (!defined('hfy_already_rendered_js_1')) {
    // define('hfy_already_rendered_js_1', 1);

// todo try to move it to place like add_general_nonce_and_settings

echo '
<script>var
calendarDisabledDates = '.json_encode($calendarDisabledDates).',
calendarCustomStay = '.json_encode($calendarCustomStay).',
calendarCustomMinStay = '.json_encode($calendarCustomMinStay).',
calendarInDays = '.intval($listingData->no_checkin_days ?? 0).',
calendarOutDays = '.intval($listingData->no_checkout_days ?? 0).',
calendarOverInDays = '.json_encode($overIn ?? []).',
calendarOverOutDays = '.json_encode($overOut ?? []).',
calendarOverMinStay = '.json_encode($overMinStay ?? []).',
minNights = '.intval($listingData->min_nights ?? 1).',
maxNights = '.intval($listingData->max_nights ?? 111).',
lat = "'.$listingData->lat.'",
lng = "'. $listingData->lng.'",
hfyltm = '.intval($longTermMode ?? HFY_LONG_TERM_DEFAULT).',
hfyminstay = '.( ($longTermMode ?? HFY_LONG_TERM_DEFAULT) == 1 ? 28 : intval($listingData->min_nights ?? 1) ).'
;
</script>';

// }
