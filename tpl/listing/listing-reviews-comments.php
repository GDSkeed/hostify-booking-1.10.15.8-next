<?php if (!defined('WPINC')) die; ?>

<?php //if ($showReviews) : ?>

	<div class="hfy-reviews-comments">

		<div class="reviews-comments-list <?= $horizontal ? 'horiz' : '' ?>">
			<?php foreach ($listingReviews as $review) :
				$nn = $max;
				$review->guest_picture = str_replace('/assets/global/img/no-avatar.png', HOSTIFYBOOKING_URL . 'public/res/images/1.png', $review->guest_picture);
				?>
				<div class="reviews-comments-item">
					<img class="comment-author" src="<?= $review->guest_picture ?>" alt="<?= $review->name; ?>" />
					<div class="comment-body">
						<h5><?= $review->name; ?></h5>
						<?php /*
						<span><?= date('F Y', strtotime($review->created)); ?></span>
						*/ ?>
						<p class="comment-content"><?= $review->comments; ?></p>
					</div>
				</div>
				<?php
				if ($max > 0) if (--$max <= 0) break;
			endforeach;
			?>
		</div>

	</div>

<?php //endif; ?>
