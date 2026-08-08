<?php
/**
 * One-shot importer for the content currently hardcoded in wordpress/*.html.
 *
 * Safe to run more than once. Every seeded record carries a tae_seed_key, so a
 * second run updates what it made the first time instead of creating a second
 * copy of everything.
 *
 * Two things it will never overwrite: the post body, and an existing featured
 * image. The seed has no bodies to restore, so overwriting one would only ever
 * destroy an article somebody had written.
 *
 * DISPOSABLE. Delete this file and data/seed.php once the content is real.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'tae_importer_menu' );

/**
 * Tools → Import TAE content.
 */
function tae_importer_menu() {
	add_management_page(
		'Import TAE content',
		'Import TAE content',
		'manage_options',
		'tae-import',
		'tae_importer_page'
	);
}

/**
 * A stable identity for a seeded record, so a re-run can find what it made.
 *
 * Derived from the title, which means renaming a post in wp-admin severs the link
 * and a re-run creates it again under the original title. That is the right
 * trade: a renamed post has been taken over by a human, and quietly reverting
 * their title would be worse.
 *
 * @param array $row Seed row.
 * @return string
 */
function tae_seed_key( $row ) {
	return sanitize_title( $row['title'] );
}

/**
 * Find the post a previous run created for this row.
 *
 * @param string $post_type Post type.
 * @param array  $row       Seed row.
 * @return WP_Post|null
 */
function tae_find_seeded( $post_type, $row ) {

	$found = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'meta_key'         => 'tae_seed_key',
			'meta_value'       => tae_seed_key( $row ),
			'suppress_filters' => false,
		)
	);

	if ( $found ) {
		return $found[0];
	}

	// Posts from the very first import predate tae_seed_key. Match them by title
	// once, so they get adopted rather than duplicated.
	$legacy = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'title'            => $row['title'],
			'suppress_filters' => false,
		)
	);

	return $legacy ? $legacy[0] : null;
}

/**
 * The page, and the POST handler.
 */
function tae_importer_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nope.' );
	}

	$report = null;
	$wiped  = null;

	if (
		isset( $_POST['tae_import_nonce'] )
		&& wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tae_import_nonce'] ) ), 'tae_import' )
	) {
		if ( isset( $_POST['tae_wipe'] ) && ! empty( $_POST['tae_wipe_confirm'] ) ) {
			$wiped = tae_delete_seeded();
		} elseif ( isset( $_POST['tae_run'] ) ) {
			$report = tae_run_import();
		}
	}

	$last = get_option( 'tae_seeded' );

	echo '<div class="wrap"><h1>Import TAE content</h1>';

	if ( null !== $wiped ) {
		printf(
			'<div class="notice notice-success"><p><strong>%d imported posts deleted.</strong> Their images were left in the Media Library.</p></div>',
			(int) $wiped
		);
	}

	if ( $report ) {
		printf(
			'<div class="notice notice-%s"><p><strong>%d created, %d updated.</strong> %s</p></div>',
			$report['image_failures'] ? 'warning' : 'success',
			(int) $report['created'],
			(int) $report['updated'],
			$report['image_failures']
				? sprintf(
					'<span style="color:#b32d2e">%d of %d images failed to download.</span> Sideloading needs outbound HTTP from this host - check that images.pexels.com is reachable, then set the featured images by hand.',
					(int) $report['image_failures'],
					(int) $report['image_attempts']
				)
				: sprintf( '%d images imported, the rest already had one.', (int) $report['image_attempts'] )
		);

		if ( ! empty( $report['errors'] ) ) {
			echo '<div class="notice notice-error"><p><strong>Errors</strong></p><ul style="list-style:disc;margin-left:20px">';
			foreach ( $report['errors'] as $error ) {
				printf( '<li>%s</li>', esc_html( $error ) );
			}
			echo '</ul></div>';
		}
	}

	if ( $last && ! $report && null === $wiped ) {
		printf(
			'<div class="notice notice-info"><p>Last run %s. Running it again updates those posts rather than duplicating them.</p></div>',
			esc_html( $last )
		);
	}

	echo '<p>Creates the 13 interviews and 11 insights that were hardcoded in the page files, and pulls their photographs into the Media Library.</p>';

	echo '<h2>Running it more than once</h2>';
	echo '<p>Safe. Every record it creates is tagged, so a re-run finds and updates the same post instead of adding a second copy. Titles, dates, categories, guests, slots and every other field are overwritten from the seed.</p>';
	echo '<p><strong>Two things it never touches:</strong></p>';
	echo '<ul style="list-style:disc;margin-left:20px">';
	echo '<li><strong>The body.</strong> The seed has no article text, so overwriting a body could only ever destroy something somebody wrote.</li>';
	echo '<li><strong>An existing featured image.</strong> Swap an image in wp-admin and a re-run leaves it alone. Remove the image and the next run puts the original back.</li>';
	echo '</ul>';
	echo '<p>Rename a post in wp-admin and the link is severed - a re-run will then create it again under its original title. Rename freely once you have stopped re-importing.</p>';

	echo '<p><em>Five of the fourteen insight tiles in the prototype are interviews wearing an "Interview" label, and two more repeat an interview title under a different topic. None of those seven are imported as insights - the stream queries both post types, so they would appear twice.</em></p>';

	echo '<form method="post">';
	wp_nonce_field( 'tae_import', 'tae_import_nonce' );
	submit_button( $last ? 'Import or refresh content' : 'Import content', 'primary', 'tae_run' );
	echo '</form>';

	echo '<hr><h2>Start over</h2>';
	echo '<p>Deletes every post this importer created, permanently - including any body text added to them since. Images stay in the Media Library. Anything you created by hand is untouched.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'tae_import', 'tae_import_nonce' );
	echo '<p><label><input type="checkbox" name="tae_wipe_confirm" value="1"> Yes, delete the imported posts.</label></p>';
	submit_button( 'Delete imported content', 'delete', 'tae_wipe', false );
	echo '</form>';

	echo '<hr><p>This screen is disposable. Delete <code>inc/importer.php</code> and <code>data/seed.php</code> once the content is real.</p>';

	echo '</div>';
}

