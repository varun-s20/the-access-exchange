<?php
/**
 * Standalone checks for the logic that is easy to get quietly wrong.
 *
 *     php tests/test-tae.php
 *
 * No framework, no WordPress — just enough stubs to exercise the pure functions.
 * What it covers is deliberately narrow: generated CSS, instance ID collisions,
 * chapter parsing and link resolution. Everything else is markup, and markup is
 * checked by looking at the page.
 *
 * @package TAE_Content
 */

define( 'ABSPATH', __DIR__ );
define( 'TAE_DIR', dirname( __DIR__ ) . '/' );

/* ── stubs ──────────────────────────────────────────────────────────────── */

$GLOBALS['tae_test_meta'] = array();
$GLOBALS['tae_test_terms'] = array();

function add_action() {}
function add_filter() {}
function add_meta_box() {}
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return (string) $s; }
function is_wp_error( $t ) { return false; }
function get_permalink( $p ) { return '/insights/' . $p->post_name . '/'; }
function get_post_meta( $id, $key, $single = false ) {
	return isset( $GLOBALS['tae_test_meta'][ $id ][ $key ] ) ? $GLOBALS['tae_test_meta'][ $id ][ $key ] : '';
}
function get_the_terms( $id, $tax ) {
	return isset( $GLOBALS['tae_test_terms'][ $id ] ) ? $GLOBALS['tae_test_terms'][ $id ] : false;
}
function get_terms( $args ) {
	$out = array();
	foreach ( array(
		'engineering' => 'Engineering',
		'product'     => 'Product',
		'data'        => 'Data & AI',
		'design'      => 'Design',
		'breaking-in' => 'Breaking in',
	) as $slug => $name ) {
		$out[] = (object) array(
			'slug'  => $slug,
			'name'  => $name,
			'count' => 1,
		);
	}
	return $out;
}

function get_post( $p ) { return is_object( $p ) ? $p : null; }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function strip_shortcodes( $s ) { return preg_replace( '/\[[^\]]*\]/', '', (string) $s ); }

require TAE_DIR . 'inc/filter-css.php';
require TAE_DIR . 'inc/meta-boxes.php';
require TAE_DIR . 'inc/render.php';
require TAE_DIR . 'inc/query.php';
require TAE_DIR . 'inc/thin-pages.php';

/* ── harness ────────────────────────────────────────────────────────────── */

$failures = 0;

/**
 * Assert two values match.
 *
 * @param string $label    What is being checked.
 * @param mixed  $expected Expected.
 * @param mixed  $actual   Actual.
 */
function check( $label, $expected, $actual ) {
	global $failures;
	if ( $expected === $actual ) {
		echo "  ok   $label\n";
		return;
	}
	++$failures;
	echo "  FAIL $label\n";
	echo "       expected: " . var_export( $expected, true ) . "\n";
	echo "       actual:   " . var_export( $actual, true ) . "\n";
}

/**
 * Collapse whitespace so selector formatting does not decide the result.
 *
 * @param string $css CSS.
 * @return string
 */
function squash( $css ) {
	$css = preg_replace( '/\s+/', ' ', str_replace( array( "\n", "\t" ), ' ', $css ) );
	$css = preg_replace( '/\s*,\s*/', ',', $css );   // the stylesheet breaks lines on commas; the generator does not.
	return trim( $css );
}

/* ── 1 · the generated wall CSS must equal the hand-written rules ───────── */

echo "\nGenerated filter CSS\n";

