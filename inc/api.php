<?php

if ( ! defined( 'WPINC' ) ) die;

require_once HOSTIFYBOOKING_DIR . 'inc/vendors/Curl.php';
require_once HOSTIFYBOOKING_DIR . 'inc/vendors/CurlCached.php';
require_once HOSTIFYBOOKING_DIR . 'inc/vendors/HttpException.php';

class HfyApi
{
    const RESERVATION_STATUS_ACCEPTED = 'accepted';
    const RESERVATION_STATUS_PENDING = 'pending';
    const RESERVATION_STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    const RESERVATION_STATUS_NEW = 'new';

    private $apiUrl;
    private $apiKey;
    private $apiWsid;
    private $apiVersion;
    private $integrationId;
    private $headers;
    private $installed = false;
    private $installed_message = '';
    private $apiWpkey;
    private $useFA = false;

    private $cache_use;
    private $cache_use_cmd;

    private const USE_CACHE = 'use';
    private const NO_CACHE = 'refresh';

    public $listings_per_page = 20;

    public function __construct()
    {
        $this->useFA = defined('HFY_USE_API_FA') && (HFY_USE_API_FA == true);
        $this->apiVersion = (HFY_USE_API_V3 || $this->useFA) ? '3' : '2';

        $this->listings_per_page = HFY_LISTINGS_PER_PAGE;

        $this->cache_use = (HFY_DISABLE_CACHE || HFY_USE_API_V3 || $this->useFA) ? false : true;
        $this->cache_use_cmd = $this->cache_use ? self::USE_CACHE : self::NO_CACHE;

		$this->apiUrl = HFY_API_URL;
		if (strlen($this->apiUrl) < 4) {
			$this->installed_message = 'Hostify: Please set API URL';
		}

        $this->apiWpkey = HFY_API_WPKEY;
		if (empty($this->apiWpkey) && empty($this->installed_message)) {
			$this->installed_message = 'Hostify: Please set API WPKEY';
		}

        $x = explode('-', base64_decode($this->apiWpkey), 3);
        $this->apiKey = $x[0] ?? '';
        $this->apiWsid = $x[1] ?? '';
        $this->integrationId = $x[2] ?? '';

        if (
            empty($this->apiKey)
            || empty($this->apiWsid)
            || empty($this->integrationId)
        ) {
            if (empty($this->installed_message)) {
			    $this->installed_message = 'Hostify: Invalid API WPKEY, please check';
            }
		}

        $token = hfy_get_sess_('x-api-key');
        if (empty($token)) {
            $_SESSION['x-api-key'] = $this->apiKey;
            $_SESSION['integration-id'] = $this->integrationId;
        }

        $this->headers = [
            'x-api-key' => $this->apiKey,
            'Content-type' => 'application/json',
            'integration-id' => $this->integrationId,
            'wsid' => $this->apiWsid
		];

        if (
            strlen($this->apiUrl) < 4
            || empty($this->apiKey)
            || empty($this->apiWsid)
            || empty($this->integrationId)
        ) {
            if (empty($this->installed_message)) {
                // $this->installed_message = 'Hostify: Plugin not yet configured';
            }
        } else {
            $this->installed = true;
            $this->installed_message = 'Hostify: Plugin configured successfully';
        }
    }

    public function getInstalledStatus()
    {
        return $this->installed;
    }

    public function getInstalledMessage()
    {
        return $this->installed_message;
    }

    public function getCurlInstance($cache_time = 0, $force_cache_sec = false)
    {
        if ($force_cache_sec) {
            return new CurlCached($force_cache_sec, true);
        }
        return $this->cache_use
            ? new CurlCached($cache_time, $this->cache_use)
            : new Curl();
    }

    public function getSettings($for_listing_id = 0)
    {
        $curl = $this->getCurlInstance(300, 5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'max_min_prices' => true,
                'cache' => $this->cache_use_cmd,
                'expire' => 3,
                'listing_id' => $for_listing_id,
                'lang' => hfyGetCurrentLang(),
                // 'tags' => 'cm,meta',
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/settings');
// echo '<pre>';
// print_r(json_decode($response));die;
// var_dump($response);die;
// print_r($response);die;
        return json_decode($response);
    }

    public function getPaymentSettings($listing_id = 0, $start_date = null, $end_date = null)
    {
        $curl = $this->getCurlInstance(300, 5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'listing_id' => $listing_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'cache' => $this->cache_use_cmd,
                'expire' => 3,
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/payments');
// echo '<pre>';
// print_r($response);die;
// print_r(json_decode($response));die;
        return json_decode($response);
    }

    public function getPaymentToken($method, $listing_id = 0)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'method' => $method,
                'listing_id' => $listing_id,
            ]))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/token');
