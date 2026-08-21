<?php
/**
 * What the interview and takeaway lists show.
 *
 * Handoff §23.3 asks that the owner can "add, edit, publish and feature
 * interviews independently", and §24 lists "change the featured interview" as
 * one of the nine things the tutorial has to cover. The mechanism was there -
 * four checkboxes on the edit screen - but nothing surfaced the answer to "which
 * one is featured right now?". Finding out meant opening interviews one at a
 * time, which is fine at three and absurd at thirty.
 *
 * So the list table answers it. One glance, no clicking.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * The four slot flags, and the short label each prints as.
 *
 * Short on purpose: four badges have to fit one column without wrapping the row.
 *
 * @return array<string,string>
 */
function tae_slot_labels() {
	return array(
		'tae_slot_cover'   => 'Cover',
		'tae_slot_minor'   => 'Rail',
		'tae_slot_feature' => 'Home',
		'tae_most_watched' => 'Most watched',
	);
}

add_filter( 'manage_tae_interview_posts_columns', 'tae_interview_columns' );

/**
 * Insert the columns before the date.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function tae_interview_columns( $columns ) {

	$out = array();

	foreach ( $columns as $key => $label ) {
		// taxonomy-tae_category is added by show_admin_column in post-types.php.
		if ( 'date' === $key ) {
			$out['tae_ep']    = 'Ep';
			$out['tae_who']   = 'Guest';
			$out['tae_slots'] = 'Appears in';
		}
		$out[ $key ] = $label;
	}

	return $out;
}

add_action( 'manage_tae_interview_posts_custom_column', 'tae_interview_column', 10, 2 );

/**
 * Fill one cell.
 *
 * @param string $column  Column key.
 * @param int    $post_id Interview.
 */
function tae_interview_column( $column, $post_id ) {

	switch ( $column ) {

		case 'tae_ep':
			$num = (string) get_post_meta( $post_id, 'tae_episode', true );
			echo '' === $num ? '<span aria-hidden="true">-</span>' : esc_html( $num );
			break;

		case 'tae_who':
			$who = tae_guest( $post_id );
			echo $who ? esc_html( $who ) : '<span aria-hidden="true">-</span>';
			break;

		case 'tae_slots':
			$held = array();
			foreach ( tae_slot_labels() as $meta_key => $label ) {
				if ( '1' === (string) get_post_meta( $post_id, $meta_key, true ) ) {
					$held[] = $label;
				}
			}

			if ( ! $held ) {
				// Not a problem - an interview in no slot is still in the archive.
				echo '<span style="color:#8c8f94">Archive only</span>';
				break;
			}

			foreach ( $held as $label ) {
				printf(
					'<span style="display:inline-block;margin:0 4px 3px 0;padding:1px 7px;border:1px solid #c3c4c7;border-radius:9px;font-size:11px;line-height:1.7">%s</span>',
					esc_html( $label )
				);
			}
			break;

		case 'tae_from':
			$parent = tae_parent( $post_id );
			if ( $parent ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url( (string) get_edit_post_link( $parent->ID ) ),
					esc_html( get_the_title( $parent ) )
				);
			} else {
				echo '<span style="color:#8c8f94">Stands on its own</span>';
			}
			break;
	}
}

add_filter( 'manage_tae_insight_posts_columns', 'tae_insight_columns' );

/**
 * Takeaways get the one column that answers the question worth asking: which
 * interview did this come out of, and is it linked at all?
 *
 * @param array $columns Existing columns.
 * @return array
 */
function tae_insight_columns( $columns ) {

	$out = array();

	foreach ( $columns as $key => $label ) {
		if ( 'date' === $key ) {
			$out['tae_from'] = 'From';
		}
		$out[ $key ] = $label;
	}

	return $out;
}

add_action( 'manage_tae_insight_posts_custom_column', 'tae_interview_column', 10, 2 );

add_filter( 'manage_edit-tae_interview_sortable_columns', 'tae_sortable_columns' );

/**
 * Episode number sorts numerically, so the list can be read as a run of episodes
 * rather than by publication date.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function tae_sortable_columns( $columns ) {
	$columns['tae_ep'] = 'tae_ep';
	return $columns;
}

add_action( 'pre_get_posts', 'tae_sort_by_episode' );

/**
 * Apply that sort.
 *
 * @param WP_Query $query The admin query.
 */
function tae_sort_by_episode( $query ) {

	if ( ! is_admin() || ! $query->is_main_query() || 'tae_ep' !== $query->get( 'orderby' ) ) {
		return;
	}

	/*
	 * A named clause, not meta_key.
	 *
	 * `meta_key => tae_episode` makes WP_Query INNER JOIN wp_postmeta, which
	 * drops every interview that has no episode number. Clicking the Ep column
	 * header therefore made content disappear from the list - the owner's most
	 * alarming possible outcome from pressing a sort arrow, and one that reads
	 * as "the site deleted my interviews".
	 *
	 * The OR with NOT EXISTS keeps the unnumbered ones in the result set. They
	 * sort as NULL, so they gather at one end of the run rather than vanishing.
	 */
	$order = 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';

	$query->set(
		'meta_query',
		array(
			'relation' => 'OR',
			'ep'       => array(
				'key'     => 'tae_episode',
				'compare' => 'EXISTS',
				'type'    => 'NUMERIC',
			),
			'no_ep'    => array(
				'key'     => 'tae_episode',
				'compare' => 'NOT EXISTS',
			),
		)
	);
	$query->set( 'orderby', array( 'ep' => $order ) );
}
