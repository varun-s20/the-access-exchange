<?php
/**
 * Hand-rolled meta boxes. No ACF, no other dependency.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions, keyed by post type. The renderer and the save handler both
 * read this, so adding a field means editing one array.
 *
 * @return array
 */
function tae_fields() {
	return array(
		'tae_interview' => array(
			'tae_guest_name'   => array(
				'label' => 'Guest name',
				'type'  => 'text',
				'help'  => 'The name as it should appear publicly.',
			),
			'tae_guest_role'   => array(
				'label' => 'Professional title',
				'type'  => 'text',
				'help'  => 'Chief Operating Officer',
			),
			'tae_org'          => array(
				'label' => 'Organization',
				'type'  => 'text',
				'help'  => 'Where the guest does that job. Printed after the title on the episode page.',
			),
			'tae_episode'      => array(
				'label' => 'Episode number',
				'type'  => 'number',
				'help'  => 'Prints as "Ep 42".',
			),
			'tae_duration'     => array(
				'label' => 'Duration',
				'type'  => 'text',
				'help'  => 'MM:SS, e.g. 42:18',
			),
			'tae_youtube'      => array(
				'label' => 'YouTube video ID',
				'type'  => 'text',
				'help'  => 'The ID only, not the whole URL. Nothing is requested from youtube.com until a visitor presses play.',
			),
			'tae_standfirst'   => array(
				'label' => 'Standfirst',
				'type'  => 'textarea',
				'help'  => 'One or two sentences under the headline. Used by the cover story and the home feature.',
			),
			'tae_rail_line'    => array(
				'label' => 'Guest rail line',
				'type'  => 'text',
				'help'  => 'The short line under the portrait on the home page: "On what twenty years of hiring taught her."',
			),
			'tae_takeaways'    => array(
				'label' => 'Key takeaways',
				'type'  => 'textarea',
				'help'  => 'One per line. The standout ideas from the conversation, shown as a numbered list on the episode page. Plain sentences, no bullets - the list numbers itself.',
				'rows'  => 6,
			),
			'tae_chapters'     => array(
				'label' => 'Chapters',
				'type'  => 'textarea',
				'help'  => 'One per line, as <code>time|label</code>. Example: <code>12:40|The decision that changed the company</code>. Shown on the episode page and on the home feature; each one jumps the video to that timestamp.',
				'rows'  => 6,
			),
			// Four independent checkboxes rather than one select: the cover story is
			// usually also the home feature and the watch poster, so these have to
			// be able to overlap.
			'tae_slot_cover'   => array(
				'label' => 'Slot · Cover story (Interview Series)',
				'type'  => 'checkbox',
				'help'  => 'If two interviews claim the same slot the lower Order wins. An interview in no slot still appears in the archive and the takeaways stream.',
			),
			'tae_slot_minor'   => array(
				'label' => 'Slot · Left rail (Interview Series)',
				'type'  => 'checkbox',
				'help'  => 'The two smaller cards beside the cover story.',
			),
			'tae_slot_feature' => array(
				'label' => 'Slot · Featured interview (Home)',
				'type'  => 'checkbox',
				'help'  => 'The full-bleed block with chapter jumps.',
			),
			'tae_slot_watch'   => array(
				'label' => 'Slot · Watch poster (Home)',
				'type'  => 'checkbox',
				'help'  => 'The single video near the foot of the home page.',
			),
			'tae_most_watched' => array(
				'label' => 'Show in "Most watched"',
				'type'  => 'checkbox',
				'help'  => 'The numbered list on the Interview Series page. Sequence comes from the Order field.',
			),
			'tae_lead'         => array(
				'label' => 'Use as lead tile (Takeaways)',
				'type'  => 'checkbox',
				'help'  => 'The wide tile at the top of the Takeaways stream on the Interview Series page. Leave every item unticked and the newest one leads. If two are ticked the lower Order wins.',
			),
		),
		'tae_insight'   => array(
			'tae_parent'     => array(
				'label' => 'Came from which interview',
				'type'  => 'post_select',
				'help'  => 'The interview this takeaway, clip or write-up came out of. Shown as a way back to the full conversation, and used to place it under "From this interview" on the episode page. Leave empty for a piece that stands on its own.',
				'none'  => '- Stands on its own -',
			),
			'tae_dek'        => array(
				'label' => 'Dek',
				'type'  => 'textarea',
				'help'  => 'Shown on the lead tile only - the first item in the stream.',
			),
			'tae_byline'     => array(
				'label' => 'Byline',
				'type'  => 'text',
				'help'  => 'A person, or a line of context: "Notes from the first term of partnerships".',
			),
			'tae_link'       => array(
				'label' => 'Onward link',
				'type'  => 'url',
				'help'  => 'Where this piece sends the reader next - the interview it came from, an external write-up, anything. Shown as a button on the insight\'s own page, labelled to match where it goes. Leave it empty if the piece stands on its own. Tiles in the stream always open the insight\'s page; this is what they find when they get there.',
			),
			'tae_start_here' => array(
				'label' => 'Show in "Start here"',
				'type'  => 'checkbox',
				'help'  => 'The five numbered links at the foot of the Takeaways section. Sequence comes from the Order field.',
			),
			'tae_lead'       => array(
				'label' => 'Use as lead tile',
				'type'  => 'checkbox',
				'help'  => 'The wide tile at the top of the Takeaways stream. Leave every item unticked and the newest one leads. If two are ticked the lower Order wins.',
			),
		),
	);
}