// echo '<pre>';
// print_r($response);die;
        return json_decode($response);
    }

    public function postPaymentCustomer($method, $customerData, $token, $paymentAccountId, $listing_id = 0)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'method' => $method,
                'data' => $customerData,
                'token' => $token,
                'id' => $paymentAccountId,
                'listing_id' => $listing_id,
            ]))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/customer');
// var_dump($response);die;
        return json_decode($response);
    }

    public function postPaymentCharge($method, $data, $resInfo, $customerId = 0, $listing_id = 0)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'method' => $method,
                'data' => $data,
                'resInfo' => $resInfo,
                'customerId' => $customerId,
                'listing_id' => $listing_id,
            ]))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/charge');
// var_dump($response);die;
        return json_decode($response);
    }

    public function getListingsByIds($ids = [])
    {
        $curl = $this->getCurlInstance(300);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'include_related_objects' => 1,
                'ids' => is_array($ids) ? implode(',', $ids) : $ids,
                'cache' => $this->cache_use_cmd,
                'expire' => 10,
            ])
            ->get($this->apiUrl . 'listings/ids');
        return json_decode($response);
    }

    public function getListingWithParams($id = '', $params = [])
    {
        $curl = $this->getCurlInstance(300);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams($params)
            ->get($this->apiUrl . 'listings/' . $id);
// var_dump($response);die;
        return json_decode($response);
    }

    public function getListing($id = '', $start_date = '', $end_date = '', $guests = false)
    {
        return $this->getListingWithParams($id, [
            'start_date' => substr($start_date, 0, 10),
            'end_date' => substr($end_date, 0, 10),
            'guests' => $guests,
            'include_related_objects' => 1,
            'cache' => $this->cache_use_cmd,
            'expire' => 5,
        ]);
    }

    public function getListings($ids = '', $cities = '', $max = 4, $id_except = 0)
    {
        $nn = $max;
        $toget = [];
        $a = explode(',', $ids);
        foreach ($a as $id) {
            $id = intval($id);
            if ($id > 0 && $id != $id_except) {
                $toget[] = $id;
            }
        }
        if (!empty($cities)) {
            $res = $this->getAvailableListings([ 'city_id' => $cities ]);
            if ($res->success) {
                if ($res->listings) {
                    foreach ($res->listings as $l) {
                        if ($l->id != $id_except) {
                            $toget[] = $l->id;
                            if ($max > 0) if (--$nn <= 0) break;
                        }
                    }
                }
            }
        }
        $toget = array_unique($toget);
        if (count($toget) > 0) {
            $ls = $this->getListingsByIds($toget);
            if ($ls->success) {
                return $ls->listings;
            }
        }
        return [];
    }

    public function getRecommendedListings($id = 0, $tags = '', $max = 4)
    {
        if (!empty($tags)) {
            $response = $this->getAvailableListings([
                'per_page' => $max + 1,
                'tags' => $tags,
            ]);
            return $response->listings ?? [];
        }

        $curl = $this->getCurlInstance(5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'listing_id' => (int) $id,
                'max' => (int) $max,
                'lang' => hfyGetCurrentLang(),
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/listings_recommended/' . $id);
// echo '<pre>';
// var_dump($response);die;
// print_r(json_decode($response));die;
// print_r($response);die;
        $decoded = json_decode($response);
        return $decoded->listings ?? [];
    }

    public function getWebsiteListing($id = '', $related = true, $start_date = '', $end_date = '', $guests = false, $adults = false, $children = false, $infants = false, $pets = false, $extras = false)
    {
        $curl = $this->getCurlInstance(300, 5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'start_date' => substr($start_date, 0, 10),
                'end_date' => substr($end_date, 0, 10),
                'guests' => intval($guests < 1 ? 1 : $guests),
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'pets' => $pets,
                'include_related_objects' => $related ? 1 : 0,
                'cache' => $this->cache_use_cmd,
                'expire' => 5,
                'lang' => hfyGetCurrentLang(),
                'extras' => $extras ? 1 : 0,
                // 'policies' => HFY_USE_API_V3 ? 1 : 0, // todo
                'policies' => $related ? 1 : 0,
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/listings_view/' . $id);
// echo '<pre>';
// print_r($response);die;
// print_r(json_decode($response));die;
// var_dump($response);die;
// var_dump(json_decode($response));die;
        return json_decode($response);
    }

    public function getWebsiteListingMinstay($listingId = '')
    {
        $curl = $this->getCurlInstance(300, 10);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'per_page' => 365 * 2, // todo get from current date + NNN
                'listing_id' => $listingId,
                'include_minstay' => 1,
                'cache' => $this->cache_use_cmd,
                'expire' => 3,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'calendar/index');
// var_dump($response);die;
// print_r(json_decode($response));die;
        return json_decode($response);
    }

    public function getAvailablePropertyTypes()
    {
        $curl = $this->getCurlInstance(3600, 30);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'cache' => $this->cache_use_cmd,
                'expire' => 300,
                'lang' => hfyGetCurrentLang()
            ])
            // ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/available_property_types');
            ->get($this->apiUrl . 'websitesv2/available_property_types');
        $t = json_decode($response);
        return $t->types ?? [];
    }

    public function getNeighbourhoods()
    {
        $curl = $this->getCurlInstance(3600);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'cache' => $this->cache_use_cmd,
                'expire' => 10,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/neighbourhoods');
        $t = json_decode($response);
        return $t->amenities ?? [];
    }

    public function getAvailableListings($prms = [], $forMap = false)
    {
        extract(shortcode_atts([
            'ids' => null,
            'country' => null,
            'state' => null,
            'city_list' => false,
            'city_id' => false, // one or more id, comma separated
            'neighbourhood' => null, // raw (xx:xx:xx:xx) - need to parse
            'custom_search' => null,
            'start_date' => '',
            'end_date' => '',
            'guests' => false,
            'bedrooms' => false,
            'bathrooms' => false,
            'longTermMode' => false,
            'prop' => null,
            'am' => null,
            'hr' => null,
            'pmin' => null,
            'pmax' => null,
            'page' => 1,
            'per_page' => $this->listings_per_page,
            'sort' => null,
            'tags' => '',
            'show_prices' => 0,
            'with_amenities' => false,
            'pets' => 0,
        ], $prms));

        // if (empty($start_date) || empty($end_date)) {
        //     $guests = false;
        // }

        // if (!empty($ids) && !$forMap) {
        //     $apiPoint = 'websitesv2/listings_available';
        // } else {
            $apiPoint = $forMap ? 'websitesv'.$this->apiVersion.'/listings_available_map' : 'websitesv'.$this->apiVersion.'/listings_available';
        // }

        $params = [
            'ids' => $ids,
            'page' => $page,
            'per_page' => $per_page,
            'city_list' => $city_list,
            'city_id' => $city_id,
            'start_date' => substr($start_date, 0, 10),
            'end_date' => substr($end_date, 0, 10),
            'guests' => (int) $guests,
            'longTermMode' => $longTermMode == 1,
            'with_photos' => true,
            'lang' => hfyGetCurrentLang(),
            'sort' => $sort,
            'tags' => $this->apiVersion == 3 ? explode(',', $tags) : $tags,
            'show_prices' => !$show_prices ? 0 : 1,
            'with_amenities' => !$with_amenities ? 0 : 1,
            'pets' => $pets,
        ];

        $bedrooms = is_numeric($bedrooms) ? $bedrooms : false;
        if ($bedrooms !== false) {
            $params['bedrooms'] = HFY_SEARCH_EXACT_BEDROOMS ? "-" . intval($bedrooms) : $bedrooms;
        }

        $bathrooms = is_numeric($bathrooms) ? $bathrooms : false;
        if ($bathrooms !== false) {
            $params['bathrooms'] = HFY_SEARCH_EXACT_BEDROOMS ? "-" . intval($bathrooms) : $bathrooms;
        }

        if (floatval($pmin ?? 0) > 0) $params['price_min'] = floatval($pmin);
        if (floatval($pmax ?? 0) > 0) $params['price_max'] = floatval($pmax);

        if ($prop) $params['prop'] = $prop;
        if ($am) $params['am'] = $am;

        if ((HFY_USE_API_V3 || $this->useFA) && !empty($custom_search)) {
            $params['q'] = $custom_search;
        } else {
            if ($neighbourhood) {
                $x = hfyParseNeighbourhood($neighbourhood);
                if (!empty($x->neighbourhood)) {
                    $params['neighbourhood'] = $x->neighbourhood;
                }
                if (empty($params['city_id']) && $x->city_id > 0) {
                    $params['city_id'] = $x->city_id;
                }
                if (!empty($x->country)) $params['country'] = $x->country;
                if (!empty($x->state)) $params['state'] = $x->state;
            }
        }

        if ($hr) {
            $allowed = [
                '1' => 'pets_allowed',
                '2' => 'events_allowed',
                '3' => 'smoking_allowed',
                '4' => 'children_allowed'
            ];
            $hrs = [];
            foreach ($hr as $val) {
                if (isset($allowed[$val])) $hrs[] = $allowed[$val];
            }

            if (!empty($hrs)) {
                $params['hr'] = implode(',', $hrs);
            }
        }
// echo '<pre>';
// print_r($this->apiUrl . $apiPoint);
// print_r($params);
// die;
        // $curl = new CurlCached($this->apiVersion == 3 ? 3 : 15, $this->cache_use);
        // $curl = $this->getCurlInstance(1);
        $curl = new Curl();

        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode($params))
            ->post($this->apiUrl . $apiPoint);
