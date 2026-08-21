<?php
/**
 * The category filter is pure CSS - :has() plus a sibling combinator, no JS and no
 * layout thrash. That only works if every term has its own rule, so the rules have
 * to be generated from the live taxonomy rather than hardcoded.
 *
 * global.css:677-681 and :949-953 hold the hand-written originals. They stay where
 * they are: for the five seeded slugs they generate identical behaviour, and they
 * keep the filter working if this plugin is ever deactivated.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per-view scope selectors and legacy radio IDs.
 *
 * NOTE on the archive scope. The stylesheet writes:
 *
 *     .tae-interviews .sorts:has(#f-eng:checked) ~ .eps li
 *
 * .sorts sits inside .index-head, and .eps is a sibling of .index-head - not of
 * .sorts. The sibling combinator therefore never matches and the Interview Series
 * filter has never worked. Scoping to .index-head fixes it without moving markup.
 *
 * @return array
 */
function tae_filter_config() {
	return array(
		// The home wall this filtered went in Phase 7, but the config stays: it is
		// the one place the hand-written fallback in global.css:677-681 can still
		// be asserted against character for character, which is what keeps the
		// generator honest if the seeded slugs are ever edited. tests/test-tae.php
		// §1 is that assertion.
		'wall'    => array(
			'scope'  => '.tae-home .filters',
			'target' => '.grid .card',
			'name'   => 'cat',
			'ids'    => array(
				''           => 'c-all',
				'leadership' => 'c-lead',
				'founders'   => 'c-fnd',
				'industry'   => 'c-ind',
				'career'     => 'c-car',
				'campus'     => 'c-cam',
			),
		),
		'archive' => array(
			'scope'  => '.tae-interviews .index-head',
			'target' => '.eps li',
			'name'   => 'cat',
			'ids'    => array(
				''           => 'f-all',
				'leadership' => 'f-lead',
				'founders'   => 'f-fnd',
				'industry'   => 'f-ind',
				'career'     => 'f-car',
				'campus'     => 'f-cam',
			),
		),
	);
}

/**
 * How many times a given view has rendered on this request.
 *
 * The first instance keeps the legacy IDs so global.js binds exactly as it does
 * today. Later instances get suffixed IDs and their own scoping class, because
 * duplicate IDs would break both the CSS filter and the search field.
 *
 * @param string $view View key.
 * @return int 1 for the first render, 2 for the second, and so on.
 */
function tae_instance( $view ) {
	static $counts = array();
	$counts[ $view ] = isset( $counts[ $view ] ) ? $counts[ $view ] + 1 : 1;
	return $counts[ $view ];
}

/**
 * Build the radio + label chips and the matching CSS.
 *
 * @param string $view     'wall' or 'archive'.
 * @param int    $instance Instance number from tae_instance().
 * @param string $active   Term slug the shortcode filtered to server-side, or ''.
 * @return array{chips:string,style:string,class:string}
 */
function tae_filter_markup( $view, $instance, $active = '' ) {

	$config = tae_filter_config();
	if ( ! isset( $config[ $view ] ) ) {
		return array(
			'chips' => '',
			'style' => '',
			'class' => '',
		);
	}

	$config = $config[ $view ];
	$first  = ( 1 === $instance );
	$suffix = $first ? '' : '-' . $instance;

	// A later instance cannot reuse the legacy scope selector, or its rules would
	// also match the first instance. It gets a unique class on the same element
	// instead of a wrapper, because a wrapper would break the sibling combinator.
	$extra_class = $first ? '' : 'tae-fi-' . $view . '-' . $instance;
	$scope       = $first ? $config['scope'] : '.' . $extra_class;

	$terms = get_terms(
		array(
			'taxonomy'   => 'tae_category',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}

	$radio_id = function ( $slug ) use ( $config, $first, $suffix ) {
		if ( $first && isset( $config['ids'][ $slug ] ) ) {
			return $config['ids'][ $slug ];
		}
		// Unknown term, or an instance past the first.
		return 'tae-' . substr( md5( $config['name'] . $slug ), 0, 6 ) . $suffix;
	};

	// "All" first, then every term. data-cat is for tae-archive.js - the CSS filter
	// keys off the ID, but JavaScript needs the slug and a label only carries a name.
	//
	// Which chip starts checked follows what the shortcode actually rendered. It
	// used to be "All", always - so [tae_interviews category="leadership"] drew a
	// leadership-only grid under a control group insisting nothing was filtered.
	$active = (string) $active;

	$chips = sprintf(
		'<input type="radio" name="%s" id="%s" data-cat=""%s><label class="chip" for="%2$s">All</label>',
		esc_attr( $config['name'] . $suffix ),
		esc_attr( $radio_id( '' ) ),
		'' === $active ? ' checked' : ''
	);
	$rules = array();

	foreach ( $terms as $term ) {
		$id     = $radio_id( $term->slug );
		$chips .= sprintf(
			'<input type="radio" name="%s" id="%s" data-cat="%s"%s><label class="chip" for="%2$s">%s</label>',
			esc_attr( $config['name'] . $suffix ),
			esc_attr( $id ),
			esc_attr( $term->slug ),
			$active === $term->slug ? ' checked' : '',
			esc_html( $term->name )
		);

		$rules[] = sprintf(
			'%s:has(#%s:checked) ~ %s:not([data-cat="%s"])',
			$scope,
			$id,
			$config['target'],
			$term->slug
		);
	}

	$style = $rules ? '<style>' . implode( ',', $rules ) . '{display:none}</style>' : '';

	return array(
		'chips' => $chips,
		'style' => $style,
		'class' => $extra_class,
	);
}