/**
 * Published interviews as id => label, for the post_select field.
 *
 * Cached per request: the render pass and the save pass both ask for it.
 *
 * @return array<int,string>
 */
function tae_interview_choices() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$cache = array();
	foreach ( get_posts(
		array(
			'post_type'        => 'tae_interview',
			'post_status'      => 'publish',
			'posts_per_page'   => 200,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	) as $item ) {
		$who                  = tae_guest( $item );
		$cache[ $item->ID ] = $who
			? get_the_title( $item ) . ' - ' . $who
			: get_the_title( $item );
	}
	return $cache;
}

add_action( 'add_meta_boxes', 'tae_add_meta_boxes' );

/**
 * Register one box per post type.
 */
function tae_add_meta_boxes() {
	foreach ( array_keys( tae_fields() ) as $type ) {
		add_meta_box(
			'tae_details',
			'tae_interview' === $type ? 'Interview details' : 'Insight details',
			'tae_render_meta_box',
			$type,
			'normal',
			'high'
		);
	}
}

/**
 * Render the box.
 *
 * @param WP_Post $post Current post.
 */
function tae_render_meta_box( $post ) {
	$fields = tae_fields();
	$fields = isset( $fields[ $post->post_type ] ) ? $fields[ $post->post_type ] : array();

	wp_nonce_field( 'tae_save_meta', 'tae_meta_nonce' );

	echo '<style>
		.tae-f{margin:0 0 18px}
		.tae-f label{display:block;font-weight:600;margin-bottom:4px}
		.tae-f input[type=text],.tae-f input[type=url],.tae-f input[type=number],.tae-f textarea,.tae-f select{width:100%;max-width:640px}
		.tae-f textarea{font-family:Consolas,Monaco,monospace;font-size:12px}
		.tae-f p.description{margin-top:4px}
	</style>';

	foreach ( $fields as $key => $f ) {
		$value = get_post_meta( $post->ID, $key, true );

		echo '<div class="tae-f">';
		printf( '<label for="%1$s">%2$s</label>', esc_attr( $key ), esc_html( $f['label'] ) );

		switch ( $f['type'] ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="%2$d">%3$s</textarea>',
					esc_attr( $key ),
					isset( $f['rows'] ) ? (int) $f['rows'] : 3,
					esc_textarea( $value )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%1$s">', esc_attr( $key ) );
				foreach ( $f['options'] as $opt_value => $opt_label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $opt_value ),
						selected( $value, $opt_value, false ),
						esc_html( $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'post_select':
				// Built here rather than in tae_fields() so the query only runs
				// when a box is actually being drawn, not on every save.
				printf( '<select id="%1$s" name="%1$s">', esc_attr( $key ) );
				printf(
					'<option value=""%s>%s</option>',
					selected( $value, '', false ),
					esc_html( isset( $f['none'] ) ? $f['none'] : '- None -' )
				);
				foreach ( tae_interview_choices() as $opt_id => $opt_label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $opt_id ),
						selected( (int) $value, $opt_id, false ),
						esc_html( $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'checkbox':
				printf(
					'<input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s>',
					esc_attr( $key ),
					checked( $value, '1', false )
				);
				break;

			default:
				printf(
					'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s">',
					esc_attr( $f['type'] ),
					esc_attr( $key ),
					esc_attr( $value )
				);
		}

		if ( ! empty( $f['help'] ) ) {
			// Help strings are authored here, not user input - <code> is intentional.
			printf( '<p class="description">%s</p>', wp_kses( $f['help'], array( 'code' => array() ) ) );
		}

		echo '</div>';
	}
}

add_action( 'save_post', 'tae_save_meta', 10, 2 );

/**
 * Persist the box.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function tae_save_meta( $post_id, $post ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['tae_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tae_meta_nonce'] ) ), 'tae_save_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = tae_fields();
	if ( ! isset( $fields[ $post->post_type ] ) ) {
		return;
	}

	foreach ( $fields[ $post->post_type ] as $key => $f ) {

		if ( 'checkbox' === $f['type'] ) {
			// An unchecked box posts nothing, so absence is the value.
			if ( empty( $_POST[ $key ] ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, '1' );
			}
			continue;
		}

		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $key ] );

		switch ( $f['type'] ) {
			case 'url':
				$value = esc_url_raw( $raw );
				break;
			case 'number':
				$value = '' === trim( $raw ) ? '' : (string) absint( $raw );
				break;
			case 'post_select':
				// Only an id that really is a published interview. Anything else
				// posted into this field is discarded rather than stored.
				$candidate = absint( $raw );
				$value     = array_key_exists( $candidate, tae_interview_choices() )
					? (string) $candidate
					: '';
				break;
			case 'textarea':
				$value = sanitize_textarea_field( $raw );
				break;
			case 'select':
				$value = array_key_exists( $raw, $f['options'] ) ? $raw : '';
				break;
			default:
				$value = sanitize_text_field( $raw );
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

/**
 * Parse the chapters textarea into rows.
 *
 * ponytail: a textarea of "time|label" lines instead of a drag-and-drop repeater.
 * Four rows, edited once per interview - a jQuery repeater would be more code than
 * the feature is worth. Swap it if chapters ever get their own fields.
 *
 * @param int $post_id Interview ID.
 * @return array<int,array{time:string,label:string}>
 */
function tae_chapters( $post_id ) {
	$raw = (string) get_post_meta( $post_id, 'tae_chapters', true );
	if ( '' === trim( $raw ) ) {
		return array();
	}

	$rows = array();
	foreach ( preg_split( '/\R/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || false === strpos( $line, '|' ) ) {
			continue;
		}
		list( $time, $label ) = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( '' === $label ) {
			continue;
		}
		$rows[] = array(
			'time'  => $time,
			'label' => $label,
		);
	}
	return $rows;
}

/**
 * Split the key-takeaways textarea into lines.
 *
 * ponytail: one sentence per line, same shape as tae_chapters, for the same
 * reason - a repeater UI would be more code than the feature is worth.
 *
 * @param int $post_id Interview ID.
 * @return string[]
 */
function tae_takeaways( $post_id ) {
	$raw = (string) get_post_meta( $post_id, 'tae_takeaways', true );
	if ( '' === trim( $raw ) ) {
		return array();
	}

	$rows = array();
	foreach ( preg_split( '/\R/', $raw ) as $line ) {
		// Tolerate a pasted list that still has its bullet characters.
		$line = trim( ltrim( trim( $line ), "-*•\t " ) );
		if ( '' !== $line ) {
			$rows[] = $line;
		}
	}
	return $rows;
}
