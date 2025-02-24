<?php if (!defined('WPINC')) die; ?>

<div class="hfy-widget-wrap">
	<div class="listings-selected">

        <?php if (!empty($listings)) {
            $idx = 0;
            foreach ($listings as $item) {

				$listing = $item;

                $rating = isset($item->rating->rating) ? ListingHelper::getReviewRating($item->rating->rating) : 0;
                $reviewsRating = ListingHelper::getReviewStarRating($rating);

                $listingUrl = ['id' => $listing->id];
                if (isset($startDate) && isset($endDate) && isset($guests)) {
                    $listingUrl = array_merge($listingUrl);
                }

                $priceMarkup = !empty($settings->price_markup) ? $settings->price_markup : $listing->price_markup;
                if (isset($item->price)) {
                    $price = ListingHelper::calcPriceMarkup($item->price, $priceMarkup);
                } else {
                    $price = ListingHelper::calcPriceMarkup($listing->default_daily_price, $priceMarkup);
                }

                if (isset($listing->extra_person_price) && $listing->extra_person_price) {
                    $price += $listing->extra_person_price;
                }

                $listingUrl = ['id' => $listing->id];
                if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                    $listingUrl = array_merge($listingUrl);
                }

                $showReviews = isset($settings->reviews) ? $settings->reviews : false;

                //
                $custom_color = '';//$this->params['custom_color'];
                $type = 'default';
                include hfy_tpl('listing/listings-recommended-item');

                $idx++;
            } ?>
        <?php } else { ?>
            <div class="alert alert-primary" role="alert">
                <?= $msgnodata ?>
            </div>
        <?php } ?>

    </div>
</div>
