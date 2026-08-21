<?php
/**
 * Load-more, and the server-side half of the category filter.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_tae_load_interviews', 'tae_ajax_load_interviews' );
add_action( 'wp_ajax_nopriv_tae_load_interviews', 'tae_ajax_load_interviews' );

/**
 * Return a page of interview tiles.
 *
 * Rendering goes through the same item templates the initial page render uses, so
 * an appended tile and a server-rendered one cannot drift apart.
 */
function tae_ajax_load_interviews() {

	/*
	 * DELIBERATELY NO NONCE.
	 *
	 * A nonce protects a state change made on someone's behalf. This endpoint
	 * changes nothing, reads nothing private, and returns the same published
	 * tiles the archive page already renders to anyone who asks for it.
	 *
	 * It also could not survive here. The nonce reached the browser through
	 * wp_localize_script, which prints it inline in the page HTML - and this
	 * site runs a page cache. A cached page freezes its nonce; nonces expire in
	 * 12-24 hours. Every visitor served from cache after that got a dead one,
	 * check_ajax_referer rejected them, and the JSON parse failed on the other
	 * end - so "Earlier episodes" and the category chips silently stopped
	 * working a day after caching was switched on, with nothing on screen to
	 * say so.
	 *
	 * Nonce-in-cached-HTML is the bug. Removing the nonce removes it. Every
	 * input below is still sanitised, and the query is still pinned to
	 * published interviews.
	 */
	$view = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : 'archive';
	if ( 'archive' !== $view ) {
		$view = 'archive';
	}

	$page     = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
	$count    = isset( $_POST['count'] ) ? min( 60, max( 1, (int) $_POST['count'] ) ) : 12;
	$category = isset( $_POST['category'] ) ? sanitize_title( wp_unslash( $_POST['category'] ) ) : '';
	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

	$query = tae_interviews(
		array(
			'posts_per_page' => $count,
			'paged'          => $page,
			'category'       => $category,
			'search'         => $search,
		)
	);

	$html = '';
	foreach ( $query->posts as $item ) {
		$html .= tae_template( 'interview-archive-item', array( 'item' => $item ) );
	}

	wp_send_json_success(
		array(
			'html'     => $html,
			'has_more' => $page < (int) $query->max_num_pages,
			'found'    => (int) $query->found_posts,
		)
	);
}
