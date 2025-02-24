<?php
if (!defined('WPINC')) die;

$price_original = $prices->v3->base_price_original ?? $prices->price_original ?? 0;
$p = $prices->v3->base_price ?? $prices->base_price ?? 0;
if ($price_original > 0 && $p > 0 && $p < $price_original) {
    //
} else {
    $price_original = 0;
}

?>

<?php // ACCOMODATION ?>

<?php if (empty($prices->v3)): ?>

    <div class='price-block-item'>
        <div class="_label">
            <?php // ListingHelper::withSymbol($listingPricePerNight, $prices, $currencySymbol) ?>
            <?= __( 'Price for', 'hostifybooking' ) ?> <?= $prices->nights ?> <?= __( 'nights', 'hostifybooking' ) ?>
        </div>
        <div class="_value">
            <?php if ($price_original > 0): ?>
                <s style="color:#aaa"><?= ListingHelper::withSymbol($price_original, $prices, $currencySymbol) ?></s><br/>
            <?php endif; ?>
            <?= ListingHelper::withSymbol($totalNights, $prices, $currencySymbol) ?>
        </div>
    </div>

<?php else: ?>

    <?php if (!empty($prices->v3->advanced_fees)): ?>
        <?php foreach ($prices->v3->advanced_fees as $fee) : ?>
            <?php if ($fee->type == 'accommodation'): ?>
                <div class='price-block-item'>
                    <div class="_label">
                        <?= $fee->name ?>
                        <br/><small><?= ListingHelper::withSymbol($fee->amount, $prices, $currencySymbol) ?> <?= $fee->fee_charge_type ?></small>
                    </div>
                    <div class="_value"><?= ListingHelper::withSymbol($fee->total, $prices, $currencySymbol) ?></div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

<?php endif;?>

<?php // DISCOUNTS ?>

<?php if (empty($prices->v3)): ?>

    <?php if (!empty($prices->monthlyPriceDiscountPercent) && $prices->monthlyPriceDiscountPercent > 0):?>
        <div class='price-block-item'>
            <div class="_label"><?=$prices->monthlyPriceDiscountPercent?>% <?= __( 'monthly discount', 'hostifybooking' ) ?></div>
            <div class="_value">&minus;&nbsp;<?= ListingHelper::withSymbol($prices->monthlyPriceDiscount, $prices, $currencySymbol) ?></div>
        </div>
    <?php endif;?>

    <?php if (!empty($prices->weeklyPriceDiscountPercent) && $prices->weeklyPriceDiscountPercent > 0):?>
        <div class='price-block-item'>
            <div class="_label"><?=$prices->weeklyPriceDiscountPercent?>% <?= __( 'weekly discount', 'hostifybooking' ) ?></div>
            <div class="_value">&minus;&nbsp;<?= ListingHelper::withSymbol($prices->weeklyPriceDiscount, $prices, $currencySymbol) ?></div>
        </div>
    <?php endif;?>

<?php else: ?>

    <?php if (!empty($prices->v3->discount_percent) && $prices->v3->discount_percent > 0):?>
        <div class='price-block-item'>
            <div class="_label"><?= $prices->v3->discount_percent ?>% <?= __('discount', 'hostifybooking') ?></div>
            <div class="_value">&minus;&nbsp;<?= ListingHelper::withSymbol($prices->v3->discount_percent * $prices->v3->base_price_original / 100, $prices, $currencySymbol) ?></div>
        </div>
    <?php endif;?>

<?php endif;?>

<?php if (HFY_SHOW_DISCOUNT): ?>
    <?php if (isset($prices->discount) && isset($prices->discount->success) && $prices->discount->success) { ?>
        <div class='price-block-item'>
            <div class="_label"><?= $prices->discount->type == '%' ? $prices->discount->message . ' coupon' : 'Coupon' ?> discount</div>
            <div class="_value">&minus;&nbsp;<?= ListingHelper::withSymbol($prices->discount->abs, $prices, $currencySymbol) ?></div>
        </div>
        <input name="dcid" type="hidden" value="<?= isset($prices->discount->id) ? $prices->discount->id : 0 ?>" />
    <?php } else if (isset($discount_code) && trim($discount_code) !== '') { ?>
        <div class='price-block-item'>
            <div class="_label"><?= __('Coupon Discount', 'hostifybooking') ?></div>
            <div class="_value color-red"><?= __('Wrong or inactive code', 'hostifybooking') ?></div>
        </div>
    <?php } ?>
<?php endif;?>

<?php // FEES, TAXES ?>

<?php if ($prices->cleaning_fee) { ?>
    <div class='price-block-item'>
        <div class="_label"><?= __( 'Cleaning fee', 'hostifybooking' ) ?></div>
        <div class="_value"><?= ListingHelper::withSymbol($prices->cleaning_fee, $prices, $currencySymbol) ?></div>
    </div>
<?php } ?>

<?php if ($prices->extra_person_price) { ?>
    <div class='price-block-item'>
        <div class="_label"><?= __( 'Extra person', 'hostifybooking' ) ?></div>
        <div class="_value"><?= ListingHelper::withSymbol($prices->extra_person_price, $prices, $currencySymbol) ?></div>
    </div>
<?php } ?>

<?php if ($tax): ?>
    <div class='price-block-item'>
        <div class="_label"><?= __( 'Tax', 'hostifybooking' ) ?></div>
        <div class="_value"><?= ListingHelper::withSymbol($tax, $prices, $currencySymbol) ?></div>
    </div>
<?php endif; ?>

<?php // TOTAL ?>

<div class='price-block-item price-block-total'>
    <div class="_label"><?= __( 'Total', 'hostifybooking' ) ?></div>
    <div class="_value"><?= ListingHelper::withSymbol($total, $prices, $currencySymbol) ?></div>
</div>