/**
 * Delete everything a previous run created.
 *
 * Attachments are deliberately left behind - they may have been reused elsewhere
 * by now, and an image in the Media Library costs nothing.
 *
 * @return int How many posts were deleted.
 */
function tae_delete_seeded() {

	$posts = get_posts(
		array(
			'post_type'        => array( 'tae_interview', 'tae_insight' ),
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'meta_key'         => 'tae_seed_key',
			'suppress_filters' => false,
		)
	);

	$count = 0;
	foreach ( $posts as $post ) {
		if ( wp_delete_post( $post->ID, true ) ) {
			++$count;
		}
	}

	delete_option( 'tae_seeded' );

	return $count;
}

/**
 * Create or refresh the seeded content.
 *
 * @return array{created:int,updated:int,image_attempts:int,image_failures:int,errors:string[]}
 */
function tae_run_import() {

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$report = array(
		'created'        => 0,
		'updated'        => 0,
		'image_attempts' => 0,
		'image_failures' => 0,
		'errors'         => array(),
	);

	// data/seed.php is disposable and the docs tell you to delete it. Say so
	// rather than fataling on a require of a file somebody correctly removed.
	if ( ! file_exists( TAE_DIR . 'data/seed.php' ) ) {
		$report['errors'][] = 'data/seed.php has been deleted. Nothing to import - you can delete inc/importer.php too.';
		return $report;
	}

	$seed = require TAE_DIR . 'data/seed.php';

	foreach ( $seed['interviews'] as $row ) {

		$id = tae_upsert( 'tae_interview', $row, isset( $row['most_watched'] ) ? (int) $row['most_watched'] : 0, $report );
		if ( ! $id ) {
			continue;
		}

		wp_set_object_terms( $id, $row['category'], 'tae_category' );

		update_post_meta( $id, 'tae_guest_name', $row['guest'] );
		update_post_meta( $id, 'tae_guest_role', $row['role'] );
		update_post_meta( $id, 'tae_episode', (string) $row['episode'] );
		update_post_meta( $id, 'tae_duration', $row['duration'] );
		update_post_meta( $id, 'tae_youtube', $row['youtube'] );

		tae_set_or_clear( $id, 'tae_standfirst', isset( $row['stand'] ) ? $row['stand'] : '' );
		tae_set_or_clear( $id, 'tae_rail_line', isset( $row['rail'] ) ? $row['rail'] : '' );
		tae_set_or_clear( $id, 'tae_chapters', isset( $row['chapters'] ) ? $row['chapters'] : '' );
		tae_set_or_clear( $id, 'tae_most_watched', empty( $row['most_watched'] ) ? '' : '1' );

		// Every slot is rewritten, not just the ones this row claims, so removing a
		// slot from the seed actually removes it on a re-run.
		$slots = isset( $row['slots'] ) ? $row['slots'] : array();
		foreach ( array( 'cover', 'minor', 'feature', 'watch' ) as $slot ) {
			tae_set_or_clear( $id, 'tae_slot_' . $slot, in_array( $slot, $slots, true ) ? '1' : '' );
		}

		tae_attach_photo( $id, $row, $report );
	}

	foreach ( $seed['insights'] as $row ) {

		$id = tae_upsert( 'tae_insight', $row, isset( $row['start_here'] ) ? (int) $row['start_here'] : 0, $report );
		if ( ! $id ) {
			continue;
		}

		wp_set_object_terms( $id, $row['topic'], 'tae_topic' );
		update_post_meta( $id, 'tae_byline', $row['byline'] );

		// The prototype's destination, so the tile behaves as it always did. Clear
		// it once the piece is written and the insight's own page is worth landing on.
		tae_set_or_clear( $id, 'tae_link', isset( $row['link'] ) ? $row['link'] : '' );
		tae_set_or_clear( $id, 'tae_start_here', empty( $row['start_here'] ) ? '' : '1' );

		tae_attach_photo( $id, $row, $report );
	}

	update_option( 'tae_seeded', current_time( 'mysql' ) );

	return $report;
}

