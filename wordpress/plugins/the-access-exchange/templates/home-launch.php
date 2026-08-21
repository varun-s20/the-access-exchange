<?php
/**
 * Home §03 - the pre-launch state (no interview holds the "Slot · Featured
 * interview" tick yet). templates/interview-featured.php is its counterpart
 * for the other state; this is fully self-contained the same way, printing
 * its own <section>, tag, heading and lede rather than relying on the page
 * to supply them.
 *
 * CLIENT EDIT (2026-08-20): new file, replacing coming-soon.php on the home
 * page specifically. Client wants ONE row here: the heading + the
 * coming-soon copy stacked in a left column, and the sneak-peek image
 * (Interviews -> Links) filling a right column that matches that column's
 * full height - not the heading on its own row with the image below it.
 * coming-soon.php is unchanged and still used as-is by the Interview Series
 * page's own [tae_coming_soon until="any"] call.
 *
 * @var string $line Headline.
 * @var string $body Optional supporting sentence.
 * @var string $cta  Button label.
 * @var string $href Button target.
 * @var string $teaser_image   Sneak-peek image URL, right column.
 * @var string $teaser_caption Optional caption over the sneak-peek image.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_teaser_image   = isset( $teaser_image ) ? trim( (string) $teaser_image ) : '';
$tae_teaser_caption = isset( $teaser_caption ) ? trim( (string) $teaser_caption ) : '';
?>
<section class="series" aria-labelledby="series-h">
	<div class="shell series-grid">
		<div class="series-left">
			<div class="tag lab"><span class="s">03</span><span>Interview Series</span></div>
			<h2 class="h2" id="series-h" data-rv>Go beyond the biography.</h2>
			<p class="lede" data-rv style="--d:80ms">Titles tell us where someone arrived. We're interested in what they learned getting there - the decisions, turning points, lessons, relationships and ideas that shaped the journey.</p>

			<div class="soon">
				<span class="soon-mark lab" aria-hidden="true"></span>
				<p class="soon-line"><?php echo esc_html( $line ); ?></p>
				<?php if ( $body ) : ?>
					<p class="soon-body"><?php echo esc_html( $body ); ?></p>
				<?php endif; ?>
				<?php if ( $cta ) : ?>
					<a href="<?php echo esc_url( $href ); ?>" class="btn"><?php echo esc_html( $cta ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $tae_teaser_image ) : ?>
			<div class="series-right">
				<img src="<?php echo esc_url( $tae_teaser_image ); ?>" alt="" loading="lazy" decoding="async">
				<?php if ( $tae_teaser_caption ) : ?>
					<span class="soon-cap lab"><?php echo esc_html( $tae_teaser_caption ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
