<?php
/**
 * The two outward-facing URLs the templates used to hardcode.
 *
 * Handoff §23.5: "Social links will be supplied or confirmed as accounts are
 * finalized." The Subscribe button on the Interview Series archive shipped with
 * a channel handle nobody had confirmed, written into a PHP template - so the
 * one thing the handoff says will change was the one thing the owner could not
 * change. The Join CTA on the episode page had the same problem for the same
 * reason.
 *
 * Two fields, one screen, no framework. This is deliberately not a settings
 * "system": if a third value ever needs an owner-editable home, add it to
 * TAE_OPTIONS and it appears.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every owner-editable value, with the default the templates used to hardcode.
 *
 * Defaults matter: a fresh install with an empty options row must render exactly
 * what the prototype rendered.
 *
 * @return array<string,array{label:string,default:string,help:string}>
 */
function tae_options() {
	return array(
		'tae_channel_url'   => array(
			'label'   => 'YouTube channel',
			'default' => 'https://www.youtube.com/@theaccessexchange',
			'help'    => 'The Subscribe button under the interview archive. Paste the full channel address.',
		),
		'tae_join_url'      => array(
			'label'   => 'Join The Exchange',
			'default' => '/#join',
			'help'    => 'Where every "Join The Exchange" button goes. The default is the sign-up band on the home page. Point it at a different page, or an external list, if the opt-in ever moves.',
		),
		/* CLIENT EDIT START (2026-08-20), 2nd pass - the home page §03 uses
		   this as a genuine on/off toggle now, not just decoration:
		     - empty  -> if interviews are published but none is featured,
		                 show 3 of them in a row instead of 1
		     - filled -> show 1 recent interview beside this image instead
		   (and it still doubles as the sneak-peek image on the true
		   pre-launch card, before anything is published at all). Left empty
		   by default on purpose - was a placeholder image, but that silently
		   forced the "1 + image" layout even with nothing configured, which
		   defeated the point of it being a toggle. See inc/shortcodes.php
		   case 'home', templates/home-launch.php and templates/home-recent.php. */
		'tae_teaser_image'   => array(
			'label'     => 'Upcoming interview - sneak peek image',
			'default'   => '',
			'help'      => 'Optional. Try https://images.unsplash.com/photo-1559523161-0fc0d8b38a7a?auto=format&fit=crop&w=1000&h=1200&q=72 (a "guest\'s back to camera" shot) if you want a placeholder - paste it here to turn it on.',
			'sanitize'  => 'esc_url_raw',
		),
		'tae_teaser_caption' => array(
			'label'     => 'Upcoming interview - sneak peek caption',
			'default'   => '',
			'help'      => 'Optional short caption over the sneak-peek image above, e.g. "Recording now."',
			'sanitize'  => 'sanitize_text_field',
		),
		/* CLIENT EDIT END */
	);
}

/**
 * Read one setting, falling back to the shipped default.
 *
 * @param string $key Option name.
 * @return string
 */
function tae_option( $key ) {

	$all = tae_options();
	if ( ! isset( $all[ $key ] ) ) {
		return '';
	}

	$value = trim( (string) get_option( $key, '' ) );

	return '' === $value ? $all[ $key ]['default'] : $value;
}

add_action( 'admin_menu', 'tae_settings_menu' );

/**
 * A submenu under Interviews, where the person editing interviews already is.
 */
function tae_settings_menu() {
	add_submenu_page(
		'edit.php?post_type=tae_interview',
		'Links',
		'Links',
		'manage_options',
		'tae-settings',
		'tae_settings_page'
	);
}

add_action( 'admin_init', 'tae_settings_register' );

/**
 * Register each option with its own sanitiser.
 */
function tae_settings_register() {
	// CLIENT EDIT (2026-08-20): every field used 'tae_sanitize_link' (URL-or-path
	// only) until the two sneak-peek fields above needed a plain image URL and a
	// plain text caption respectively - each field now names its own sanitiser,
	// falling back to the original link sanitiser when it doesn't.
	foreach ( tae_options() as $key => $field ) {
		register_setting(
			'tae_settings',
			$key,
			array(
				'type'              => 'string',
				'sanitize_callback' => isset( $field['sanitize'] ) ? $field['sanitize'] : 'tae_sanitize_link',
				'default'           => '',
			)
		);
	}
}

/**
 * Accept an absolute URL or a site-relative path, and nothing else.
 *
 * esc_url_raw alone would throw away "/#join" and "/interview-series/", which
 * are both legitimate values here and both what the templates shipped with.
 *
 * @param string $raw Submitted value.
 * @return string
 */
function tae_sanitize_link( $raw ) {

	$raw = trim( (string) $raw );

	if ( '' === $raw ) {
		return '';
	}

	// Relative path, or a bare fragment.
	if ( '/' === $raw[0] || '#' === $raw[0] ) {
		return esc_url_raw( $raw, array( 'http', 'https' ) );
	}

	return esc_url_raw( $raw, array( 'http', 'https', 'mailto' ) );
}

/**
 * Draw the screen.
 */
function tae_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Links</h1>
		<p>Two addresses the site prints in more than one place. Leave a field empty to go back to the shipped default, shown underneath it.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'tae_settings' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( tae_options() as $tae_key => $tae_field ) : ?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $tae_key ); ?>"><?php echo esc_html( $tae_field['label'] ); ?></label>
						</th>
						<td>
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $tae_key ); ?>"
								name="<?php echo esc_attr( $tae_key ); ?>"
								value="<?php echo esc_attr( (string) get_option( $tae_key, '' ) ); ?>">
							<p class="description">
								<?php echo esc_html( $tae_field['help'] ); ?>
								<br><strong>Default:</strong> <code><?php echo esc_html( $tae_field['default'] ); ?></code>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
