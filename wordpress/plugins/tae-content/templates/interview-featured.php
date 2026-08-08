<?php
/**
 * Home §04 — the featured interview, with chapter jumps.
 *
 * Replaces index.html lines 125-150.
 *
 * @var WP_Post $post
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_stand    = (string) get_post_meta( $post->ID, 'tae_standfirst', true );
$tae_duration = (string) get_post_meta( $post->ID, 'tae_duration', true );
$tae_cat      = tae_cat_name( $post );
$tae_who      = tae_guest( $post );
$tae_chapters = tae_chapters( $post->ID );

/**
 * The cover prints the standfirst on its own ("The three promotions that
 * mattered…"); the home feature leads with the guest ("Amara Osei, Staff
 * Engineer, on the three promotions that mattered…"). One field, two readings.
 *
 * ponytail: lowercase the join only when the standfirst opens with a word that is
 * safe to lowercase. Anything else — a name, an acronym — is left alone and joined
 * with an em dash instead. Give the standfirst its own field if this ever needs to
 * be smarter than a word list.
 */
$tae_lead = $tae_stand;
if ( $tae_who && $tae_stand ) {
	$tae_first = strtok( $tae_stand, ' ' );
	$tae_safe  = array( 'The', 'A', 'An', 'Her', 'His', 'Their', 'She', 'He', 'They', 'It', 'What', 'How', 'Why', 'Three', 'Two', 'Five' );

	$tae_lead = in_array( $tae_first, $tae_safe, true )
		? $tae_who . ', on ' . lcfirst( $tae_stand )
		: $tae_who . ' — ' . $tae_stand;
}
?>
<section class="feature" aria-labelledby="feature-h">
	<div class="ph">
		<?php echo tae_img( $post, 'tae-feature' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<div class="feature-in">
		<div class="feature-panel">
			<div class="tag lab"><span class="s">04</span><span>Featured interview</span></div>
			<div class="f-meta lab">
				<?php if ( $tae_cat ) : ?>
					<span class="b"><?php echo esc_html( $tae_cat ); ?></span>
				<?php endif; ?>
				<?php echo tae_time( $post, 'M j, Y' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $tae_duration ) : ?>
					<span class="dot"></span><span><?php echo esc_html( $tae_duration ); ?></span>
				<?php endif; ?>
			</div>
			<h2 id="feature-h"><?php echo esc_html( get_the_title( $post ) ); ?></h2>
			<?php if ( $tae_lead ) : ?>
				<p class="stand"><?php echo esc_html( $tae_lead ); ?></p>
			<?php endif; ?>
			<a href="/interview-series/" class="ln">Watch the interview <?php echo tae_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>

			<?php if ( $tae_chapters ) : ?>
				<div class="chapters">
					<span class="cap lab">In this conversation</span>
					<ul>
						<?php foreach ( $tae_chapters as $tae_chapter ) : ?>
							<li>
								<a href="/interview-series/">
									<span class="t lab"><?php echo esc_html( $tae_chapter['time'] ); ?></span>
									<span class="c"><?php echo esc_html( $tae_chapter['label'] ); ?></span>
									<?php echo tae_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
