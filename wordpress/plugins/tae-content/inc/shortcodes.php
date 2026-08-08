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
 * [tae_interviews view="archive|cover|featured" count="12" category="" heading="" divider=""]
 *
 * wall, rail and watch went in Phase 7 with their CSS and templates - no page in
 * the approved architecture used them. Re-adding a view means re-adding all three.
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
			$query = tae_interviews(
				array(
					'posts_per_page' => $count,
					'category'       => sanitize_title( $a['category'] ),
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
			$filter   = tae_filter_markup( $view, $instance );
			return tae_divider( $a['divider'] ) . tae_template(
				'interview-' . $view,
				array(
					'query'    => $query,
					'filter'   => $filter,
					'instance' => $instance,
					'count'    => $count,
					'category' => sanitize_title( $a['category'] ),
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

	}

	return '';
}

/**
 * [tae_insights view="stream|start" count="14"]
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

	if ( 'start' === $view ) {
		$posts = tae_start_here( '' === $a['count'] ? 5 : max( 1, (int) $a['count'] ) );
		return $posts ? tae_template( 'insights-start', array( 'posts' => $posts ) ) : '';
	}

	if ( 'stream' === $view ) {
		// The whole stream renders at once: global.js §09 caches the card list on
		// boot and filters the DOM, so there is nothing to paginate against - and
		// the sidebar counters can only be honest about what is actually there.
		//
		// Hence a cap rather than -1. Everything rendered is searchable, filterable
		// and counted; anything past the cap would be invisible to all three. Raise
		// the number in the shortcode when the archive outgrows it.
		$query = tae_stream(
			array(
				'posts_per_page' => '' === $a['count'] ? 60 : max( 1, (int) $a['count'] ),
			)
		);
		if ( ! $query->posts ) {
			return '';
		}
		$headed = 'no' === $a['search'] && ( $a['divider'] || $a['heading'] || $a['lede'] );
		return ( $headed ? tae_stream_head( $a ) : '' ) . tae_template(
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

	wp_localize_script(
		'tae-archive',
		'TAE_ARCHIVE',
		array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'tae_archive' ),
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
	$out = tae_divider( $a['divider'] ) . '<div class="shell">';

	if ( $a['heading'] ) {
		$out .= '<h2 id="takeaways-h" class="take-h">' . esc_html( $a['heading'] ) . '</h2>';
	}
	if ( $a['lede'] ) {
		$out .= '<p class="lede take-lede">' . esc_html( $a['lede'] ) . '</p>';
	}

	return $out . '</div>';
}
