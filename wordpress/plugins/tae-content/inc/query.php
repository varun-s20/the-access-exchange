<?php
/**
 * The one place queries are built. The wall, archive, cover, stream and the AJAX
 * handler all want nearly the same query; the ordering and slot rules should be
 * written once.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interviews, newest first, with the Order field able to override.
 *
 * @param array $args Overrides. Recognised extras: 'category' (term slug),
 *                    'slot' (string), 'exclude' (int[]), 'search' (string).
 * @return WP_Query
 */
function tae_interviews( $args = array() ) {

	$category = isset( $args['category'] ) ? $args['category'] : '';
	$slot     = isset( $args['slot'] ) ? $args['slot'] : null;
	$search   = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';
	unset( $args['category'], $args['slot'], $args['search'] );

	// Chronological, newest first. menu_order deliberately does NOT appear here:
	// it means "rank within a curated list" (Most watched, Start here, and which
	// of two claimants wins a hero slot). If it also ordered the archive, ranking
	// five interviews for the Most watched rail would push them to the bottom of
	// every other listing on the site.
	$q = array(
		'post_type'           => 'tae_interview',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
	);

	if ( $category ) {
		$q['tax_query'] = array(
			array(
				'taxonomy' => 'tae_category',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}

	if ( null !== $slot ) {
		$q['meta_key']   = 'tae_slot_' . $slot;
		$q['meta_value'] = '1';
	}

	if ( '' !== $search ) {
		$q['s'] = $search;
	}

	return new WP_Query( array_merge( $q, $args ) );
}

/**
 * Insights and interviews together, for the /insights/ stream.
 *
 * @param array $args Overrides.
 * @return WP_Query
 */
function tae_stream( $args = array() ) {
	return new WP_Query(
		array_merge(
			array(
				'post_type'           => array( 'tae_interview', 'tae_insight' ),
				'post_status'         => 'publish',
				'posts_per_page'      => 14,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
			),
			$args
		)
	);
}

/**
 * The posts holding a given hero slot.
 *
 * If two interviews claim the same slot the lower Order wins; the caller decides
 * how many it wants and the rest are ignored.
 *
 * @param string $slot  Slot key.
 * @param int    $limit How many to return.
 * @return WP_Post[]
 */
function tae_slot_posts( $slot, $limit = 1 ) {
	$q = tae_interviews(
		array(
			'slot'           => $slot,
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		)
	);
	return $q->posts;
}

/**
 * The single post holding a slot, or null.
 *
 * @param string $slot Slot key.
 * @return WP_Post|null
 */
function tae_slot_post( $slot ) {
	$posts = tae_slot_posts( $slot, 1 );
	return $posts ? $posts[0] : null;
}

/**
 * Interviews flagged for the "Most watched" rail.
 *
 * @param int $limit How many.
 * @return WP_Post[]
 */
function tae_most_watched( $limit = 5 ) {
	$q = tae_interviews(
		array(
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
			'meta_key'       => 'tae_most_watched',
			'meta_value'     => '1',
			// Curated sequence, so menu_order leads here.
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		)
	);
	return $q->posts;
}

/**
 * Insights flagged for "Start here".
 *
 * @param int $limit How many.
 * @return WP_Post[]
 */
function tae_start_here( $limit = 5 ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'tae_insight',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'meta_key'       => 'tae_start_here',
			'meta_value'     => '1',
		)
	);
	return $q->posts;
}

/**
 * What to read next after an insight.
 *
 * Insights sharing a topic first, then recent interviews to fill the row. Always
 * returns something as long as anything else is published - an article page that
 * dead-ends is worse than one whose suggestions are only loosely related.
 *
 * @param WP_Post $post  The insight being read.
 * @param int     $limit How many.
 * @return WP_Post[]
 */
function tae_related( $post, $limit = 3 ) {

	$exclude = array( $post->ID );
	$out     = array();

	$terms = get_the_terms( $post->ID, 'tae_topic' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		$q = new WP_Query(
			array(
				'post_type'      => 'tae_insight',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'post__not_in'   => $exclude,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'tax_query'      => array(
					array(
						'taxonomy' => 'tae_topic',
						'field'    => 'slug',
						'terms'    => wp_list_pluck( $terms, 'slug' ),
					),
				),
			)
		);
		$out = $q->posts;
	}

	if ( count( $out ) < $limit ) {
		$q   = tae_interviews(
			array(
				'posts_per_page' => $limit - count( $out ),
				'no_found_rows'  => true,
				'post__not_in'   => array_merge( $exclude, wp_list_pluck( $out, 'ID' ) ),
			)
		);
		$out = array_merge( $out, $q->posts );
	}

	return $out;
}

/**
 * Move the flagged lead tile to the front of the stream.
 *
 * .it--lead spans four of the stream's six grid columns, so it has to be first in
 * the DOM or the grid opens a hole where it should have been.
 *
 * No flag anywhere means the newest item leads, which is what the prototype did.
 * Two flags means the lower Order wins.
 *
 * @param WP_Post[] $posts Stream posts, already in display order.
 * @return WP_Post[]
 */
function tae_lead_first( $posts ) {

	$flagged = array();
	foreach ( $posts as $post ) {
		if ( '1' === (string) get_post_meta( $post->ID, 'tae_lead', true ) ) {
			$flagged[] = $post;
		}
	}

	if ( ! $flagged ) {
		return $posts;
	}

	usort(
		$flagged,
		function ( $a, $b ) {
			return (int) $a->menu_order - (int) $b->menu_order;
		}
	);

	$lead = $flagged[0];
	$rest = array();
	foreach ( $posts as $post ) {
		if ( $post->ID !== $lead->ID ) {
			$rest[] = $post;
		}
	}

	return array_merge( array( $lead ), $rest );
}

// Sidebar counters are tallied from the rendered posts, in templates/insights-stream.php.
// Nothing here counts them: global.js §09 filters the DOM, so a count derived from
// the taxonomy could disagree with what filtering can actually reach.