// Copied verbatim from wordpress/assets/global.css:677-681.
$stylesheet = '.tae-home .filters:has(#c-eng:checked)  ~ .grid .card:not([data-cat="engineering"]),
.tae-home .filters:has(#c-prod:checked) ~ .grid .card:not([data-cat="product"]),
.tae-home .filters:has(#c-data:checked) ~ .grid .card:not([data-cat="data"]),
.tae-home .filters:has(#c-des:checked)  ~ .grid .card:not([data-cat="design"]),
.tae-home .filters:has(#c-brk:checked)  ~ .grid .card:not([data-cat="breaking-in"]){display:none}';

$wall = tae_filter_markup( 'wall', 1 );

check(
	'first instance reproduces global.css:677-681 exactly',
	squash( $stylesheet ),
	squash( str_replace( array( '<style>', '</style>' ), '', $wall['style'] ) )
);

check( 'first instance adds no scoping class', '', $wall['class'] );

check(
	'chips carry the legacy IDs global.js expects',
	true,
	false !== strpos( $wall['chips'], 'id="c-eng"' ) && false !== strpos( $wall['chips'], 'id="c-all"' )
);

check(
	'chips carry the slug for tae-archive.js',
	true,
	false !== strpos( $wall['chips'], 'data-cat="breaking-in"' )
);

/* ── 2 · the archive scope must NOT copy the stylesheet's broken selector ─ */

echo "\nArchive scope\n";

$archive = tae_filter_markup( 'archive', 1 );

check(
	'scopes to .index-head, not .sorts',
	true,
	false !== strpos( $archive['style'], '.tae-interviews .index-head:has(' )
);

// global.css:949 writes ".tae-interviews .sorts:has(...) ~ .eps li". .sorts is a
// child of .index-head and .eps is a sibling of .index-head, so that combinator
// never matches and the filter has never worked. Regenerating it the same way
// would reproduce the bug.
check(
	'does not reproduce the never-matching .sorts selector',
	false,
	false !== strpos( $archive['style'], '.sorts:has(' )
);

/* ── 3 · a second instance must not collide with the first ──────────────── */

echo "\nInstance IDs\n";

$second = tae_filter_markup( 'wall', 2 );

preg_match_all( '/id="([^"]+)"/', $wall['chips'], $first_ids );
preg_match_all( '/id="([^"]+)"/', $second['chips'], $second_ids );

check( 'second instance emits the same number of chips', count( $first_ids[1] ), count( $second_ids[1] ) );
check( 'second instance shares no ID with the first', array(), array_values( array_intersect( $first_ids[1], $second_ids[1] ) ) );
check( 'second instance gets its own scoping class', 'tae-fi-wall-2', $second['class'] );
check(
	'second instance scopes its CSS to that class',
	true,
	false !== strpos( $second['style'], '.tae-fi-wall-2:has(' )
);
check(
	'second instance uses its own radio group name',
	true,
	false !== strpos( $second['chips'], 'name="cat-2"' )
);

/* ── 4 · chapter parsing ────────────────────────────────────────────────── */

echo "\nChapter parsing\n";

$GLOBALS['tae_test_meta'][1]['tae_chapters'] =
	"03:10|The bootcamp year\n" .
	"  14:45 | Volunteering for the work nobody wanted  \n" .
	"\n" .                                  // blank line
	"no pipe here so this is not a row\n" .
	"26:02|\n" .                            // empty label
	"35:30|What she would tell someone finishing a bootcamp|and a stray pipe";

$chapters = tae_chapters( 1 );

check( 'drops blank, pipe-less and label-less lines', 3, count( $chapters ) );
check( 'trims around the pipe', array( 'time' => '14:45', 'label' => 'Volunteering for the work nobody wanted' ), $chapters[1] );
check( 'splits on the first pipe only', 'What she would tell someone finishing a bootcamp|and a stray pipe', $chapters[2]['label'] );
check( 'empty field yields no rows', array(), tae_chapters( 99 ) );

/* ── 5 · link resolution ────────────────────────────────────────────────── */

echo "\nLink resolution\n";

$interview = (object) array( 'ID' => 10, 'post_type' => 'tae_interview', 'post_name' => 'amara' );
$linked    = (object) array( 'ID' => 11, 'post_type' => 'tae_insight', 'post_name' => 'promotion' );
$unlinked  = (object) array( 'ID' => 12, 'post_type' => 'tae_insight', 'post_name' => 'cold-outreach' );

$GLOBALS['tae_test_meta'][11]['tae_link'] = 'https://example.org/piece';

check( 'interviews always land on the archive', '/interview-series/#episodes', tae_destination( $interview ) );
check( 'an insight goes to its own page', '/insights/cold-outreach/', tae_destination( $unlinked ) );
// The onward link is a button ON the insight's page, not a replacement for it —
// otherwise every insight page is unreachable from the site that owns it.
check( 'an onward link does not divert the tile', '/insights/promotion/', tae_destination( $linked ) );

/* ── 6 · guest line ─────────────────────────────────────────────────────── */

echo "\nGuest line\n";

$GLOBALS['tae_test_meta'][20] = array( 'tae_guest_name' => 'Amara Osei', 'tae_guest_role' => 'Staff Engineer' );
$GLOBALS['tae_test_meta'][21] = array( 'tae_guest_name' => 'Amara Osei' );
$GLOBALS['tae_test_meta'][22] = array( 'tae_guest_role' => 'Staff Engineer' );

check( 'name and role', 'Amara Osei, Staff Engineer', tae_guest( 20 ) );
check( 'name only, no trailing comma', 'Amara Osei', tae_guest( 21 ) );
check( 'role only', 'Staff Engineer', tae_guest( 22 ) );
check( 'neither', '', tae_guest( 23 ) );

/* ── 7 · reveal stagger ─────────────────────────────────────────────────── */

echo "\nReveal stagger\n";

check( 'first of each group of four carries no delay', '', tae_delay( 0 ) . tae_delay( 4 ) . tae_delay( 8 ) );
check( 'cycles 60/120/180', ' style="--d:60ms" style="--d:120ms" style="--d:180ms"', tae_delay( 1 ) . tae_delay( 2 ) . tae_delay( 3 ) );
check( 'continues across an AJAX page boundary', ' style="--d:60ms"', tae_delay( 13 ) );

/* ── 8 · has this insight actually been written? ────────────────────────── */

echo "\nBody detection\n";

$body = function ( $content ) {
	return (object) array( 'ID' => 1, 'post_type' => 'tae_insight', 'post_content' => $content );
};

check( 'empty string', false, tae_has_body( $body( '' ) ) );
check( 'whitespace only', false, tae_has_body( $body( "  \n\t " ) ) );
check( 'an empty Gutenberg paragraph block', false, tae_has_body( $body( '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->' ) ) );
check( 'a bare shortcode with nothing else', false, tae_has_body( $body( '[some_shortcode]' ) ) );
check( 'real prose', true, tae_has_body( $body( '<p>Two people leave the same course.</p>' ) ) );
check( 'an image with no prose still counts', true, tae_has_body( $body( '<figure><img src="x.jpg"></figure>' ) ) );

/* ── 9 · lead tile selection ────────────────────────────────────────────── */

echo "\nLead tile\n";

$mk = function ( $id, $order = 0 ) {
	return (object) array( 'ID' => $id, 'post_type' => 'tae_insight', 'menu_order' => $order );
};

$stream = array( $mk( 1 ), $mk( 2 ), $mk( 3 ), $mk( 4 ) );

$ids = function ( $posts ) {
	return array_map(
		function ( $p ) {
			return $p->ID;
		},
		$posts
	);
};

check( 'nothing flagged leaves the order alone', array( 1, 2, 3, 4 ), $ids( tae_lead_first( $stream ) ) );

$GLOBALS['tae_test_meta'][3]['tae_lead'] = '1';
check( 'a flagged item moves to the front', array( 3, 1, 2, 4 ), $ids( tae_lead_first( $stream ) ) );
check( 'and nothing is lost or duplicated', 4, count( tae_lead_first( $stream ) ) );

$GLOBALS['tae_test_meta'][2]['tae_lead'] = '1';
$stream_ordered = array( $mk( 1 ), $mk( 2, 5 ), $mk( 3, 2 ), $mk( 4 ) );
check( 'two flagged: the lower Order wins', array( 3, 1, 2, 4 ), $ids( tae_lead_first( $stream_ordered ) ) );

$GLOBALS['tae_test_meta'][2]['tae_lead'] = '';
$GLOBALS['tae_test_meta'][3]['tae_lead'] = '';
check( 'an unticked box is not a flag', array( 1, 2, 3, 4 ), $ids( tae_lead_first( $stream ) ) );

/* ── 10 · onward-link labels ────────────────────────────────────────────── */

echo "\nOnward link labels\n";

$home = 'https://theaccessexchange.com';

check( 'no destination, no label', '', tae_onward_label( '', $home ) );
check( 'an interview', 'Watch the interview', tae_onward_label( '/interview-series/#episodes', $home ) );
check( 'the partnership guide', 'Read the partnership guide', tae_onward_label( '/university-partnerships/#s1', $home ) );
check( 'somewhere else entirely', 'Read it in full', tae_onward_label( 'https://example.org/piece', $home ) );
check( 'an absolute link back to this site is not external', 'Continue', tae_onward_label( $home . '/about/', $home ) );
check( 'an unrecognised internal path', 'Continue', tae_onward_label( '/contact/', $home ) );
check( 'an absolute interview URL still reads as an interview', 'Watch the interview', tae_onward_label( $home . '/interview-series/', $home ) );

/* ── result ─────────────────────────────────────────────────────────────── */

echo "\n" . ( $failures ? "$failures FAILED\n" : "all passed\n" );
exit( $failures ? 1 : 0 );
