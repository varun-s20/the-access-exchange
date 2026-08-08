<?php
/**
 * Post types, taxonomies, seeded terms, image sizes, single template.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * Taxonomy term slugs are load-bearing: they are written into data-cat / data-topic
 * attributes and matched by the generated filter CSS. Names are freely editable in
 * wp-admin, slugs are not.
 */
const TAE_CATEGORIES = array(
	'leadership' => 'Leadership',
	'founders'   => 'Founders & Builders',
	'industry'   => 'Industry & Craft',
	'career'     => 'Career & Transitions',
	'campus'     => 'On Campus',
);

/**
 * Topics classify takeaways - the interview-derived pieces the handoff asks to
 * keep inside the Interview Series ecosystem rather than on a page of their own.
 */
const TAE_TOPICS = array(
	'takeaway'  => 'Takeaways',
	'framework' => 'Frameworks',
	'clip'      => 'Clips',
	'written'   => 'Written',
);

add_action( 'init', 'tae_register_post_types' );
add_action( 'init', 'tae_register_taxonomies' );
add_action( 'after_setup_theme', 'tae_register_image_sizes' );

/**
 * Register the two post types.
 */
function tae_register_post_types() {

	register_post_type(
		'tae_interview',
		array(
			'labels'             => array(
				'name'               => 'Interviews',
				'singular_name'      => 'Interview',
				'add_new_item'       => 'Add Interview',
				'edit_item'          => 'Edit Interview',
				'not_found'          => 'No interviews yet.',
				'featured_image'     => 'Poster / portrait',
				'set_featured_image' => 'Set poster',
				'menu_name'          => 'Interviews',
			),
			// Each interview is a page of its own at /interviews/{slug}/. The
			// handoff's episode template - takeaways, chapters, sharing, related
			// interview - needs somewhere to live, and an episode nobody can link
			// to cannot be shared or indexed.
			//
			// has_archive stays false: /interview-series/ is an Elementor page
			// that composes the cover, archive and takeaways, which a generated
			// archive could not do.
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => false,
			'menu_position'      => 21,
			'menu_icon'          => 'dashicons-video-alt3',
			'rewrite'            => array(
				'slug'       => 'interviews',
				'with_front' => false,
			),
			// page-attributes is what exposes the Order field, which drives every
			// curated sequence in this plugin. editor + excerpt arrived with the
			// episode page: the body is the write-up, the excerpt feeds sharing.
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'tae_insight',
		array(
			'labels'             => array(
				'name'          => 'Insights',
				'singular_name' => 'Insight',
				'add_new_item'  => 'Add Insight',
				'edit_item'     => 'Edit Insight',
				'not_found'     => 'No insights yet.',
				'menu_name'     => 'Insights',
			),
			// Public, because an insight with no tae_link falls back to its own page.
			'public'             => true,
			'publicly_queryable' => true,
			'show_in_menu'       => true,
			// There is no /insights/ page any more - the handoff puts takeaways
			// inside the Interview Series ecosystem, so the stream is a section on
			// /interview-series/ and each piece sits under /takeaways/.
			'has_archive'        => false,
			'menu_position'      => 22,
			'menu_icon'          => 'dashicons-media-text',
			'rewrite'            => array(
				'slug'       => 'takeaways',
				'with_front' => false,
			),
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		)
	);
}

/**
 * Register the two taxonomies.
 */
function tae_register_taxonomies() {

	register_taxonomy(
		'tae_category',
		'tae_interview',
		array(
			'labels'            => array(
				'name'          => 'Categories',
				'singular_name' => 'Category',
				'menu_name'     => 'Categories',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		)
	);

	register_taxonomy(
		'tae_topic',
		'tae_insight',
		array(
			'labels'            => array(
				'name'          => 'Topics',
				'singular_name' => 'Topic',
				'menu_name'     => 'Topics',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		)
	);
}

/**
 * Create the seeded terms on activation. Existing terms are left alone, so a
 * renamed term survives reactivation.
 */
function tae_seed_terms() {
	foreach ( TAE_CATEGORIES as $slug => $name ) {
		if ( ! term_exists( $slug, 'tae_category' ) ) {
			wp_insert_term( $name, 'tae_category', array( 'slug' => $slug ) );
		}
	}
	foreach ( TAE_TOPICS as $slug => $name ) {
		if ( ! term_exists( $slug, 'tae_topic' ) ) {
			wp_insert_term( $name, 'tae_topic', array( 'slug' => $slug ) );
		}
	}
}

/**
 * One upload per post; WordPress generates the crops the markup asks for.
 *
 * Every size is a hard centre crop. The 16:9 -> 3:4 jump for tae-rail is the one
 * that can cut off a head - fix that per image with the featured-image crop
 * editor rather than by adding another upload field.
 */
function tae_register_image_sizes() {
	add_image_size( 'tae-tile', 640, 360, true );     // archive tile
	add_image_size( 'tae-watch', 1200, 675, true );   // home §11 watch poster
	add_image_size( 'tae-cover', 1400, 875, true );   // interview-series cover story
	add_image_size( 'tae-minor', 560, 420, true );    // interview-series left rail
	add_image_size( 'tae-card', 620, 775, true );     // home wall card
	add_image_size( 'tae-rail', 700, 930, true );     // home guest rail portrait
	add_image_size( 'tae-stream', 700, 440, true );   // insights stream tile
	add_image_size( 'tae-lead', 1400, 620, true );    // insights lead tile
	add_image_size( 'tae-feature', 1800, 1013, true ); // home §04 full-bleed
}

/**
 * Single templates.
 *
 * Insights reuse the About broadsheet layout. Interviews get the episode page -
 * every field the handoff's episode template lists, in one place.
 */
add_filter(
	'single_template',
	function ( $template ) {
		$map = array(
			'tae_insight'   => 'templates/single-insight.php',
			'tae_interview' => 'templates/single-tae_interview.php',
		);
		foreach ( $map as $type => $file ) {
			if ( is_singular( $type ) && file_exists( TAE_DIR . $file ) ) {
				return TAE_DIR . $file;
			}
		}
		return $template;
	}
);
