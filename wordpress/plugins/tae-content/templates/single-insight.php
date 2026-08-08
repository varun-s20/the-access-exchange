<?php
/**
 * Single insight — a real page, for everybody.
 *
 * The prototype never designed one, and it did not need to: the About broadsheet
 * at global.css:1405 is already a long-form article layout — .sheet, .sheet-head,
 * .spread, .copy, .dateline, .plate, .ends — so this borrows the .tae-about
 * wrapper wholesale. "Read next" borrows .tae-insights .stream in a second
 * wrapper, which is exactly how the page-scoped token system is meant to be used.
 *
 * The page renders for everyone, whether or not an article has been written. What
 * varies is what fills the reading column:
 *
 *   body written  → the article
 *   no body yet   → the dek, and a clear route to wherever this piece points
 *
 * Either way the reader gets a headline, a date, an image, three things to read
 * next and two ways to get involved. Nothing is invented to fill space, and the
 * page never dead-ends.
 *
 * A bodiless insight is still noindex and still out of the sitemap — see
 * inc/thin-pages.php. That is a search-engine decision, not a visitor-facing one.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$tae_post    = get_post();
	$tae_written = tae_has_body( $tae_post );
	$tae_byline  = (string) get_post_meta( get_the_ID(), 'tae_byline', true );
	$tae_dek     = (string) get_post_meta( get_the_ID(), 'tae_dek', true );
	$tae_link    = (string) get_post_meta( get_the_ID(), 'tae_link', true );
	$tae_topic   = tae_topic_name( $tae_post );

	$tae_dateline = array_filter( array( $tae_byline, $tae_topic, get_the_date( 'F Y' ) ) );

	$tae_onward = tae_onward_label( $tae_link );
	$tae_related = tae_related( $tae_post, 3 );
	?>
	<style>
		/* .tae-about .copy sets columns:2, which is right for six known paragraphs
		   and wrong for an article of unknown length. .spread reserves a 232px
		   margin column for editorial notes this page does not have. */
		.tae-about.tae-single .copy{columns:1}
		.tae-about.tae-single .spread{grid-template-columns:minmax(0,1fr)}
	</style>

	<div class="tae tae-about tae-single">

		<section class="sheet">
			<div class="shell">
				<div class="sheet-head">
					<h1><?php the_title(); ?></h1>
					<?php if ( $tae_dateline ) : ?>
						<p class="dateline lab"><?php echo esc_html( implode( ' · ', $tae_dateline ) ); ?></p>
					<?php endif; ?>
				</div>

				<div class="spread">
					<div class="copy">
						<?php if ( $tae_written ) : ?>
							<?php the_content(); ?>
						<?php elseif ( $tae_dek ) : ?>
							<p><?php echo esc_html( $tae_dek ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $tae_link ) : ?>
					<?php // Shown whether or not an article has been written — a finished piece can still point somewhere next. ?>
					<div class="ends">
						<a href="<?php echo esc_url( $tae_link ); ?>" class="btn"><?php echo esc_html( $tae_onward ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( has_post_thumbnail() ) : ?>
			<?php $tae_caption = wp_get_attachment_caption( get_post_thumbnail_id() ); ?>
			<figure class="plate">
				<?php
				the_post_thumbnail(
					'tae-lead',
					array(
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>
				<?php if ( $tae_caption ) : ?>
					<figcaption class="lab"><?php echo esc_html( $tae_caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<section class="facts" aria-label="Get involved">
			<div class="shell">
				<div class="ends">
					<a href="/insights/" class="btn btn--out">All insights</a>
					<a href="/interview-series/#be-a-guest" class="btn btn--out">Be a guest</a>
					<a href="/university-partnerships/" class="btn btn--out">Partner with us</a>
				</div>
			</div>
		</section>

	</div>

	<?php if ( $tae_related ) : ?>
		<?php // A second page wrapper, so the stream tiles get their own tokens without leaking into the article above. ?>
		<div class="tae tae-insights">
			<section class="body" aria-labelledby="tae-next">
				<div class="shell">
					<p class="lab dim" id="tae-next" style="margin-bottom:clamp(18px,2vw,28px)">Read next</p>
					<div class="stream">
						<?php
						foreach ( $tae_related as $tae_item ) {
							echo tae_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'insights-stream-item',
								array(
									'item' => $tae_item,
									'lead' => false,
								)
							);
						}
						?>
					</div>
				</div>
			</section>
		</div>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