// var_dump($response);die;
// print_r($response);die;
// print_r(json_decode($response));die;
        return json_decode($response);
    }

    public function getListingPrice($listing_id, $start_date = '', $end_date = '', $guests = false, $payment = false, $discountCode = '', $adults = 0, $children = 0, $infants = 0, $pets = 0, $fees = [])
    {
        $curl = $this->getCurlInstance(5);
// var_dump($this->headers);die;
// var_dump($this->apiWsid);die;
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'listing_id' => $listing_id,
                'start_date' => substr($start_date, 0, 10),
                'end_date' => substr($end_date, 0, 10),
                'guests' => $guests,
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'pets' => $pets,
                'payment' => $payment,
                // 'onlycheck' => $payment ? 1 : 0,
                'discount_code' => $discountCode,
                'wsid' => $this->apiWsid,
                // 'cache' => 'refresh',
                // 'cache' => $this->cache_use_cmd,
                // 'expire' => 5,
                'fees' => is_array($fees) ? $fees : explode(',', $fees),
            ])->get($this->apiUrl . 'websitesv'.$this->apiVersion.'/listings_price');
            // ])->get($this->apiUrl . 'websitesv2/listings_price');
// print_r($response);die;
// var_dump($response);die;
        $out = json_decode($response);
// print_r($out);die;
// print_r($out->price->v3);die;
        if ($this->apiVersion == 3) {
            if (isset($out->price->v3->advanced_fees)) {
                $out->price = $this->fixFees($out->price ?? [], $start_date, $end_date, $guests, $adults, $children, $infants, $pets);
            }
            if (isset($out->price->v3->extras)) {
                $out->price->extras = $out->price->v3->extras;
            }
        }