/**
 * Create the post, or update the one a previous run made.
 *
 * post_content is set on create and never on update - see the file header.
 *
 * @param string $post_type  Post type.
 * @param array  $row        Seed row.
 * @param int    $menu_order Curation rank.
 * @param array  $report     Report, by reference.
 * @return int|null Post ID, or null if it could not be written.
 */
function tae_upsert( $post_type, $row, $menu_order, &$report ) {

	$existing = tae_find_seeded( $post_type, $row );

	$fields = array(
		'post_type'   => $post_type,
		'post_title'  => $row['title'],
		'post_date'   => $row['date'],
		'menu_order'  => $menu_order,
		'post_status' => 'publish',
	);

	if ( $existing ) {
		$fields['ID'] = $existing->ID;
		// Do not resurrect something an editor sent to the bin.
		if ( 'trash' === $existing->post_status ) {
			unset( $fields['post_status'] );
		}
		$id = wp_update_post( $fields, true );
	} else {
		$id = wp_insert_post( $fields, true );
	}

	if ( is_wp_error( $id ) ) {
		$report['errors'][] = $row['title'] . ': ' . $id->get_error_message();
		return null;
	}

	update_post_meta( $id, 'tae_seed_key', tae_seed_key( $row ) );

	if ( $existing ) {
		++$report['updated'];
	} else {
		++$report['created'];
	}

	return (int) $id;
}

/**
 * Write a meta value, or remove the key when the value is empty.
 *
 * Without the delete branch a re-run could set a field but never unset one, so
 * removing a value from the seed would have no effect.
 *
 * @param int    $post_id Post.
 * @param string $key     Meta key.
 * @param string $value   Value, '' to remove.
 */
function tae_set_or_clear( $post_id, $key, $value ) {
	if ( '' === (string) $value ) {
		delete_post_meta( $post_id, $key );
		return;
	}
	update_post_meta( $post_id, $key, $value );
}

/**
 * Sideload one Pexels photo and set it as the featured image.
 *
 * Skipped entirely when the post already has one, so a re-run neither re-downloads
 * 24 files nor overwrites an image somebody replaced by hand.
 *
 * Image failures are counted separately from post creation on purpose. Sideloading
 * needs outbound HTTP from the host; on a locked-down server every image fails
 * silently and the importer would otherwise report a clean run with no pictures.
 *
 * @param int   $post_id Target post.
 * @param array $row     Seed row.
 * @param array $report  Report, by reference.
 */
function tae_attach_photo( $post_id, $row, &$report ) {

	if ( empty( $row['photo'] ) || has_post_thumbnail( $post_id ) ) {
		return;
	}

	++$report['image_attempts'];

	$url = sprintf(
		'https://images.pexels.com/photos/%1$d/pexels-photo-%1$d.jpeg?auto=compress&cs=tinysrgb&w=1800',
		(int) $row['photo']
	);

	$tmp = download_url( $url );

	if ( is_wp_error( $tmp ) ) {
		++$report['image_failures'];
		$report['errors'][] = $row['title'] . ' - image: ' . $tmp->get_error_message();
		return;
	}

	$alt = isset( $row['alt'] ) ? $row['alt'] : $row['title'];

	// media_sideload_image() takes the filename from the URL, and a Pexels URL ends
	// in a query string. Hand it a real .jpeg name instead.
	$attachment_id = media_handle_sideload(
		array(
			'name'     => sprintf( 'pexels-%d.jpeg', (int) $row['photo'] ),
			'tmp_name' => $tmp,
		),
		$post_id,
		$alt
	);

	if ( is_wp_error( $attachment_id ) ) {
		// download_url() created the temp file; media_handle_sideload() only removes
		// it on success.
		wp_delete_file( $tmp );
		++$report['image_failures'];
		$report['errors'][] = $row['title'] . ' - image: ' . $attachment_id->get_error_message();
		return;
	}

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	set_post_thumbnail( $post_id, $attachment_id );
}
