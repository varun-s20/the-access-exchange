<?php
/**
 * One home wall card. Shared by the wall template and the AJAX handler.
 *
 * @var WP_Post $item
 * @var int     $index Zero-based position, for the staggered reveal delay.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_duration = (string) get_post_meta( $item->ID, 'tae_duration', true );
$tae_name     = tae_cat_name( $item );
$tae_who      = tae_guest( $item );
?>
<a class="card" href="/interview-series/" data-cat="<?php echo esc_attr( tae_cat_slug( $item ) ); ?>" data-rv<?php echo tae_delay( $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ph">
		<?php echo tae_img( $item, 'tae-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $tae_duration ) : ?>
			<span class="dur lab"><?php echo esc_html( $tae_duration ); ?></span>
		<?php endif; ?>
	</div>
	<div class="m lab">
		<span><?php echo esc_html( $tae_name ); ?></span>
		<span><?php echo esc_html( get_the_date( 'M j', $item ) ); ?></span>
	</div>
	<h3><?php echo esc_html( get_the_title( $item ) ); ?></h3>
	<?php if ( $tae_who ) : ?>
		<p class="g"><?php echo esc_html( $tae_who ); ?></p>
	<?php endif; ?>
</a>
