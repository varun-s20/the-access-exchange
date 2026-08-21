<?php
/**
 * Interview Series §01 - the cover: story, two minors, most-watched rail.
 *
 * Replaces interview-series.html lines 75-126.
 *
 * The story poster is the LCP element on this page. It is the one image here that
 * is eager and fetchpriority="high"; everything else is lazy.
 *
 * @var WP_Post|null $story
 * @var WP_Post[]    $minors
 * @var WP_Post[]    $rail
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

if ( ! $story && ! $minors && ! $rail ) {
	return;
}

/*
 * id="cover-h" is on the cover story's heading, and the cover story is the one
 * part of this section that can be absent while the rest of it renders - the
 * three slots are independent checkboxes, so "Most watched" ticked with no
 * "Cover story" ticked is a state an owner reaches without doing anything odd.
 * Pointing aria-labelledby at an id that is not on the page leaves the section
 * with no accessible name at all, which is worse than naming it plainly.
 */
$tae_label = $story
	? 'aria-labelledby="cover-h"'
	: 'aria-label="Latest interviews"';
?>
<section class="cover" <?php echo $tae_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- one of two literals above. ?>>
	<div class="shell cover-grid">

		<div>
			<?php foreach ( $minors as $tae_minor ) : ?>
				<article class="minor">
					<?php echo tae_vid( $tae_minor, 'minor-vid', 'tae-minor' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php // The poster plays the video in place, so the headline is the only route to the episode page. Without this the three interviews at the top of the page could not be opened at all. ?>
					<h3><a href="<?php echo esc_url( tae_destination( $tae_minor ) ); ?>"><?php echo esc_html( get_the_title( $tae_minor ) ); ?></a></h3>
					<?php if ( tae_guest( $tae_minor ) ) : ?>
						<p class="who"><?php echo esc_html( tae_guest( $tae_minor ) ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $story ) : ?>
			<?php
			$tae_bits = array_filter(
				array(
					tae_episode( $story, 'Episode' ),
					tae_cat_name( $story ),
					(string) get_post_meta( $story->ID, 'tae_duration', true ),
				)
			);
			$tae_stand = (string) get_post_meta( $story->ID, 'tae_standfirst', true );
			$tae_name  = (string) get_post_meta( $story->ID, 'tae_guest_name', true );
			?>
			<article class="story">
				<?php echo tae_vid( $story, 'story-vid', 'tae-cover', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $tae_bits ) : ?>
					<?php // Non-breaking spaces around the separator, as the prototype has them - they stop the meta line breaking mid-token. ?>
					<p class="lab dim" style="margin-bottom:12px"><?php echo implode( ' &nbsp;·&nbsp; ', array_map( 'esc_html', $tae_bits ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
				<?php // h2, not h1: the page hero owns the h1 since Phase 2. ?>
				<h2 id="cover-h"><a href="<?php echo esc_url( tae_destination( $story ) ); ?>"><?php echo esc_html( get_the_title( $story ) ); ?></a></h2>
				<?php if ( $tae_stand ) : ?>
					<p class="stand"><?php echo esc_html( $tae_stand ); ?></p>
				<?php endif; ?>
				<?php if ( $tae_name ) : ?>
					<span class="byline"><em>With <?php echo esc_html( $tae_name ); ?></em></span>
				<?php endif; ?>
			</article>
		<?php endif; ?>

		<div>
			<?php if ( $rail ) : ?>
				<p class="rail-h lab">Most watched</p>
				<ul class="watched">
					<?php foreach ( $rail as $tae_i => $tae_watched ) : ?>
						<li>
							<a href="<?php echo esc_url( tae_destination( $tae_watched ) ); ?>">
								<span class="n"><?php echo esc_html( str_pad( (string) ( $tae_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
								<p><?php echo esc_html( get_the_title( $tae_watched ) ); ?></p>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

	</div>
</section>
