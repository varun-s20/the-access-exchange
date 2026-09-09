<?php
/**
 * One card in the home §03 "3 recent interviews" row.
 *
 * CLIENT EDIT (2026-08-20): new file, used only by templates/home-recent.php
 * when no sneak-peek image is set. Deliberately lighter than
 * templates/interview-archive-item.php (a still image via tae_img(), not the
 * click-to-load video facade via tae_vid()) - this is a homepage teaser row,
 * not the archive itself.
 *
 * @var WP_Post $item
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_cat = tae_cat_name( $item );
$tae_who = tae_guest( $item );
?>
<li>
	<article class="rec-card">
		<div class="ph"><?php echo tae_img( $item, 'tae-tile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<div class="rec-meta lab">
			<?php if ( $tae_cat ) : ?>
				<span class="b"><?php echo esc_html( $tae_cat ); ?></span>
			<?php endif; ?>
			<?php echo tae_time( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<h3><a href="<?php echo esc_url( tae_destination( $item ) ); ?>"><?php echo esc_html( get_the_title( $item ) ); ?></a></h3>
		<?php if ( $tae_who ) : ?>
			<p class="rec-who"><?php echo esc_html( $tae_who ); ?></p>
		<?php endif; ?>
	</article>
</li>
