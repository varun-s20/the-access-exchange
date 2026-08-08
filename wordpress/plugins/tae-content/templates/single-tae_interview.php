<?php
/**
 * The episode page - /interviews/{slug}/
 *
 * Every field the handoff's episode template lists (§9), in the order it lists
 * them: guest name, professional title, organization, portrait, episode title,
 * hook, the YouTube embed, key takeaways, chapters, related clips and written
 * takeaways, social sharing, the related interview, and the Join CTA.
 *
 * Interviews were slotless fragments until this existed - cards linked at an
 * archive anchor and the video played inline. Now each conversation is a page
 * that can be linked to, shared and indexed.
 *
 * The video keeps the facade behaviour from global.js §06: the poster is ours
 * and nothing is requested from youtube.com until someone presses play.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$tae_id        = get_the_ID();
	$tae_post      = get_post();
	$tae_name      = (string) get_post_meta( $tae_id, 'tae_guest_name', true );
	$tae_role      = (string) get_post_meta( $tae_id, 'tae_guest_role', true );
	$tae_org       = (string) get_post_meta( $tae_id, 'tae_org', true );
	$tae_stand     = (string) get_post_meta( $tae_id, 'tae_standfirst', true );
	$tae_duration  = (string) get_post_meta( $tae_id, 'tae_duration', true );
	$tae_cat       = tae_cat_name( $tae_post );
	$tae_takeaways = tae_takeaways( $tae_id );
	$tae_chapters  = tae_chapters( $tae_id );
	$tae_related   = tae_related( $tae_post, 3 );

	// "Chief Operating Officer, Northwind" - one line, however many parts exist.
	$tae_credit = implode( ', ', array_filter( array( $tae_role, $tae_org ) ) );

	$tae_share_url   = rawurlencode( get_permalink() );
	$tae_share_title = rawurlencode( get_the_title() );
	?>

<div class="tae tae-episode">

	<article>
		<!-- ═══ 01 · MASTHEAD ═══════════════════════════════════════════════ -->
		<header class="ep-head">
			<div class="shell">
				<p class="ep-crumb lab">
					<a href="/interview-series/">Interview Series</a>
					<?php if ( $tae_cat ) : ?>
						<span class="dot"></span><span><?php echo esc_html( $tae_cat ); ?></span>
					<?php endif; ?>
				</p>

				<h1><?php the_title(); ?></h1>

				<?php if ( $tae_stand ) : ?>
					<p class="ep-stand"><?php echo esc_html( $tae_stand ); ?></p>
				<?php endif; ?>

				<?php if ( $tae_name ) : ?>
					<p class="ep-guest">
						<span class="ep-guest-name"><?php echo esc_html( $tae_name ); ?></span>
						<?php if ( $tae_credit ) : ?>
							<span class="ep-guest-role"><?php echo esc_html( $tae_credit ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<p class="ep-meta lab">
					<?php echo tae_episode( $tae_post, 'Episode' ) ? esc_html( tae_episode( $tae_post, 'Episode' ) ) . '<span class="dot"></span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo tae_time( $tae_post, 'M j, Y' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( $tae_duration ) : ?>
						<span class="dot"></span><span><?php echo esc_html( $tae_duration ); ?></span>
					<?php endif; ?>
				</p>
			</div>
		</header>

		<!-- ═══ 02 · THE CONVERSATION ═══════════════════════════════════════ -->
		<div class="shell">
			<div class="ep-video">
				<?php echo tae_vid( $tae_post, 'ep-vid', 'tae-cover', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<div class="shell ep-grid">

			<div class="ep-main">
				<?php if ( get_the_content() ) : ?>
					<div class="ep-body"><?php the_content(); ?></div>
				<?php endif; ?>

				<?php if ( $tae_takeaways ) : ?>
					<section class="ep-take" aria-labelledby="take-h">
						<h2 id="take-h">What we took from it</h2>
						<ol>
							<?php foreach ( $tae_takeaways as $tae_i => $tae_line ) : ?>
								<li>
									<span class="n lab"><?php echo esc_html( str_pad( (string) ( $tae_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<p><?php echo esc_html( $tae_line ); ?></p>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>
			</div>

			<aside class="ep-side">
				<?php if ( $tae_chapters ) : ?>
					<section class="ep-chapters" id="chapters" aria-labelledby="chap-h">
						<h2 id="chap-h" class="lab">In this conversation</h2>
						<ul>
							<?php foreach ( $tae_chapters as $tae_chapter ) : ?>
								<li>
									<?php // data-t is read by global.js §06, which seeks the player once it exists. ?>
									<button type="button" class="ep-chapter" data-t="<?php echo esc_attr( $tae_chapter['time'] ); ?>">
										<span class="t lab"><?php echo esc_html( $tae_chapter['time'] ); ?></span>
										<span class="c"><?php echo esc_html( $tae_chapter['label'] ); ?></span>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<section class="ep-share" aria-labelledby="share-h">
					<h2 id="share-h" class="lab">Share this</h2>
					<div class="ep-share-row">
						<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $tae_share_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" rel="noopener nofollow" target="_blank">LinkedIn</a>
						<a href="https://x.com/intent/tweet?url=<?php echo $tae_share_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>&amp;text=<?php echo $tae_share_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" rel="noopener nofollow" target="_blank">X</a>
						<a href="mailto:?subject=<?php echo $tae_share_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>&amp;body=<?php echo $tae_share_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">Email</a>
						<button type="button" class="ep-copy" data-copy="<?php echo esc_url( get_permalink() ); ?>">Copy link</button>
					</div>
				</section>
			</aside>
		</div>

		<!-- ═══ 03 · ONWARD ═════════════════════════════════════════════════ -->
		<?php if ( $tae_related ) : ?>
			<section class="ep-next" aria-labelledby="next-h">
				<div class="shell">
					<h2 id="next-h" class="lab">Carry on from here</h2>
					<ul class="ep-next-list">
						<?php foreach ( $tae_related as $tae_item ) : ?>
							<li>
								<a href="<?php echo esc_url( tae_destination( $tae_item ) ); ?>">
									<span class="k lab"><?php echo esc_html( 'tae_interview' === $tae_item->post_type ? 'Interview' : 'Takeaway' ); ?></span>
									<span class="t"><?php echo esc_html( get_the_title( $tae_item ) ); ?></span>
									<?php if ( tae_guest( $tae_item ) ) : ?>
										<span class="w"><?php echo esc_html( tae_guest( $tae_item ) ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<section class="ep-join" aria-labelledby="join-h">
			<div class="shell">
				<h2 id="join-h">Stay close to what's coming.</h2>
				<p>New interviews. New perspectives. New rooms.</p>
				<a href="/#join" class="btn">Join The Exchange</a>
			</div>
		</section>
	</article>

</div>

	<?php
endwhile;

get_footer();
