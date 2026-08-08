<?php
/**
 * The pre-launch card. Printed by [tae_coming_soon] while the site has no
 * interview to show, and gone the moment it does.
 *
 * Deliberately not a placeholder for the featured module - it does not pretend
 * to be an episode. It states where things are and offers the one action that
 * is available before launch.
 *
 * @var string $line Headline.
 * @var string $body Optional supporting sentence.
 * @var string $cta  Button label.
 * @var string $href Button target.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;
?>
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
