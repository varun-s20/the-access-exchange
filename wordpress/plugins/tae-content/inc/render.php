<?php
/**
 * Shared markup helpers. Every template composes from these so the same element
 * cannot be written two slightly different ways in two files.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Amara Osei, Staff Engineer" — or just the name if there is no role.
 *
 * @param int|WP_Post $post Interview.
 * @return string
 */
function tae_guest( $post ) {
	$id   = is_object( $post ) ? $post->ID : (int) $post;
	$name = (string) get_post_meta( $id, 'tae_guest_name', true );
	$role = (string) get_post_meta( $id, 'tae_guest_role', true );

	if ( $name && $role ) {
		return $name . ', ' . $role;
	}
	return $name ? $name : $role;
}

/**
 * The interview's category term.
 *
 * @param int|WP_Post $post Interview.
 * @return WP_Term|null
 */
function tae_cat_term( $post ) {
	$id    = is_object( $post ) ? $post->ID : (int) $post;
	$terms = get_the_terms( $id, 'tae_category' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * The interview's category slug, or '' when it has none.
 *
 * An interview with no category still renders; it is simply hidden by every
 * filter except "All".
 *
 * @param int|WP_Post $post Interview.
 * @return string
 */
function tae_cat_slug( $post ) {
	$term = tae_cat_term( $post );
	return $term ? $term->slug : '';
}

/**
 * The interview's category name.
 *
 * @param int|WP_Post $post Interview.
 * @return string
 */
function tae_cat_name( $post ) {
	$term = tae_cat_term( $post );
	return $term ? $term->name : '';
}

/**
 * The insight's topic slug. Interviews always report "interview".
 *
 * @param WP_Post $post Interview or insight.
 * @return string
 */
function tae_topic_slug( $post ) {
	if ( 'tae_interview' === $post->post_type ) {
		return 'interview';
	}
	$terms = get_the_terms( $post->ID, 'tae_topic' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';
}

/**
 * The insight's topic label. Interviews always report "Interview".
 *
 * @param WP_Post $post Interview or insight.
 * @return string
 */
function tae_topic_name( $post ) {
	if ( 'tae_interview' === $post->post_type ) {
		return 'Interview';
	}
	$terms = get_the_terms( $post->ID, 'tae_topic' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
}

/**
 * Where a stream tile points.
 *
 * Insights always land on their own page. Interviews have no single page by
 * design, so they always land on the archive.
 *
 * This used to send an insight straight to its tae_link when one was set, which
 * dated from when a bodiless insight had no page worth visiting. It has one now —
 * headline, dateline, dek, image, a labelled route onward, three things to read
 * next — so skipping past it only cost the reader context and left every insight
 * page unreachable from the site itself.
 *
 * tae_link is still honoured; it is the onward button ON that page rather than a
 * replacement for it. To go back to the old behaviour, return the link here when
 * it is set.
 *
 * @param WP_Post $post Interview or insight.
 * @return string
 */
function tae_destination( $post ) {
	if ( 'tae_interview' === $post->post_type ) {
		return '/interview-series/#episodes';
	}
	return get_permalink( $post );
}

/**
 * Label the onward link on an insight that has not been written yet.
 *
 * A button reading "Continue reading" over a link to a video is a small lie, and
 * the reader finds out one click later. So the label names the destination.
 *
 * @param string $url Destination.
 * @param string $home Site URL, for spotting external links. Injected for testing.
 * @return string
 */
function tae_onward_label( $url, $home = null ) {

	if ( '' === (string) $url ) {
		return '';
	}

	if ( false !== strpos( $url, '/interview-series/' ) ) {
		return 'Watch the interview';
	}

	if ( false !== strpos( $url, '/university-partnerships/' ) ) {
		return 'Read the partnership guide';
	}

	$home = null === $home ? home_url() : $home;

	// Absolute, and not pointing back at this site.
	if ( 0 === strpos( $url, 'http' ) && false === strpos( $url, $home ) ) {
		return 'Read it in full';
	}

	return 'Continue';
}

/**
 * A featured image at a registered size.
 *
 * Returns '' when the post has no thumbnail, which is also what switches an
 * insight tile into its .it--text variant.
 *
 * @param int|WP_Post $post  Post.
 * @param string      $size  Registered size.
 * @param bool        $eager Mark as the LCP candidate.
 * @return string
 */
function tae_img( $post, $size, $eager = false ) {
	$id = is_object( $post ) ? $post->ID : (int) $post;
	if ( ! has_post_thumbnail( $id ) ) {
		return '';
	}

	$attr = $eager
		? array(
			'decoding'      => 'async',
			'fetchpriority' => 'high',
			'loading'       => 'eager',
		)
		: array(
			'decoding' => 'async',
			'loading'  => 'lazy',
		);

	return get_the_post_thumbnail( $id, $size, $attr );
}

/**
 * The click-to-load YouTube poster button.
 *
 * Nothing is requested from youtube.com until this is pressed — global.js §06
 * swaps the poster for the player. That is the single biggest reason these pages
 * hit their PageSpeed target, so the data-yt contract must not drift.
 *
 * @param WP_Post $post    Interview.
 * @param string  $classes Extra classes after "vid".
 * @param string  $size    Registered image size.
 * @param bool    $eager   Mark as the LCP candidate.
 * @return string
 */
function tae_vid( $post, $classes, $size, $eager = false ) {
	$title    = get_the_title( $post );
	$video_id = (string) get_post_meta( $post->ID, 'tae_youtube', true );
	$duration = (string) get_post_meta( $post->ID, 'tae_duration', true );

	$out  = sprintf(
		'<button class="%s" type="button" data-yt="%s" data-title="%s" aria-label="Play: %s">',
		esc_attr( trim( 'vid ' . $classes ) ),
		esc_attr( $video_id ),
		esc_attr( $title ),
		esc_attr( $title )
	);
	$out .= tae_img( $post, $size, $eager );
	$out .= '<span class="vid-play" aria-hidden="true"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></span></span>';
	if ( $duration ) {
		$out .= '<span class="vid-dur lab">' . esc_html( $duration ) . '</span>';
	}
	$out .= '</button>';

	return $out;
}

/**
 * "Ep 41", or '' when the interview has no number.
 *
 * @param int|WP_Post $post   Interview.
 * @param string      $prefix "Ep" or "Episode".
 * @return string
 */
function tae_episode( $post, $prefix = 'Ep' ) {
	$id  = is_object( $post ) ? $post->ID : (int) $post;
	$num = (string) get_post_meta( $id, 'tae_episode', true );
	return '' === $num ? '' : $prefix . ' ' . $num;
}

/**
 * A machine-readable date.
 *
 * @param WP_Post $post   Post.
 * @param string  $format Display format.
 * @return string
 */
function tae_time( $post, $format = 'M j' ) {
	return sprintf(
		'<time datetime="%s">%s</time>',
		esc_attr( get_the_date( 'Y-m-d', $post ) ),
		esc_html( get_the_date( $format, $post ) )
	);
}

/**
 * One of the sprite icons defined at the top of header.html.
 *
 * @param string $id Sprite id without the hash: arrow, left, right, search, close.
 * @return string
 */
function tae_icon( $id ) {
	return sprintf(
		'<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-%s"/></svg>',
		esc_attr( $id )
	);
}

/**
 * The staggered reveal delay, cycling 0 / 60 / 120 / 180ms in groups of four,
 * exactly as the prototype hardcoded it.
 *
 * @param int $index Zero-based position.
 * @return string A style attribute, or '' for the first of each group.
 */
function tae_delay( $index ) {
	$step = ( $index % 4 ) * 60;
	return $step ? sprintf( ' style="--d:%dms"', $step ) : '';
}

/**
 * Load a template with variables in scope.
 *
 * @param string $name Template file without .php.
 * @param array  $vars Extracted into the template's scope.
 * @return string
 */
function tae_template( $name, $vars = array() ) {
	$file = TAE_DIR . 'templates/' . $name . '.php';
	if ( ! file_exists( $file ) ) {
		return '';
	}

	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled, internal keys only.
	extract( $vars, EXTR_SKIP );

	ob_start();
	include $file;
	return trim( (string) ob_get_clean() );
}
