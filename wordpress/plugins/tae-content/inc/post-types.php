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
	'engineering' => 'Engineering',
	'product'     => 'Product',
	'data'        => 'Data & AI',
	'design'      => 'Design',
	'breaking-in' => 'Breaking in',
);

const TAE_TOPICS = array(
	'breaking-in'  => 'Breaking in',
	'levelling-up' => 'Levelling up',
	'on-campus'    => 'On campus',
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
			// No single page by design — cards link to /interview-series/#episodes
			// and the video plays inline through global.js §06.
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => false,
			'menu_position'      => 21,
			'menu_icon'          => 'dashicons-video-alt3',
			// page-attributes is what exposes the Order field, which drives every
			// curated sequence in this plugin.
			'supports'           => array( 'title', 'thumbnail', 'page-attributes' ),
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
			'has_archive'        => false, // /insights/ is an Elementor page, not an archive.
			'menu_position'      => 22,
			'menu_icon'          => 'dashicons-media-text',
			'rewrite'            => array(
				'slug'       => 'insights',
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
 * that can cut off a head — fix that per image with the featured-image crop
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
 * Insight single pages reuse the About broadsheet layout, which already exists in
 * global.css §1035 onward. No new CSS.
 */
add_filter(
	'single_template',
	function ( $template ) {
		if ( is_singular( 'tae_insight' ) ) {
			$candidate = TAE_DIR . 'templates/single-insight.php';
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}
		return $template;
	}
);
