<?php
/**
 * Plugin Name:       The Access Exchange - Content (v2)
 * Description:       Interviews and takeaways as editable content, rendered through shortcodes into the existing Elementor HTML widgets. Emits markup identical to the static prototype, so global.css never changes. Reinstalled under a new plugin slug (tae-content-v2) after the old "tae-content" slug got stuck reappearing after deletion on the live host.
 * Version:           1.1.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Digital Heroes
 * Text Domain:       tae
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

// Also the cache-buster on tae-archive.js. Bump it whenever that file changes.
define( 'TAE_VER', '1.1.2' );
define( 'TAE_DIR', plugin_dir_path( __FILE__ ) );
define( 'TAE_URL', plugin_dir_url( __FILE__ ) );

require_once TAE_DIR . 'inc/post-types.php';
require_once TAE_DIR . 'inc/meta-boxes.php';
require_once TAE_DIR . 'inc/query.php';
require_once TAE_DIR . 'inc/render.php';
require_once TAE_DIR . 'inc/filter-css.php';
require_once TAE_DIR . 'inc/shortcodes.php';
require_once TAE_DIR . 'inc/ajax.php';
require_once TAE_DIR . 'inc/site-index.php';
require_once TAE_DIR . 'inc/thin-pages.php';
require_once TAE_DIR . 'inc/settings.php';
require_once TAE_DIR . 'inc/schema.php';

if ( is_admin() ) {
	require_once TAE_DIR . 'inc/admin-columns.php';
}

/*
 * inc/importer.php and data/seed.php are gone. They transcribed the 13
 * interviews and 11 insights that were hardcoded in the prototype, to save
 * typing them once - and then stayed in the shipped plugin, one menu click away
 * from putting demo content on the live site. The prototype's copy is not the
 * launch content, and the launch state is an empty library that fills itself in
 * as the owner publishes. Deleted rather than documented.
 */

/**
 * Elementor HTML widgets output their content verbatim. Without this the pages
 * print "[tae_interviews ...]" as literal text.
 *
 * The same filter is required for the six Contact Form 7 forms (CF7-SMTP.md §3),
 * so if that is already in place this is a harmless no-op - do_shortcode on
 * already-expanded output finds nothing left to expand.
 */
add_filter(
	'elementor/widget/render_content',
	function ( $content, $widget ) {
		if ( 'html' === $widget->get_name() ) {
			$content = do_shortcode( $content );
		}
		return $content;
	},
	10,
	2
);

register_activation_hook(
	__FILE__,
	function () {
		tae_register_post_types();
		tae_register_taxonomies();
		tae_seed_terms();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
