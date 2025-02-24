<?php

if ( ! defined( 'WPINC' ) ) die;

require_once HOSTIFYBOOKING_DIR . 'inc/lib.php';

include HOSTIFYBOOKING_DIR . 'inc/shortcodes/inc/load-settings.php';

// wp_enqueue_script( 'hfygmaps' );
// wp_enqueue_script( 'hfygmap3' );
hfyIncludeMaps($settings);

require_once HOSTIFYBOOKING_DIR . 'inc/helpers/ListingHelper.php';
require_once HOSTIFYBOOKING_DIR . 'inc/helpers/UrlHelper.php';

$prm = hfy_get_vars_([
	'neighbourhood',
	'city_id',
	'start_date',
	'end_date',
	'guests',
	'adults',
	'children',
	'infants',
	'bedrooms',
	'bathrooms',
	'long_term_mode',
	'pmin',
	'pmax',
	'tag',
	'pg',
	'pets',
	'custom_search',
]);

$guests = $prm->guests;
$adults = $prm->adults;
$children = $prm->children;
$infants = $prm->infants;
$pets = $prm->pets;

$page = $prm->pg < 1 ? 1 : $prm->pg;

$prm->prop = hfy_get_('prop', []);
$prm->am = hfy_get_('am', []);
$prm->hr = hfy_get_('hr', []);
$prm->tag = hfy_get_('tag', '');
if (empty($prm->tag)) {
	$prm->tag = hfy_get_('tags', '');
}

$tags = empty($prm->tag) ? ($tags ?? '') : $prm->tag;

$ids = isset($ids) ? $ids : '';
$max = isset($max) ? $max : 9999; // todo

$_cities = empty($city) ? (empty($cities) ? false : $cities) : $city;
$city_id = $prm->city_id ? $prm->city_id : $_cities;
$city_list = $city_id !== false;

if (!empty($neighbourhood)) {
	$prm->neighbourhood = $neighbourhood;
}

$longTermMode = hfy_ltm_fix_(!empty($monthly) ? $monthly : ($prm->long_term_mode ?? HFY_LONG_TERM_DEFAULT));

$api = new HfyApi();
$result = $api->getAvailableListings([
	'ids' => $ids,
	'city_list' => $city_list,
	'city_id' => $city_id,
	'start_date' => $prm->start_date,
	'end_date' => $prm->end_date,
	'guests' => (int) $prm->guests,
	'bedrooms' => $prm->bedrooms,
	'bathrooms' => $prm->bathrooms,
	'longTermMode' => $longTermMode,
	'prop' => $prm->prop,
	'am' => $prm->am,
	'neighbourhood' => $prm->neighbourhood,
	'custom_search' => $prm->custom_search,
	'hr' => $prm->hr,
	'pmin' => floatval($prm->pmin),
	'pmax' => floatval($prm->pmax),
	'tags' => $tags,
	'page' => 1,
	'per_page' => 9999, // todo
	'show_prices' => 1,// HFY_MAP_PRICE_LABEL,
	'pets' => $pets
], true);

$listings = isset($result->listings) ? (array) $result->listings : null;

$mapMarkers = [];
if ($listings) {
	foreach ($listings as $list) {
		$p = $list->price ?? 0;
		if (HFY_MAP_PRICE_LABEL && $p > 0) {
			$mapMarkers[] = [$list->id, $list->lat, $list->lng, ListingHelper::formatPrice($p, (object) [
				'symbol' => $list->cur_symbol ?? $list->symbol,
				'position' => $list->cur_position ?? $list->position,
			], true, 2, ',', ' ')];
		} else {
			$mapMarkers[] = [$list->id, $list->lat, $list->lng];
		}
	}
}

?>
<script>
var
mapPrices = <?= HFY_MAP_PRICE_LABEL ? 'true' : 'false' ?>,
mgreyImg = '<?= HOSTIFYBOOKING_URL . 'public/res/images/mgrey.png' ?>',
mredImg = '<?= HOSTIFYBOOKING_URL . 'public/res/images/mred.png' ?>',
meImg = '<?= HOSTIFYBOOKING_URL . 'public/res/images/mc.png' ?>',
hfyPerPage = <?= HFY_LISTINGS_PER_PAGE ?>,
hfyMapMarkers = <?= json_encode($mapMarkers) ?>,
hfyMapMarkersClusters = <?= HFY_MAP_CLUSTERS ? 'true' : 'false' ?>
</script>
<?php

include hfy_tpl('listing/listings-map');
?>

<div class="info-window-content-wrap" style='display:none'>
	<?php include hfy_tpl('listing/listings-map-marker-info'); ?>
</div>
