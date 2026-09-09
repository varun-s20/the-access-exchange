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
	$tae_children  = tae_children( $tae_post, 6 );
	$tae_related   = tae_related( $tae_post, 3 );

	// "Chief Operating Officer, Northwind" - one line, however many parts exist.
	$tae_credit = implode( ', ', array_filter( array( $tae_role, $tae_org ) ) );

	$tae_share_url   = rawurlencode( get_permalink() );
	$tae_share_title = rawurlencode( get_the_title() );
	?>

<?php // <main>, not <div>: every Elementor page sets its container's HTML tag to main, and a generated page with no main landmark is the odd one out for anyone navigating by landmark. The class does the styling either way. ?>
<main class="tae tae-episode">

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
						<a class="s-li" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $tae_share_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" rel="noopener nofollow" target="_blank" aria-label="Share on LinkedIn" title="Share on LinkedIn"><svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.59 0 4.26 2.37 4.26 5.45v6.29zM5.34 7.43a2.07 2.07 0 1 1 0-4.13 2.07 2.07 0 0 1 0 4.13zm1.78 13.02H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z"/></svg></a>
						<a class="s-x" href="https://x.com/intent/tweet?url=<?php echo $tae_share_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>&amp;text=<?php echo $tae_share_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" rel="noopener nofollow" target="_blank" aria-label="Share on X" title="Share on X"><svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.41l-5.8-7.58-6.64 7.58H.46l8.6-9.83L0 1.15h7.59l5.24 6.93 6.07-6.93zm-1.29 19.5h2.04L6.49 3.24H4.3l13.31 17.41z"/></svg></a>
						<a class="s-em" href="mailto:?subject=<?php echo $tae_share_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>&amp;body=<?php echo $tae_share_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" aria-label="Share by email" title="Share by email"><svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 7.13L19.6 7H4.4L12 12.13zM4 8.5V17h16V8.5l-7.42 4.98a1 1 0 0 1-1.16 0L4 8.5z"/></svg></a>
						<button type="button" class="ep-copy" data-copy="<?php echo esc_url( get_permalink() ); ?>"><svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7a5 5 0 0 0 0 10h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4a5 5 0 0 0 0-10z"/></svg>Copy link</button>
					</div>
				</section>
			</aside>
		</div>

		<!-- ═══ 03 · WHAT CAME OUT OF IT ════════════════════════════════════
			 Handoff §9, "Related clips / written takeaways". Every takeaway,
			 clip and write-up whose "Came from which interview" points here.

			 Reuses .ep-next wholesale rather than earning its own classes: it is
			 the same object - a labelled rule, then a grid of typed links - and a
			 second set of rules would be two things to keep in step for no visible
			 difference. -->
		<?php if ( $tae_children ) : ?>
			<section class="ep-next" aria-labelledby="from-h">
				<div class="shell">
					<h2 id="from-h" class="lab">From this interview</h2>
					<ul class="ep-next-list">
						<?php foreach ( $tae_children as $tae_child ) : ?>
							<li>
								<a href="<?php echo esc_url( tae_destination( $tae_child ) ); ?>">
									<?php // CLIENT EDIT (2026-09-04): the card was type-only. ?>
									<?php $tae_child_img = tae_img( $tae_child, 'tae-tile' ); ?>
									<?php if ( $tae_child_img ) : ?>
										<span class="ph"><?php echo $tae_child_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php endif; ?>
									<span class="k lab"><?php echo esc_html( tae_topic_name( $tae_child ) ? tae_topic_name( $tae_child ) : 'Takeaway' ); ?></span>
									<span class="t"><?php echo esc_html( get_the_title( $tae_child ) ); ?></span>
									<?php $tae_child_by = (string) get_post_meta( $tae_child->ID, 'tae_byline', true ); ?>
									<?php if ( $tae_child_by ) : ?>
										<span class="w"><?php echo esc_html( $tae_child_by ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<!-- ═══ 04 · ONWARD ═════════════════════════════════════════════════ -->
		<?php if ( $tae_related ) : ?>
			<section class="ep-next" aria-labelledby="next-h">
				<div class="shell">
					<h2 id="next-h" class="lab">Carry on from here</h2>
					<ul class="ep-next-list">
						<?php foreach ( $tae_related as $tae_item ) : ?>
							<li>
								<a href="<?php echo esc_url( tae_destination( $tae_item ) ); ?>">
									<?php /* CLIENT EDIT (2026-09-04): "show the proper interview with the
									   thumbnail". A still, not a tae_vid() facade - this card navigates to
									   the interview, and a play button here would promise playback that
									   does not happen.
									
									   BLOCK COMMENT, NOT //. A // comment ends at the newline, so the
									   lines under it become PHP code and the page fatals. */ ?>
									<?php $tae_item_img = tae_img( $tae_item, 'tae-tile' ); ?>
									<?php if ( $tae_item_img ) : ?>
										<span class="ph"><?php echo $tae_item_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php endif; ?>
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
				<?php // CLIENT EDIT (2026-08-20): "Join The Access Exchange" -> "Join The Exchange". ?>
				<a href="<?php echo esc_url( tae_option( 'tae_join_url' ) ); ?>" class="btn">Join The Exchange</a>
			</div>
		</section>
	</article>

</main>

	<?php
endwhile;

get_footer();
