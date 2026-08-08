<?php
/**
 * Insights §03 — the five numbered links.
 *
 * Replaces the .five div in insights.html lines 217-223. The .start-head above it
 * is prose and stays in the Elementor widget.
 *
 * @var WP_Post[] $posts
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="five">
	<?php foreach ( $posts as $tae_i => $tae_item ) : ?>
		<a href="<?php echo esc_url( tae_destination( $tae_item ) ); ?>">
			<span class="n"><?php echo esc_html( str_pad( (string) ( $tae_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
			<h3><?php echo esc_html( get_the_title( $tae_item ) ); ?></h3>
			<?php echo tae_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	<?php endforeach; ?>
</div>
