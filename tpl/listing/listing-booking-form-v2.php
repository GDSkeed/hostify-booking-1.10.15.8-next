<?php if (!defined('WPINC')) die; ?>

<div class="hfy-listing-booking-form">

    <div class="booking-price-block body">
        <div class="price-bar calendar-fields-wrapper">
            <span class="price-title"><?= $priceTitle ?></span>
            <form class="listing-price" method="get">
                <div class="row">
                    <?php if (HFY_USE_NEW_CALENDAR): ?>
                        <input type="hidden" name="start_date" value="<?= esc_attr($start_date) ?>"/>
                        <input type="hidden" name="end_date" value="<?= esc_attr($end_date) ?>"/>
                        <div class="col">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" placeholder="<?= esc_attr__('Check In', 'hostifybooking') . ' – ' . esc_attr__('Check Out', 'hostifybooking') ?>" readonly class="form-control calentim-dates" value="<?= esc_attr($dates_value) ?>"/>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-6">
                            <div class="form-group">
                                <div class="input-group">
                                <?php $startDate ?>
                                    <input type="text" name="start_date" readonly placeholder="<?= esc_attr__('Check In', 'hostifybooking') ?>" class="input-theme1 form-control calentim-start" value="<?= esc_attr($startDate) ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" class="input-theme1 form-control calentim-end" name="end_date" readonly placeholder="<?= esc_attr__('Check Out', 'hostifybooking') ?>" value="<?= esc_attr($endDate) ?>" />
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col">
                        <input name="guests" type='hidden' value="<?= $guests ?>" />
                        <?php include hfy_tpl('element/guests-block'); ?>
                    </div>
                </div>

                <?php if (HFY_SHOW_DISCOUNT): ?>
                    <div class='row'>
                        <div class='col discount-code-wrap'>
                            <label><input autocomplete="off" type="checkbox" class='form-control discount_code_cb' /> <?= __( 'Have a discount code?', 'hostifybooking' ) ?> </label>
                            <div class="input_wrap" style='display:none'>
                                <input autocomplete="off" type="text" name="discount_code" class='form-control' placeholder="<?= __( 'Your discount code...', 'hostifybooking' ) ?>" />
                                <input type="button" class='btn btn-small btn-light discount_code_check' value="<?= esc_attr__('Apply', 'hostifybooking') ?>" />
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="discount_code" />
                <?php endif; ?>

                <div class="prices" data-channel-listing-id="<?= $listingData->channel_listing_id ?? 0 ?>">
                    <?php
                    if ($listingPrice && is_object($listingPrice)) {
                        $prices = $listingPrice;
                        $channelListingId = $listingData->channel_listing_id ?? 0;
                        $currencySymbol = $listingCurrencyData->symbol;
                        $is_listed = $listingData->is_listed;
                        $accountingActive = ($listingPrice->accounting_module ?? 0) == 1;
                        include hfy_tpl('element/price-block');
                    }
                    ?>
                </div>

                <input type="hidden" name="id" value="<?= (int) $listingData->id ?>" />
                <input type="hidden" name="listing_id" value="<?= (int) $listingData->id ?>" />

            </form>
        </div>

        <div class="reset-date-wrap">
            <a class="reset-date"><?= __( 'Clear dates', 'hostifybooking' ) ?></a>
        </div>

        <?php if ($settings->direct_inquiry_email ?? false): ?>
            <div class="direct-inquiry-modal-open">
                <span><?= __( 'Reservation Inquiry', 'hostifybooking' ) ?></span>
            </div>
        <?php endif; ?>


        <?php // CUSTOM: ?>

        <?php /* ?>

        <?php if (isset($paymentSchedule)): ?>
            <div class='payment-schedule-description'>
                <?= $paymentSchedule->description ?? '' ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($listingDescription->notes)) { ?>
            <div class='listing-description-notes'>
                <?= nl2br($listingDescription->notes); ?>
            </div>
        <?php } ?>

        <?php */ ?>

<?php /*
$paymentSchedule OBJECT EXAMPLE:

'name' => string '50% at Booking, 50% 30 Days Before Check-in'
'description' => string '50% payment at booking, remainder 30 days before check-in'
'id' => int 101
'items' => array
        0 => object
            'amount' => string '50.00'
            'type_amount' => string 'PERCENT'
            'days' => null
            'type_due' => string 'AT_BOOKING'
        1 => object
            'amount' => string '50.00'
            'type_amount' => string 'REMAINDER'
            'days' => int 30
            'type_due' => string 'BEFORE_CHECKIN'
'instant_booking' => string 'everyone'
'checkin_start' => string '00:00:00'
'checkin_end' => string '00:00:00'
'cancel_policy' => null
'checkout' => string '00:00:00'
'min_nights' => null
*/ ?>


    </div>

</div>

<?php
if ($settings->direct_inquiry_email ?? false) {
    include hfy_tpl('element/direct-inquiry-modal');
}
