<?php

class ListingHelper
{
    public static $listing_types = [
        1 => 'listing_category_type_home',
        2 => 'listing_category_type_hotel',
        9 => 'listing_category_type_other',
    ];

    public static $room_types = [
        1 => 'Entire home/flat',
        2 => 'Private room',
        3 => 'Shared room',
    ];

    public static $room_types_title = [
        1 => 'Flats for',
        2 => 'Rooms for',
        3 => 'Rooms for',
    ];

    public static $property_types = [
        1	=> 'Apartment',
        2	=> 'Bungalow',
        3	=> 'Cabin',
        4	=> 'Condominium',
        5	=> 'Guesthouse',
        6	=> 'House',
        7	=> 'Guest suite',
        8	=> 'Townhouse',
        9	=> 'Vacation home',
        10	=> 'Boutique hotel',
        11	=> 'Nature lodge',
        12	=> 'Hostel',
        13	=> 'Chalet',
        14	=> 'Dorm',
        15	=> 'Villa',
        16	=> 'Other',
        17	=> 'Bed and breakfast',
        18	=> 'Studio',
        19	=> 'Hotel',
        20	=> 'Resort',
        21	=> 'Castle',
        22	=> 'Aparthotel',
        23	=> 'Boat',
        24	=> 'Cottage',
        25	=> 'Camping',
        37	=> 'Serviced apartment',
        38	=> 'Loft',
        39	=> 'Hut',
    ];

    private static function fakestr() // for wp translate
    {
        $a = [
            __('Entire home/flat', 'hostifybooking'),
            __('Private room', 'hostifybooking'),
            __('Shared room', 'hostifybooking'),
        ];
    }

    public static function getListingType($listingTypeId)
    {
        return isset(self::$listing_types[$listingTypeId]) ? self::$listing_types[$listingTypeId] : '';
    }

    public static function getPropertyType($id)
    {
        return isset(self::$property_types[(int) $id]) ? __(self::$property_types[(int) $id], 'hostifybooking') : '';
    }

    public static function getRoomType($roomId)
    {
        return isset(self::$room_types[$roomId]) ? __(self::$room_types[$roomId], 'hostifybooking') : '';
    }

    public static function getRoomTypeForTitle($roomId)
    {
        return isset(self::$room_types_title[$roomId]) ? self::$room_types_title[$roomId] : '';
    }

    public static function getReviewRating($rating)
    {
        return round($rating, 1);
    }

    public static function getReviewStarRating($rating)
    {
        return $rating ? round(round($rating, 1) * (100/5)) : 0;
    }

    public static function calcPricePerNight($listingPrice, $priceMarkup = 0)
    {
        if (is_object($listingPrice)) {
            $price = round($listingPrice->price / ($listingPrice->nights ?? 1));
            return self::calcPriceMarkup($price, $listingPrice->price_markup ?? 0);
        } else {
            return self::calcPriceMarkup($listingPrice, $priceMarkup);
        }
    }

    public static function calcDefaultPrice($listingData)
    {
        return self::calcPriceMarkup($listingData->default_daily_price, $listingData->price_markup);
    }

    public static function calcPriceMarkup($price, $listingPriceMarkup) {
        // if ( //params['price_markup'] !== false) {
        //     $price *= (1 + //params['price_markup'] / 100);
        //     return round($price);
        // } else
        // todo
        if ($listingPriceMarkup) {
            $price *= (1 + $listingPriceMarkup / 100);
            return round($price);
        }
        return $price;
    }

    public static function toAirbnbDateFormat($date)
    {
        return date("Y-m-d", strtotime($date));
    }

    public static function formatPrice($price, $listing, $round = false, $num = 2, $th = ',', $space = '&nbsp;')
    {
        if (is_object($price)) {
            $price = $price->price ?? 0;
        }
        $p = number_format( $price, ($round || (!$round && intval($price) == $price)) ? 0 : 2, '.', $th);
        // $p = number_format($price, 2, '.', '');
        $sym = isset($listing->symbol)
            ? $listing->symbol
            : (
                isset($listing->currency_data)
                    ? $listing->currency_data->symbol
                    : (isset($listing->currency) ? $listing->currency : '')
            );
        $pos = isset($listing->currency_data) ? $listing->currency_data->position : null;
        if (!$pos) $pos = isset($listing->position) ? $listing->position : '';
        return $pos == 'before'
            ? $sym . $space . $p
            : $p . $space . $sym;
    }

    public static function withSymbol($value, $details = null, $sym = '', $space = '&nbsp;')
    {
        $formatted = is_string($value) ? $value : number_format($value, 2, '.', ',');
        if (isset($details)) {
            if (isset($details->position) || isset($details->currency_position)) {
                $s = isset($details->symbol) || isset($details->currency_symbol) ? ($details->symbol ?? $details->currency_symbol) : $sym;
                return ($details->position ?? $details->currency_position) == 'before'
                    ? $s . $space . $formatted
                    : $formatted . $space . $s;
            }
        }
        return $formatted . $space . $sym;
    }

    public static function listingName($listing)
    {
        if (isset($listing) && is_object($listing)) {
            $name = empty($listing->name) ? $listing->nickname : $listing->name;
            if (!empty(HFY_SEO_LISTING_SLUG_FIND)) {
				$name = str_replace(HFY_SEO_LISTING_SLUG_FIND, HFY_SEO_LISTING_SLUG_REPLACE, $name);
			}
            return $name;
        }
        return '';
    }
}
