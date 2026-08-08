<?php
/**
 * The site-search index.
 *
 * global.js:100 declares SITE_INDEX as a JavaScript literal, and eight of its rows
 * are interview titles. Publishing an interview therefore meant hand-editing a .js
 * file, with no failure signal when somebody forgot - search just went quietly
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
			'd' => 'The platform and every way into it',
			'u' => '/',
		),
		array(
			't' => 'Interview Series',
			'd' => 'In-depth interviews, takeaways and the archive',
			'u' => '/interview-series/',
		),
		array(
			't' => 'Guests & Partners',
			'd' => 'Guest consideration, sponsorship, nomination',
			'u' => '/guests-partners/',
		),
		array(
			't' => 'Universities & Institutions',
			'd' => 'Leadership talks, discussions, Q&A, custom programmes',
			'u' => '/universities/',
		),
		array(
			't' => 'Experiences',
			'd' => 'Live panels, leadership discussions and events',
			'u' => '/experiences/',
		),
		array(
			't' => 'Coaching',
			'd' => 'Professional coaching, coach training and certification',
			'u' => '/coaching/',
		),
		array(
			't' => 'About',
			'd' => 'Philosophy, mission and the founder story',
			'u' => '/about/',
		),
		array(
			't' => 'Get Involved',
			'd' => 'Every route into The Exchange',
			'u' => '/get-involved/',
		),
		array(
			't' => 'Share your perspective',
			'd' => 'Guest consideration form',
			'u' => '/guests-partners/#guest',
		),
		array(
			't' => 'Partner with The Exchange',
			'd' => 'Corporate sponsorship and partnership form',
			'u' => '/guests-partners/#corporate',
		),
		array(
			't' => 'Bring The Exchange to campus',
			'd' => 'University and institutional engagement form',
			'u' => '/universities/#enquire',
		),
		array(
			't' => 'Explore coaching',
			'd' => 'Professional coaching inquiry',
			'u' => '/coaching/#coaching',
		),
		array(
			't' => 'Explore coach training',
			'd' => 'Coach training and certification inquiry',
			'u' => '/coaching/#training',
		),
		array(
			't' => 'Takeaways',
			'd' => 'Standout ideas and clips from the interviews',
			'u' => '/interview-series/#takeaways',
		),
		array(
			't' => 'The archive',
			'd' => 'Every interview in full',
			'u' => '/interview-series/#episodes',
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