// print_r($out);die;
        return $out;
    }

    public function fixFees($price, $start_date, $end_date, $guests, $adults = 0, $children = 0, $infants = 0, $pets = 0, $feesExtraIdsExclude = [])
    {
        $v3 = $price->v3 ?? false;
        $price->extras = $price->v3->extras ?? [];
        $fees = $price->v3->advanced_fees ?? $price->fees ?? [];

        if (!empty($fees)) {
            $price->feesAll = (object) [];
            $price->feesAll->fees = $fees;
            $price->fees = [];
            $price->totalFees = 0;
            $price->taxes = [];
            $price->totalTaxesCalc = 0;
            $price->offline = [];
            $price->totalOfflineCalc = 0;
            $price->feesBasePrice = 0;
            $price->feesAccommodation = [];
            $price->feesRecurring = [];
            $price->paymentSchedule = [];
            $price->extras = [];
            $price->totalExtrasCalc = 0;
            $price->monthlyPricingTable = [];
            $price->monthlyPricingDiscountTable = [];

            $isMonthlyDynamic = 0;

            $currentDate = date('Y-m-d',strtotime($start_date));

            $sub = 0;
            foreach ($fees as &$fee) {

                if ($fee->type == 'cost') {
                    // $sub += $fee->total;
                    continue;
                }
                if ($fee->type == 'accommodation') continue;

                if (!isset($fee->fee_name) && isset($fee->name)) $fee->fee_name = $fee->name;
                if (!isset($fee->property_fee_id) && isset($fee->id)) $fee->property_fee_id = $fee->id;
                if (!isset($fee->charge_type_label)) $fee->charge_type_label = $fee->amount . ' ' . __($fee->fee_charge_type, 'hostifybooking');

                // if ($fee->type !== 'extra' && isset($fee->is_base_price)) {
                if ($fee->type !== 'extra') {
                    if (!isset($fee->is_base_price) && floatval($fee->total) > 0) {
                        if (
                            strtolower($fee->condition_type ?? '') != 'online'
                            && $fee->fee_id != 470 // pet fee
                        ) {
                            $price->offline[] = $fee;
                            $price->totalOfflineCalc += floatval($fee->total);
                        } else if ($fee->type == 'tax') {
                            $price->taxes[] = $fee;
                            $price->totalTaxesCalc += floatval($fee->total);
                        } else {
                            $price->fees[] = $fee;
                            $price->totalFees += floatval($fee->total);
                        }
                    } else if (isset($fee->is_base_price)) {
                        $price->feesBasePrice += floatval($fee->total);
                        $price->feesAccommodation[] = $fee;
                    }
                } else {
                    if (
                        (is_array($feesExtraIdsExclude) && in_array($fee->fee_id, $feesExtraIdsExclude))
                        || $fee->type == 'extra'
                    ) {
                        $price->extras[] = $fee;
                        $price->totalExtrasCalc += floatval($fee->total);
                    }
                }

                if ($fee->fee_charge_type == "Per Month" || $fee->fee_charge_type == "Per Month Dynamic"){

                    if(!array_key_exists($fee->fee_name, $price->feesRecurring)){
                        $price->feesRecurring[$fee->fee_name] = (object)[
                            'fee_id' => $fee->fee_id,
                            'fee_name' => $fee->fee_name,
                            'is_base_price' => $fee->is_base_price ?? 0,
                            'condition_type' => $fee->condition_type,
                            'amount' => $fee->amount,
                        ];
                    }

                    if (!array_key_exists($fee->start_date, $price->paymentSchedule)) {
                        $price->paymentSchedule[$fee->start_date] = (object)[
                                'date' => $fee->start_date,
                                'total' => 0,
                                'fees' => [],
                            ];
                    }
                    $price->paymentSchedule[$fee->start_date]->total += $fee->total;
                    $price->paymentSchedule[$fee->start_date]->fees[] = $fee;

                    if($fee->fee_charge_type == "Per Month Dynamic"){
                        $isMonthlyDynamic = 1;
                    }

                } else {
                    if (!array_key_exists($currentDate, $price->paymentSchedule)) {
                        $price->paymentSchedule[$currentDate] = (object)[
                            'date' => $currentDate,
                            'total' => 0,
                            'fees' => [],
                        ];
                    }
                    $price->paymentSchedule[$currentDate]->total += $fee->total;
                    $price->paymentSchedule[$currentDate]->fees[] = $fee;
                }
            }

            // if($isMonthlyDynamic){
            //     $price->monthlyPricingTable = $this->generateMonthlyDynamicTable($listing_id, $start_date);
            //     $price->monthlyPricingDiscountTable = $this->generateMonthlyDynamicDiscountTable($listing_id);
            // }

            $price->subtotal = $fees->total_net ?? $v3->subtotal ?? 0;
            $price->totalTaxes = $v3->tax ?? 0;
            $price->totalAfterTax = $fees->total ?? $v3->total ?? 0;
            // if ($price->totalAfterTax > 0) $price->totalAfterTax = $price->totalAfterTax - $sub;
            $price->includes_exclusive_fees = $fees->includes_exclusive_fees ?? 1; // todo
        }
        return $price;
    }

    public function postTransaction($reservation_id, $amount, $currency, $processor, $code, $paymentIntegrationId, $customerId)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'reservation_id' => $reservation_id,
                'amount' => $amount,
                'currency' => $currency,
                'charge_date' => date('Y-m-d'),
                'arrival_date' => date('Y-m-d', strtotime('+7 days')),
                'is_completed' => 1,
                'details' => ucfirst($processor) . " transaction: {$code}",
                'payment_integration_id' => $paymentIntegrationId,
                'channel_transactionId' => $code,
                'customerId' => $customerId
            ]))
            ->post($this->apiUrl . 'websites/transactions');
		return json_decode($response);
    }

    public function postBookListing($listing_id, $start_date, $end_date, $guests, $total_price, $name, $email, $phone, $note, $status, $discount_code = '', $discount_id = 0, $adults = 0, $children = 0, $infants = 0, $pets = 0, $fees = [])
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'created_at' => date('c'), // ISO 8601 format
                'listing_id' => $listing_id,
                'start_date' => substr($start_date, 0, 10),
                'end_date' => substr($end_date, 0, 10),
                'guests' => $guests,
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'pets' => $pets,
                'total_price' => $total_price,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'note' => $note,
                'source' => empty($_COOKIE['hfy_source'] ?? '') ? 'Website' : $_COOKIE['hfy_source'],
                'status' => $status,
                'discount_code' => $discount_code,
                'discount_id' => $discount_id,
                'website_id' => $this->apiWsid,
                'fees' => is_array($fees) ? $fees : explode(',', $fees),
            ]))
            ->post($this->apiUrl . ($this->useFA ? 'websitesFlataway/reservations_flataway_parent' : 'websitesv' . $this->apiVersion . '/reservations'));
