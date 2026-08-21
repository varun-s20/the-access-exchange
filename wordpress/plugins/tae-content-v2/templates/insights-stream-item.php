<?php
/**
 * One stream tile.
 *
 * Two layout variants are derived, never entered:
 *   .it--lead  the first item in the stream
 *   .it--text  any item with no featured image
 *
 * data-topic is what global.js §09 filters on. Interviews always report
 * "interview" so the sidebar's fifth bucket works without a term.
 *
 * @var WP_Post $item
 * @var bool    $lead
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_has_image = has_post_thumbnail( $item->ID );

$tae_classes = 'it';
if ( $lead ) {
	$tae_classes .= ' it--lead';
} elseif ( ! $tae_has_image ) {
	$tae_classes .= ' it--text';
}

$tae_who = 'tae_interview' === $item->post_type
	? tae_guest( $item )
	: (string) get_post_meta( $item->ID, 'tae_byline', true );

$tae_dek = 'tae_interview' === $item->post_type
	? (string) get_post_meta( $item->ID, 'tae_standfirst', true )
	: (string) get_post_meta( $item->ID, 'tae_dek', true );
?>
<a class="<?php echo esc_attr( $tae_classes ); ?>" href="<?php echo esc_url( tae_destination( $item ) ); ?>" data-topic="<?php echo esc_attr( tae_topic_slug( $item ) ); ?>">
	<?php if ( $tae_has_image ) : ?>
		<div class="ph"><?php echo tae_img( $item, $lead ? 'tae-lead' : 'tae-stream', $lead ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>
	<div class="it-in">
		<span class="kind lab"><?php echo esc_html( trim( tae_topic_name( $item ) . ' · ' . get_the_date( 'M j', $item ) ) ); ?></span>
		<?php if ( $lead ) : ?>
			<h2><?php echo esc_html( get_the_title( $item ) ); ?></h2>
			<?php if ( $tae_dek ) : ?>
				<p class="sub"><?php echo esc_html( $tae_dek ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<h3><?php echo esc_html( get_the_title( $item ) ); ?></h3>
		<?php endif; ?>
		<?php if ( $tae_who ) : ?>
			<span class="who"><?php echo esc_html( $tae_who ); ?></span>
		<?php endif; ?>
	</div>
</a>
