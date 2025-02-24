<?php
if (!defined('WPINC')) die;

$stepActive = 2;
include hfy_tpl('payment/stripe-form-3ds-step-slider');

?>

<div class="booking-tab-title"><?= __('Extras', 'hostifybooking') ?></div>

<?php if (!empty($reserveInfo->extrasOptional)) : ?>
	<div class="extras-optional-wrap">

		<?php foreach ($reserveInfo->extrasOptional as $extra) :
			$checked = $extrasOptionalSelected[$extra->fee_id] ?? false;
			?>
			<div class="booking-title sub extras-optional">
				<div class="d-flex mb-3">
					<label class="d-flex flex-grow-1" for="extra_<?= $extra->fee_id ?>">
						<div class="w-auto">
							<input type="checkbox" value="<?= $extra->fee_id ?>" id="extra_<?= $extra->fee_id ?>" <?= $checked ? 'checked' : '' ?> />
						</div>
						<div class="flex-grow-1 px-3">
							<?= __($extra->fee_name, 'hostifybooking') ?>
							<?php $t = hfy_fee_charge_type_text($extra->fee_charge_type_id ?? 0, $nights, $guests); ?>
							<?= empty($t) ? '' : "<small>/ $t</small>" ?>
							<?php if (!empty($extra->description)): ?>
								<div class="extra-description hfy-wrap small">
									<?= nl2br($extra->description) ?>
								</div>
							<?php endif; ?>
						</div>
					</label>
					<div class="w-auto text-end">
						<?= ListingHelper::withSymbol($extra->total, $reserveInfo->prices, $listingInfo->currency_symbol) ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

	</div>
<?php endif; ?>

<div class="row mt-5">
	<div class="col">
		<button class='prev-btn' type="button">&lsaquo; &nbsp; <?= __('Back', 'hostifybooking') ?></button>
	</div>
	<div class="col text-right">
		<button class='next-btn' type="button"><?= __('Next', 'hostifybooking') ?> &nbsp; &rsaquo;</button>
	</div>
</div>
