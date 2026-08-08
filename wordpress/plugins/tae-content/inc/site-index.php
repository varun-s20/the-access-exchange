<?php
/**
 * The site-search index.
 *
 * global.js:100 declares SITE_INDEX as a JavaScript literal, and eight of its rows
 * are interview titles. Publishing an interview therefore meant hand-editing a .js
 * file, with no failure signal when somebody forgot — search just went quietly
 * stale. This builds the same array from real posts.
 *
 * Requires one edit to global.js, and only one:
 *
 *     var SITE_INDEX = window.TAE_INDEX || [ … existing literal, unchanged … ];
 *
 * Keeping the literal as the fallback means search still works if this plugin is
 * deactivated.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'tae_print_site_index', 21 );

/**
 * Publish the index before global.js runs.
 */
function tae_print_site_index() {

	$js = 'window.TAE_INDEX=' . wp_json_encode( tae_site_index_rows() ) . ';';

	// Preferred route: the child theme registered the handle at priority 20.
	if ( wp_script_is( 'tae-global', 'registered' ) ) {
		wp_add_inline_script( 'tae-global', $js, 'before' );
		return;
	}

	// Fallback for the footer-widget route in README-WORDPRESS.md §7b, where
	// global.js is pasted into the footer and no handle exists. wp_head runs
	// before the footer, so the assignment still lands first.
	add_action(
		'wp_head',
		function () use ( $js ) {
			printf( "<script>%s</script>\n", $js ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output.
		},
		5
	);
}

/**
 * Static pages and anchors, then every published interview and insight.
 *
 * @return array<int,array{t:string,d:string,u:string}>
 */
function tae_site_index_rows() {

	$rows = array(
		array(
			't' => 'Home',
			'd' => 'The platform and its two vehicles',
			'u' => '/',
		),
		array(
			't' => 'The Interview Series',
			'd' => 'Format, archive and how to be a guest',
			'u' => '/interview-series/',
		),
		array(
			't' => 'University Partnerships',
			'd' => 'How a partnership is scoped, what it costs',
			'u' => '/university-partnerships/',
		),
		array(
			't' => 'Insights',
			'd' => 'Everything we have published',
			'u' => '/insights/',
		),
		array(
			't' => 'About',
			'd' => 'Why this exists and what we refuse to do',
			'u' => '/about/',
		),
		array(
			't' => 'Contact',
			'd' => 'Guests, universities, press or anything else',
			'u' => '/contact/',
		),
		array(
			't' => 'Be a guest',
			'd' => 'Guest inquiry form',
			'u' => '/interview-series/#be-a-guest',
		),
		array(
			't' => 'Partner with us',
			'd' => 'University inquiry form',
			'u' => '/university-partnerships/#enquire',
		),
		array(
			't' => 'The archive',
			'd' => 'Every episode in full',
			'u' => '/interview-series/#episodes',
		),
		array(
			't' => 'A term, mapped',
			'd' => 'What a partnership looks like across twelve weeks',
			'u' => '/university-partnerships/#s4',
		),
		array(
			't' => 'Cost and commitment',
			'd' => 'What a partnership costs, lead times, procurement',
			'u' => '/university-partnerships/#s7',
		),
	);

	$posts = get_posts(
		array(
			'post_type'        => array( 'tae_interview', 'tae_insight' ),
			'post_status'      => 'publish',
			'posts_per_page'   => 100,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);

	foreach ( $posts as $post ) {
		$rows[] = array(
			't' => get_the_title( $post ),
			'd' => 'tae_interview' === $post->post_type
				? tae_guest( $post )
				: (string) get_post_meta( $post->ID, 'tae_byline', true ),
			'u' => tae_destination( $post ),
		);
	}

	return $rows;
}
