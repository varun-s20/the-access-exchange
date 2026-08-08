<?php
/**
 * Home §08 — the guest rail.
 *
 * Replaces index.html lines 326-362 (the .rail div only). The .room-head above it
 * holds the heading and the #railPrev / #railNext buttons; those stay in the
 * Elementor widget, and global.js §08 finds them by ID wherever they are.
 *
 * The id="rail" is what global.js §08 binds its arrows and drag handling to.
 *
 * @var WP_Query $query
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="rail" id="rail">
	<?php foreach ( $query->posts as $tae_item ) : ?>
		<?php
		$tae_name = (string) get_post_meta( $tae_item->ID, 'tae_guest_name', true );
		$tae_role = (string) get_post_meta( $tae_item->ID, 'tae_guest_role', true );
		$tae_line = (string) get_post_meta( $tae_item->ID, 'tae_rail_line', true );
		?>
		<a class="face" href="/interview-series/">
			<div class="ph"><?php echo tae_img( $tae_item, 'tae-rail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<h3><?php echo esc_html( $tae_name ? $tae_name : get_the_title( $tae_item ) ); ?></h3>
			<?php if ( $tae_role ) : ?>
				<span class="role lab"><?php echo esc_html( $tae_role ); ?></span>
			<?php endif; ?>
			<?php if ( $tae_line ) : ?>
				<p><?php echo esc_html( $tae_line ); ?></p>
			<?php endif; ?>
		</a>
	<?php endforeach; ?>

	<a class="face face--cta" href="/interview-series/#be-a-guest">
		<span class="lab">The invitation</span>
		<div>
			<h3>Be a guest.</h3>
			<p>Remote, about an hour, no polished narrative required. The rejections and detours are the useful part.</p>
		</div>
		<span class="ln">Tell us about you <?php echo tae_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	</a>
</div>
