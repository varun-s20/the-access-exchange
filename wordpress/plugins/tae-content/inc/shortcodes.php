<?php
/**
 * Two shortcodes, dispatched on a view attribute.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'tae_interviews', 'tae_sc_interviews' );
add_shortcode( 'tae_insights', 'tae_sc_insights' );
add_shortcode( 'tae_coming_soon', 'tae_sc_coming_soon' );

/**
 * [tae_coming_soon until="feature|any" line="" body="" cta="" href=""]
 *
 * The pre-launch treatment. It prints while the site has nothing to show and
 * disappears on its own the moment it does - the handoff asks for a homepage the
 * owner can move past without a developer, and "publish the interview" is the
 * only action that should be needed.
 *
 *   until="feature"  hide once an interview holds the Featured slot. Use where a
 *                    featured module renders directly underneath, so exactly one
 *                    of the two is ever on the page.
 *   until="any"      hide once any interview is published at all.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tae_sc_coming_soon( $atts ) {

	$a = shortcode_atts(
		array(
			'until' => 'feature',
			'line'  => 'First interview coming soon.',
			'body'  => '',
			// CLIENT EDIT (2026-08-20): "Join The Access Exchange" -> "Join The Exchange".
			'cta'   => 'Join The Exchange',
			'href'  => '#join',
		),
		$atts,
		'tae_coming_soon'
	);

	if ( 'any' === $a['until'] ) {
		$counts = wp_count_posts( 'tae_interview' );
		$live   = $counts && ! empty( $counts->publish );
	} else {
		$live = (bool) tae_slot_post( 'feature' );
	}

	if ( $live ) {
		return '';
	}

	return tae_template(
		'coming-soon',
		array(
			'line' => $a['line'],
			'body' => $a['body'],
			'cta'  => $a['cta'],
			'href' => $a['href'],
		)
	);
}

/**
 * [tae_interviews view="archive|cover|featured|home" count="12" category="" heading="" divider=""]
 *
 * wall, rail and watch went in Phase 7 with their CSS and templates - no page in
 * the approved architecture used them. Re-adding a view means re-adding all three.
 *
 * CLIENT EDIT (2026-08-20): added view="home", replacing the old pairing of
 * [tae_coming_soon until="feature"] + [tae_interviews view="featured"] on the
 * home page. That pairing only knew two states - a featured pick, or
 * nothing - so "interviews are already published, just none of them
 * featured yet" fell through to the same "first interview coming soon" card
 * as a brand-new site with nothing published at all, which is not true and
 * reads oddly once the archive is not empty. See the 'home' case below.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tae_sc_interviews( $atts ) {

	$a = shortcode_atts(
		array(
			'view'     => 'archive',
			'count'    => '',
			'category' => '',
			'heading'  => '',
			// The little centred rule-label above a section. It belongs to the
			// shortcode rather than the page so that an empty module takes its
			// own label away with it - a divider reading "The archive" with
			// nothing under it is worse than no archive.
			'divider'  => '',
		),
		$atts,
		'tae_interviews'
	);

	$view  = sanitize_key( $a['view'] );
	$count = '' === $a['count'] ? 12 : max( 1, (int) $a['count'] );

	switch ( $view ) {

		case 'archive':
			$category = sanitize_title( $a['category'] );
			$query    = tae_interviews(
				array(
					'posts_per_page' => $count,
					'category'       => $category,
				)
			);
			// Before the first interview is published this would otherwise print
			// a heading, a row of empty filter chips and a Subscribe button over
			// nothing. The page's own launch-state block covers that position.
			if ( ! $query->posts ) {
				return '';
			}
			tae_enqueue_archive_js();
			$instance = tae_instance( $view );
			$filter   = tae_filter_markup( $view, $instance, $category );
			return tae_divider( $a['divider'] ) . tae_template(
				'interview-' . $view,
				array(
					'query'    => $query,
					'filter'   => $filter,
					'instance' => $instance,
					'count'    => $count,
					'category' => $category,
					'heading'  => $a['heading'],
				)
			);

		case 'cover':
			return tae_template(
				'interview-cover',
				array(
					'story'  => tae_slot_post( 'cover' ),
					'minors' => tae_slot_posts( 'minor', 2 ),
					'rail'   => tae_most_watched( 5 ),
				)
			);

		case 'featured':
			$post = tae_slot_post( 'feature' );
			return $post ? tae_template( 'interview-featured', array( 'post' => $post ) ) : '';

		// CLIENT EDIT START (2026-08-20), 3rd pass - the home page §03 state
		// machine. Three states:
		//   1. a featured pick exists     -> the featured module, self-
		//                                    contained, full-bleed.
		//   2. interviews exist, none is
		//      featured                   -> templates/home-recent.php.
		//      Behaviour depends on the sneak-peek image (Interviews ->
		//      Links): set -> 1 most recent interview beside that image;
		//      empty -> up to 3 most recent interviews in a row instead,
		//      using the width the image would have taken.
		//   3. nothing published at all   -> templates/home-launch.php - the
		//      "First interview coming soon" card, with the same sneak-peek
		//      image if one is set.
		// Earlier passes: a version of state 2 existed, got removed because
		// it didn't match what was wanted ("didn't want the speaker example
		// next to it") - that one was unconditional and showed 2 interviews
		// with no image ever. This is a different, more specific design:
		// the sneak-peek image toggles between showing 1 interview + that
		// image, or 3 interviews with no image. Both home-launch.php and
		// home-recent.php print their own "Go beyond the biography" heading -
		// there is no shared static heading in wordpress/index.html for §03.
		case 'home':
			$featured = tae_slot_post( 'feature' );
			if ( $featured ) {
				return tae_template( 'interview-featured', array( 'post' => $featured ) );
			}

			$teaser_image   = tae_option( 'tae_teaser_image' );
			$teaser_caption = tae_option( 'tae_teaser_caption' );

			$recent = tae_interviews(
				array(
					'posts_per_page' => $teaser_image ? 1 : 3,
					'no_found_rows'  => true,
				)
			)->posts;

			if ( $recent ) {
				return tae_template(
					'home-recent',
					array(
						'posts'          => $recent,
						'teaser_image'   => $teaser_image,
						'teaser_caption' => $teaser_caption,
					)
				);
			}

			return tae_template(
				'home-launch',
				array(
					'line'           => 'First interview coming soon.',
					'body'           => 'Join The Exchange and you will hear about it before it goes out.',
					'cta'            => 'Join The Exchange',
					'href'           => '#join',
					'teaser_image'   => $teaser_image,
					'teaser_caption' => $teaser_caption,
				)
			);
		// CLIENT EDIT END

	}

	return '';
}

/**
 * [tae_insights view="stream" count="14"]
 *
 * view="start" went with templates/insights-start.php: the five numbered links
 * it printed were never placed on a page, so "Show in Start here" was a checkbox
 * the owner could tick to no effect anywhere on the site.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tae_sc_insights( $atts ) {

	$a = shortcode_atts(
		array(
			'view'    => 'stream',
			'count'   => '',
			'heading' => '',
			// "no" drops the h1, the tally and the search field. The stream began
			// life as a whole page; embedded as a section on /interview-series/ it
			// must not bring a second <h1> or a second search box with it.
			'search'  => 'yes',
			// Section furniture for the embedded case. Printed by the shortcode so
			// it disappears with the stream before the first takeaway is published.
			'divider' => '',
			'lede'    => '',
		),
		$atts,
		'tae_insights'
	);

	$view = sanitize_key( $a['view'] );

	if ( 'stream' === $view ) {
		// The whole stream renders at once: global.js §09 caches the card list on
		// boot and filters the DOM, so there is nothing to paginate against - and
		// the sidebar counters can only be honest about what is actually there.
		//
		// Hence a cap rather than -1. Everything rendered is searchable, filterable
		// and counted; anything past the cap is invisible to all three - it still
		// has its own page, it just never appears in this section.
		//
		// 150, raised from 60. At roughly two takeaways per interview, 60 is about
		// seven months of publishing, which is close enough to be a launch-year
		// problem rather than a someday one. 150 is a few years, and the tiles are
		// small. When even that is outgrown the answer is paging this section, not
		// another raise - past a few hundred the page weight starts to matter more
		// than the completeness does.
		$query = tae_stream(
			array(
				'posts_per_page' => '' === $a['count'] ? 150 : max( 1, (int) $a['count'] ),
			)
		);
		if ( ! $query->posts ) {
			return '';
		}
		// The divider is section furniture and belongs to the stream whatever the
		// stream looks like; it used to be dropped silently unless search="no",
		// so [tae_insights divider="Takeaways"] on its own printed nothing.
		// Only the heading and lede are conditional, and only because the stream
		// prints its own h1 when the search header is on - two titles, not one.
		$headed = 'no' === $a['search'] && ( $a['heading'] || $a['lede'] );
		return tae_divider( $a['divider'] ) . ( $headed ? tae_stream_head( $a ) : '' ) . tae_template(
			'insights-stream',
			array(
				'query'    => $query,
				'instance' => tae_instance( 'stream' ),
				'heading'  => $a['heading'],
				'search'   => 'no' !== $a['search'],
			)
		);
	}

	return '';
}

/**
 * Register and enqueue the load-more script, once.
 *
 * No dependency on 'tae-global': README-WORDPRESS.md §7b documents a fallback
 * route where global.js is pasted into the footer widget and no handle exists.
 * Declaring the dependency would silently drop this script on those installs.
 */