// echo '<pre>';
// var_dump($response);die;
// error_log(var_export($response,1));
        return json_decode(preg_replace('/^[^\{]+/s', '', $response));
    }

    public function getTerms()
    {
        $curl = $this->getCurlInstance(3600, 30);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'cache' => $this->cache_use_cmd,
                'expire' => 10,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/terms');
		return json_decode($response);
    }

    public function getHomepageData($longTermMode = false)
    {
        $cache_was_cleared = '';
        if (isset($_SESSION['cache_was_cleared']) && $_SESSION['cache_was_cleared'] == 1) {
            $_SESSION['cache_was_cleared'] = 0;
            $cache_was_cleared = '?cwu=1';
        }
        $curl = $this->getCurlInstance(300, 5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'longTermMode' => $longTermMode == 1,
                'lang' => hfyGetCurrentLang(),
                'cache' => $this->cache_use_cmd,
                'expire' => 5,
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/homepage' . $cache_was_cleared);
// echo '<pre>';
// print_r($response);die;
// print_r(json_decode($response));die;
		return json_decode($response);
    }

    public function getTopListings($max = 4)
    {
        $curl = $this->getCurlInstance(3600);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'max' => $max,
                'cache' => $this->cache_use_cmd,
                'expire' => 3600,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/top_listings');
		return json_decode($response);
    }

    public function getAmenities($short = true)
    {
        $curl = $this->getCurlInstance(3600, 30);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'short' => $short ?? true,
                'cache' => $this->cache_use_cmd,
                'expire' => 300,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/amenities');
        return json_decode($response);
    }

    public function getListingsIdNames()
    {
        $curl = $this->getCurlInstance(3600);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'cache' => $this->cache_use_cmd,
                'expire' => 300,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/listings_ids_names');
        return json_decode($response);
    }

    public function postPayment3ds($data)
    {
        $fees = $data['fees'] ?? [];
        $data['fees'] = is_array($fees) ? $fees : explode(',', $fees);
        $data['website_id'] = $this->apiWsid;
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode($data))
            ->post($this->apiUrl . ($this->useFA ? 'websitesFlataway/payment_3ds_flataway' : 'websites/payment_3ds'));
// var_dump($response);die;
// error_log(var_export($response,1));
        return json_decode(preg_replace('/^[^\{]+/s', '', $response));
    }

    public function getReservationsByEmail($email = '', $rid = null)
    {
        $curl = $this->getCurlInstance(5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'email' => $email,
                // 'rid' => $email, // todo
                'cache' => 'use',
                'expire' => 5,
                'lang' => hfyGetCurrentLang()
            ]))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/reservations_by_email');
