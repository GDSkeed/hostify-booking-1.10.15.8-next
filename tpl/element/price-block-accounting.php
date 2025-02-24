<?php
if (!defined('WPINC')) die;
?>

<?php // ACCOMODATION ?>

<?php if (empty($prices->v3)): ?>

    <?php if ($detailedAccomodation): ?>
        <?php // detailed breakdown ?>
        <?php if (!empty($prices->feesAccommodation)) foreach ($prices->feesAccommodation as $fee) : ?>
            <div class='price-block-item'>
                <div class="_label"><?= __('Price for', 'hostifybooking') ?> <?= $fee->charge_type_label ?></div>
                <div class="_value"><?= ListingHelper::withSymbol($fee->total, $prices, $currencySymbol) ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php // just a price per night ?>
        <div class='price-block-item'>
            <div class="_label">
                <?= __( 'Price for', 'hostifybooking' ) ?> <?= $prices->nights ?> <?= __( 'nights', 'hostifybooking' ) ?>
            </div>
            <div class="_value"><?= ListingHelper::withSymbol($totalNights, $prices, $currencySymbol) ?></div>
        </div>
    <?php endif; ?>

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

<?php endif; ?>

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

<?php if (!empty($prices->fees)) foreach ($prices->fees as $fee) : ?>
    <div class='price-block-item'>
        <div class="_label"><?= $fee->fee_name ?> <?= $fee->charge_type_label ?? '' ?></div>
        <div class="_value"><?= ListingHelper::withSymbol($fee->total, $prices, $currencySymbol) ?></div>
    </div>
<?php endforeach; ?>

<?php if (!empty($prices->taxes)) foreach ($prices->taxes as $t) : ?>
    <div class='price-block-item'>
        <div class="_label"><?= $t->fee_name ?> <?= $t->charge_type_label ?? '' ?></div>
        <div class="_value"><?= ListingHelper::withSymbol($t->total, $prices, $currencySymbol) ?></div>
    </div>
<?php endforeach; ?>

<?php // OFFLINE OPTIONS ?>

<?php if (!empty($prices->offline)) : ?>
    <div class='price-block'>
        <div class='price-block-item prices-offline_'>
            <span class='upon-arrival'><?= __('Due upon arrival:', 'hostifybooking') ?></span>
            <?php foreach ($prices->offline as $o) : ?>
                <div class='price-block-item'>
                    <div class="_label"><?= $o->fee_name ?></div>
                    <div class="_value"><?= ListingHelper::withSymbol($o->total, $prices, $currencySymbol) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php // TOTAL ?>

<div class='price-block-total'>
    <?php if ($prices->includes_exclusive_fees > 0 && $prices->totalFees > 0) : ?>
        <div class='price-block-item'>
            <div class="_label"><?= __( 'Subtotal', 'hostifybooking' ) ?></div>
            <div class="_value"><?= ListingHelper::withSymbol($prices->subtotal, $prices, $currencySymbol) ?></div>
        </div>
        <div class='price-block-item'>
            <div class="_label"><?= __( 'Taxes', 'hostifybooking' ) ?></div>
            <div class="_value"><?= ListingHelper::withSymbol($prices->totalFees, $prices, $currencySymbol) ?></div>
        </div>
    <?php endif; ?>

    <div class='price-block-item'>
        <div class="_label"><?= __( 'Total', 'hostifybooking' ) ?></div>
        <?php if (empty($prices->feesAll)): ?>
            <div class="_value"><?= ListingHelper::withSymbol($prices->total ?? $prices->price, $prices, $currencySymbol) ?></div>
        <?php else: ?>
            <div class="_value"><?= ListingHelper::withSymbol($prices->totalAfterTax ?? $prices->price, $prices, $currencySymbol) ?></div>
        <?php endif; ?>
    </div>
</div>
