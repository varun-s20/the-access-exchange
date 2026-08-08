<?php
/**
 * One archive tile.
 *
 * Shared by templates/interview-archive.php and the AJAX handler — the initial
 * render and the load-more response must produce identical markup, and one file is
 * the only way to guarantee that.
 *
 * @var WP_Post $item
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_cat  = tae_cat_slug( $item );
$tae_name = tae_cat_name( $item );
$tae_ep   = tae_episode( $item );
$tae_who  = tae_guest( $item );
?>
<li data-cat="<?php echo esc_attr( $tae_cat ); ?>">
	<article class="epc">
		<?php echo tae_vid( $item, 'epc-vid', 'tae-tile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="epc-meta">
			<?php if ( $tae_name ) : ?>
				<span class="epc-tag lab"><?php echo esc_html( $tae_name ); ?></span>
			<?php endif; ?>
			<span class="lab"><?php
				echo $tae_ep ? esc_html( $tae_ep ) . ' &nbsp;·&nbsp; ' : '';
				echo tae_time( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?></span>
		</div>
		<h3><a href="#episodes"><?php echo esc_html( get_the_title( $item ) ); ?></a></h3>
		<?php if ( $tae_who ) : ?>
			<p class="epc-who"><?php echo esc_html( $tae_who ); ?></p>
		<?php endif; ?>
	</article>
</li>