// echo '<pre>';
// print_r($response);die;
// print_r(json_decode($response));die;
        return json_decode($response);
    }

    public function getReservation($id = 0, $email = '', $cache = true)
    {
        $curl = $this->getCurlInstance(5);

        $data = [
            'id' => $id,
            'email' => $email,
            'lang' => hfyGetCurrentLang()
        ];

        if ($cache) {
            $data['cache'] = 'use';
            $data['expire'] = 5;
        }

        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode($data))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/reservation');
// echo '<pre>';
// print_r(json_decode($response));die;
// var_dump($response);die;
        return json_decode($response);
    }

    public function postCancelReservation($reservation_id, $message_guest = '')
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'reservation_id' => $reservation_id,
                'message_guest' => $message_guest,
            ]))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/cancel_reservation');
// var_dump($response);die;
        return json_decode($response);
    }

    public function confirmReservation($reservation_id, $status, $transaction_fee, $transaction_data)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode([
                'reservation_id' => $reservation_id,
                'status' => $status,
                'transaction_fee' => $transaction_fee,
                'transaction_data' => $transaction_data
            ]))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/confirm_reservation');

        $response = json_decode($response);
        return $response;
    }

    public function reservationFailedPayment($reservation_id)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode(['reservation_id' => $reservation_id]))
            ->post($this->apiUrl . ($this->useFA ? 'websitesFlataway/reservation_failed_payment_flataway' : 'websitesv' . $this->apiVersion . '/reservation_failed_payment'));

        return json_decode($response);
    }

    public function paypalPayment($data)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode(array_merge($data, [
                'discount_code' => $data['discount_code'] ?? '',
                'discount_id' => $data['discount_id'] ?? '',
                'source' => empty($_COOKIE['hfy_source'] ?? '') ? 'Website' : $_COOKIE['hfy_source'],
                'status' => self::RESERVATION_STATUS_ACCEPTED,
                'website_id' => $this->apiWsid,
            ])))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/paypalpayment');
        return json_decode($response);
    }

    /**
     * Creates a Reservation and a Transaction.
     * Returns their IDs
     *
     * @return void
     * @throws Exception
     */
    public function netpayPaymentSetup($data)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode($data))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/netpay_payment_setup');

        return json_decode($response);
    }

    /**
     * Marks the Transaction as Captured and Completed
     *
     * @return mixed
     * @throws Exception
     */
    public function netpayPaymentSuccess($data)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode(array_merge($data, [
                'transaction_id' => $data['transaction_id'] ?? '',
            ])))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/netpay_payment_success');

        return json_decode($response);
    }

    /**
     * Deletes the Reservation and Transaction
     *
     * @return mixed
     * @throws Exception
     */
    public function netpayPaymentFail($data)
    {
        $curl = new Curl();
        $response = $curl
            ->setHeaders($this->headers)
            ->setRawPostData(json_encode(array_merge($data, [
                'reservation_id' => $data['reservation_id'] ?? '',
                'transaction_id' => $data['transaction_id'] ?? '',
            ])))
            ->post($this->apiUrl . 'websitesv' . $this->apiVersion . '/netpay_payment_fail');

        return json_decode($response);
    }

    public function getExtras($listing_id = 0, $ids = [])
    {
        $curl = $this->getCurlInstance(5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'listing_id' => $listing_id,
                'ids' => $ids,
                'cache' => $this->cache_use_cmd,
                'expire' => 5,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/payment_extras');
// print_r(json_decode($response));die;
// print_r($response);die;
        return json_decode($response);
    }

    public function getExtrasAll()
    {
        $curl = $this->getCurlInstance(5);
        $response = $curl
            ->setHeaders($this->headers)
            ->setGetParams([
                'cache' => $this->cache_use_cmd,
                'expire' => 5,
                'lang' => hfyGetCurrentLang()
            ])
            ->get($this->apiUrl . 'websitesv' . $this->apiVersion . '/payment_extras_all');
// var_dump($response);die;
// print_r($response);die;
        return json_decode($response);
    }

}