function tae_enqueue_archive_js() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	wp_enqueue_script(
		'tae-archive',
		TAE_URL . 'assets/tae-archive.js',
		array(),
		TAE_VER,
		true
	);

	// No nonce. inc/ajax.php explains why: this endpoint is a public read, and a
	// nonce printed into cacheable HTML expires inside the cache and takes the
	// load-more button down with it.
	wp_localize_script(
		'tae-archive',
		'TAE_ARCHIVE',
		array(
			'url' => admin_url( 'admin-ajax.php' ),
		)
	);
}

/**
 * The centred rule-label that introduces a section.
 *
 * @param string $label Label text, or '' for nothing.
 * @return string
 */
function tae_divider( $label ) {
	return $label
		? '<div class="divider lab">' . esc_html( $label ) . '</div>'
		: '';
}

/**
 * Heading furniture for a stream embedded as a section rather than a page.
 *
 * Lives with the stream so that a page with no takeaways yet shows neither the
 * heading nor a gap where one would be.
 *
 * @param array $a Shortcode attributes.
 * @return string
 */
function tae_stream_head( $a ) {
	// The divider is printed by the caller, so that it survives when the heading
	// and lede do not apply.
	$out = '<div class="shell">';

	if ( $a['heading'] ) {
		$out .= '<h2 id="takeaways-h" class="take-h">' . esc_html( $a['heading'] ) . '</h2>';
	}
	if ( $a['lede'] ) {
		$out .= '<p class="lede take-lede">' . esc_html( $a['lede'] ) . '</p>';
	}

	return $out . '</div>';
}
