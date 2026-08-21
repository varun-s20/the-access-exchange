<?php
/**
 * Insights that have not been written yet.
 *
 * Every insight has a page, and every visitor gets it - logged in or not. An
 * unwritten one is not a stub: templates/single-insight.php gives it a headline, a
 * dateline, the dek, the image, a labelled route to wherever the piece points, and
 * three things to read next. Nothing is invented to fill space, and the page never
 * dead-ends. That is the visitor's side, and it is settled there.
 *
 * What is left here is the search engine's side. Until somebody writes a body, the
 * page is mostly assembled from fields that also appear on the Insights stream, so
 * it is noindexed and kept out of the sitemap - publishing a dozen near-identical
 * URLs on a new domain is a bad first impression to hand a crawler.
 *
 * Write a body and both lift, on their own. There is no switch to remember.
 *
 * If you would rather these were indexed from day one, delete the two filters
 * below. The pages themselves do not change either way.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * Has somebody actually written this one?
 *
 * strip_tags also removes Gutenberg's block comments, so an "empty" block editor
 * post reads as empty here. An image-only body counts as written.
 *
 * @param WP_Post|int $post Insight.
 * @return bool
 */
function tae_has_body( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}

	$raw = (string) $post->post_content;

	if ( false !== stripos( $raw, '<img' ) ) {
		return true;
	}

	return '' !== trim( wp_strip_all_tags( strip_shortcodes( $raw ) ) );
}

add_filter( 'wp_robots', 'tae_noindex_bodiless_insight' );
add_filter( 'wpseo_robots_array', 'tae_noindex_bodiless_insight' );

/**
 * noindex while there is nothing written.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function tae_noindex_bodiless_insight( $robots ) {

	if ( ! is_singular( 'tae_insight' ) ) {
		return $robots;
	}

	$post = get_queried_object();
	if ( $post && ! tae_has_body( $post ) ) {
		unset( $robots['index'] );
		$robots['noindex'] = true;
	}

	return $robots;
}

/**
 * Published insights with nothing written in them.
 *
 * Worked out from the content rather than a saved flag, so it cannot go stale
 * against posts that were imported or edited outside the normal save path.
 *
 * @return int[]
 */
function tae_bodiless_insight_ids() {

	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}

	// 2000, not 500. Past the cap an unwritten takeaway stops being noindexed and
	// starts appearing in search results as a near-empty page - the exact outcome
	// this file exists to prevent, arriving silently. The query returns IDs only,
	// runs once per request, and is worth the headroom.
	$ids   = array();
	$posts = get_posts(
		array(
			'post_type'        => 'tae_insight',
			'post_status'      => 'publish',
			'posts_per_page'   => 2000,
			'suppress_filters' => false,
		)
	);

	foreach ( $posts as $post ) {
		if ( ! tae_has_body( $post ) ) {
			$ids[] = (int) $post->ID;
		}
	}

	return $ids;
}

add_filter( 'wp_sitemaps_posts_query_args', 'tae_sitemap_skip_bodiless', 10, 2 );

/**
 * Core sitemaps.
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type.
 * @return array
 */
function tae_sitemap_skip_bodiless( $args, $post_type ) {

	if ( 'tae_insight' !== $post_type ) {
		return $args;
	}

	$existing            = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
	$args['post__not_in'] = array_merge( $existing, tae_bodiless_insight_ids() );

	return $args;
}

add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'tae_yoast_sitemap_skip_bodiless' );

/**
 * Yoast's sitemap, which replaces core's when Yoast is active.
 *
 * @param array $ids Excluded IDs.
 * @return array
 */
function tae_yoast_sitemap_skip_bodiless( $ids ) {
	return array_merge( (array) $ids, tae_bodiless_insight_ids() );
}
