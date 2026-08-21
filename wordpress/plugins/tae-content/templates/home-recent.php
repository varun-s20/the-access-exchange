<?php
/**
 * Home §03 - interviews are published, but none holds the "Slot · Featured
 * interview" tick yet.
 *
 * CLIENT EDIT (2026-08-20): new file. Two layouts depending on whether a
 * sneak-peek image is set (Interviews -> Links):
 *   - set   -> the most recent interview, beside that image, on the same
 *              .series-grid/.series-left/.series-right structure
 *              home-launch.php and interview-featured.php already use.
 *   - empty -> up to 3 most recent interviews in a row instead, using the
 *              width the image would have taken.
 *
 * @var WP_Post[] $posts          1 post (teaser_image set) or up to 3 (not
 *                                 set) - see inc/shortcodes.php case 'home'.
 * @var string    $teaser_image   Optional sneak-peek image URL.
 * @var string    $teaser_caption Optional caption over that image.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_teaser_image   = isset( $teaser_image ) ? trim( (string) $teaser_image ) : '';
$tae_teaser_caption = isset( $teaser_caption ) ? trim( (string) $teaser_caption ) : '';
$tae_lead            = $posts ? $posts[0] : null;
?>
<section class="series" aria-labelledby="series-h">
	<div class="shell">
		<div class="tag lab"><span class="s">03</span><span>Interview Series</span></div>
		<h2 class="h2" id="series-h">Go beyond the biography.</h2>
		<p class="lede">Titles tell us where someone arrived. We're interested in what they learned getting there - the decisions, turning points, lessons, relationships and ideas that shaped the journey.</p>

		<?php if ( $tae_teaser_image && $tae_lead ) : ?>
			<?php
			$tae_cat = tae_cat_name( $tae_lead );
			$tae_who = tae_guest( $tae_lead );
			?>
			<div class="series-grid" style="margin-top:clamp(28px,3.4vw,48px)">
				<div class="series-left">
					<div class="f-meta lab">
						<?php if ( $tae_cat ) : ?>
							<span class="b"><?php echo esc_html( $tae_cat ); ?></span>
						<?php endif; ?>
						<?php echo tae_time( $tae_lead, 'M j, Y' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<h3 class="h2"><a href="<?php echo esc_url( tae_destination( $tae_lead ) ); ?>"><?php echo esc_html( get_the_title( $tae_lead ) ); ?></a></h3>
					<?php if ( $tae_who ) : ?>
						<p class="stand"><?php echo esc_html( $tae_who ); ?></p>
					<?php endif; ?>
					<a href="<?php echo esc_url( tae_destination( $tae_lead ) ); ?>" class="ln">Watch the interview <?php echo tae_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
				<div class="series-right">
					<img src="<?php echo esc_url( $tae_teaser_image ); ?>" alt="" loading="lazy" decoding="async">
					<?php if ( $tae_teaser_caption ) : ?>
						<span class="soon-cap lab"><?php echo esc_html( $tae_teaser_caption ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		<?php else : ?>
			<ul class="recent-row">
				<?php foreach ( array_slice( $posts, 0, 3 ) as $tae_post ) : ?>
					<?php echo tae_template( 'home-recent-item', array( 'item' => $tae_post ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<a href="/interview-series/" class="btn" style="margin-top:clamp(28px,3.2vw,44px)">See every interview</a>
	</div>
</section>
