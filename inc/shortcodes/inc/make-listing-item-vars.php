<?php
if ( ! defined( 'WPINC' ) ) die;

$listingDescription = $listing->description;
$listingTitle = empty($listingDescription->name) ? $listing->name : $listingDescription->name;
// Use description name as translation source if available, otherwise use listing name
$listing->name = empty($listingDescription->name) ? __($listing->name, 'hostifybooking') : __($listingDescription->name, 'hostifybooking');

$rating = isset($listing->reviews->rating) ? ListingHelper::getReviewRating($listing->reviews->rating) : 0;
$reviewsRating = ListingHelper::getReviewStarRating($rating);

$issetDates = !empty($startDate) && !empty($endDate);

$listingUrl = [
    'id' => $listing->id,
    'guests' => $guests ?? 1,
    'adults' => $adults ?? 1,
    'children' => $children ?? 0,
    'infants' => $infants ?? 0,
    'pets' => $pets ?? 0,
];

if ($issetDates && isset($guests)) {
    $listingUrl = array_merge($listingUrl, [
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
}

// if (isset($listing->min_nights)) {
//     if ($listing->min_nights >= 28) {
//         $longTermMode = 1;
//     }
// }

$pricePrefix = $issetDates
    ? __('Total', 'hostifybooking')
    : __('From', 'hostifybooking');

$priceSuffix = $issetDates
    ? __('stay', 'hostifybooking')
    : (
        ($longTermMode ?? HFY_LONG_TERM_DEFAULT) == 1 ? __('month', 'hostifybooking') : __('night', 'hostifybooking')
    );

$nights = isset($listing->nights)
    ? $listing->nights
    : (isset($listing->min_nights) ? $listing->min_nights : 1);

if ($issetDates && isset($listing->calculated_price->nights)) {
    $nights = $listing->calculated_price->nights;
}

if ($nights < 1) $nights = 1;

$listing->price = empty($listing->price) ? ($listing->default_daily_price ?? $listing->price) : $listing->price;

// $priceNight = ($listing->price ?? 0) / $nights;
$priceNight = floatval(str_replace(',', '', ''.$listing->price) ?? 0) / $nights;

$priceMarkup = empty($settings->price_markup) ? $listing->price_markup : $settings->price_markup;

// if (isset($listing->calculated_price)) {
//     $price = $listing->calculated_price->priceWithMarkup;
// } else {
//     $price = ListingHelper::calcPriceMarkup(($listing->price ?? 0), $priceMarkup);
// }

// if (isset($listing->extra_person_price) && $listing->extra_person_price) {
//     $price += $listing->extra_person_price;
// }

//// see before
// $listingUrl = [
//     'id' => (int) $listing->id,
//     'guests' => (int) ($guests ?? 1),
//     'long_term_mode' => intval($longTermMode ?? HFY_LONG_TERM_DEFAULT)
// ];
$listingUrl['long_term_mode'] = intval($longTermMode ?? HFY_LONG_TERM_DEFAULT);

$_d1 = hfy_get_('start_date', '');
$_d2 = hfy_get_('end_date', '');
if (!empty($_d1) && !empty($_d2)) {
    $listingUrl = array_merge($listingUrl, [
        'start_date' => $_d1,
        'end_date' => $_d2,
        'guests' => (int) hfy_get_('guests', 1)
    ]);
}
else if ($listingUrl['long_term_mode'] == 1) {
    // $price = ListingHelper::calcPriceMarkup((30 * $listing->default_daily_price), $priceMarkup);
    // if (!empty($listing->monthly_price_factor) && $listing->monthly_price_factor > 0) {
    //     $price = round($price * ((100 - $listing->monthly_price_factor) / 100));
    // }
}

$showReviews = isset($settings->reviews) ? $settings->reviews : false;

$priceFallbackMonth = $priceNight * 30;

// if ($price <= 0)
$price = ($longTermMode ?? HFY_LONG_TERM_DEFAULT) == 1
    ? (
        $listing->price_monthly > 0 ? $listing->price_monthly : (
            $listing->calculated_price->total
            ?? $listing->calculated_price->price
            ?? $listing->min_price_monthly
            ?? $priceFallbackMonth
        )
    )
    : (isset($listing->calculated_price) ? (
        $listing->calculated_price->total
        ?? $listing->calculated_price->price
        ?? $listing->price
    ) : $listing->price);


$priceOnRequest = $listing->is_upon_request ?? $listing->price_on_request ?? 0 == 1;

$priceIsEmpty = is_object($price) ? ($price->price ?? 0) : $price;
$priceIsEmpty = $priceIsEmpty <= 0;

if ($priceIsEmpty) {
    $priceOnRequest = true;
}
