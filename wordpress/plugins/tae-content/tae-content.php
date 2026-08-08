<?php
/**
 * Plugin Name:       The Access Exchange - Content
 * Description:       Interviews and insights as editable content, rendered through shortcodes into the existing Elementor HTML widgets. Emits markup identical to the static prototype, so global.css never changes.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            The Access Exchange
 * Text Domain:       tae
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

define( 'TAE_VER', '1.0.0' );
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

if ( is_admin() && file_exists( TAE_DIR . 'inc/importer.php' ) ) {
	require_once TAE_DIR . 'inc/importer.php';
}

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
