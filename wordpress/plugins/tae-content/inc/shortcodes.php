<?php
/**
 * Two shortcodes, dispatched on a view attribute.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'tae_interviews', 'tae_sc_interviews' );
add_shortcode( 'tae_insights', 'tae_sc_insights' );

/**
 * [tae_interviews view="wall|archive|cover|featured|rail|watch" count="12" category="" heading=""]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tae_sc_interviews( $atts ) {

	$a = shortcode_atts(
		array(
			'view'     => 'wall',
			'count'    => '',
			'category' => '',
			'heading'  => '',
		),
		$atts,
		'tae_interviews'
	);

	$view  = sanitize_key( $a['view'] );
	$count = '' === $a['count'] ? 12 : max( 1, (int) $a['count'] );

	switch ( $view ) {

		case 'wall':
		case 'archive':
			tae_enqueue_archive_js();
			$instance = tae_instance( $view );
			$filter   = tae_filter_markup( $view, $instance );
			$query    = tae_interviews(
				array(
					'posts_per_page' => $count,
					'category'       => sanitize_title( $a['category'] ),
				)
			);
			return tae_template(
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

		case 'watch':
			$post = tae_slot_post( 'watch' );
			return $post ? tae_template( 'interview-watch', array( 'post' => $post ) ) : '';

		case 'rail':
			$query = tae_interviews(
				array(
					'posts_per_page' => '' === $a['count'] ? 5 : $count,
					'no_found_rows'  => true,
				)
			);
			return tae_template( 'interview-rail', array( 'query' => $query ) );
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
		// boot and filters the DOM, so there is nothing to paginate against — and
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
		return tae_template(
			'insights-stream',
			array(
				'query'    => $query,
				'instance' => tae_instance( 'stream' ),
				'heading'  => $a['heading'],
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
