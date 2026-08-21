<?php
/**
 * What search engines are told about an episode.
 *
 * Handoff §20 asks for "SEO titles/descriptions, heading hierarchy, XML
 * sitemap/indexing readiness". Yoast covers the pages. It does not know that an
 * interview is a video, and for an interview series the video result is the
 * organic surface that matters - a thumbnail, a duration and an upload date in
 * the result, rather than a line of grey text.
 *
 * Two things here, both invisible on the page:
 *
 *   1. A VideoObject for every episode that has a YouTube ID.
 *   2. A title and description for episodes whose Yoast fields are empty, built
 *      from the fields the owner already filled in. §24 asks the owner to be
 *      able to edit SEO titles; this means they only have to when the generated
 *      one is not good enough, rather than on every single episode forever.
 *
 * Both defer to anything typed by hand. Nothing here overwrites a real answer.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'tae_video_schema', 20 );

/**
 * VideoObject for the episode page.
 *
 * Emitted standalone rather than grafted into Yoast's graph: Yoast's schema
 * pieces are a versioned internal API, and a separate script tag is understood
 * by every consumer without depending on Yoast being installed at all.
 */
function tae_video_schema() {

	if ( ! is_singular( 'tae_interview' ) ) {
		return;
	}

	$post     = get_queried_object();
	$video_id = $post ? (string) get_post_meta( $post->ID, 'tae_youtube', true ) : '';

	// No video, no VideoObject. An episode page with nothing to play is a
	// scheduling accident, not a video.
	if ( ! $video_id ) {
		return;
	}

	$standfirst = (string) get_post_meta( $post->ID, 'tae_standfirst', true );

	$graph = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'VideoObject',
		'name'         => get_the_title( $post ),
		'description'  => $standfirst ? $standfirst : get_the_title( $post ),
		'uploadDate'   => get_the_date( 'c', $post ),
		'url'          => get_permalink( $post ),
		'embedUrl'     => 'https://www.youtube-nocookie.com/embed/' . $video_id,
		// The poster the visitor actually sees, when there is one; YouTube's
		// generated thumbnail otherwise, which always exists.
		'thumbnailUrl' => tae_schema_thumbnail( $post, $video_id ),
	);

	$duration = tae_iso_duration( (string) get_post_meta( $post->ID, 'tae_duration', true ) );
	if ( $duration ) {
		$graph['duration'] = $duration;
	}

	$guest = (string) get_post_meta( $post->ID, 'tae_guest_name', true );
	if ( $guest ) {
		$person = array(
			'@type' => 'Person',
			'name'  => $guest,
		);

		$org = (string) get_post_meta( $post->ID, 'tae_org', true );
		if ( $org ) {
			$person['worksFor'] = array(
				'@type' => 'Organization',
				'name'  => $org,
			);
		}

		$role = (string) get_post_meta( $post->ID, 'tae_guest_role', true );
		if ( $role ) {
			$person['jobTitle'] = $role;
		}

		// actor, not author: the guest appears in the video, they did not publish it.
		$graph['actor'] = $person;
	}

	printf(
		"<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output.
	);
}

/**
 * The poster, or YouTube's own thumbnail as the fallback.
 *
 * @param WP_Post $post     Interview.
 * @param string  $video_id YouTube ID.
 * @return string
 */
function tae_schema_thumbnail( $post, $video_id ) {

	if ( has_post_thumbnail( $post->ID ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'tae-cover' );
		if ( $src && ! empty( $src[0] ) ) {
			return $src[0];
		}
	}

	/*
	 * hqdefault, not maxresdefault.
	 *
	 * YouTube only generates maxresdefault.jpg for videos uploaded at 1280x720
	 * or better, so on anything smaller that URL 404s - and a thumbnailUrl that
	 * 404s can invalidate the whole video rich result, which is the only reason
	 * this VideoObject exists. hqdefault is generated for every video there has
	 * ever been, at 480x360, which clears Google's 60x30 minimum comfortably.
	 */
	return 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';
}

/**
 * "42:18" -> "PT42M18S", the only duration format schema.org accepts.
 *
 * Accepts H:MM:SS too, because a long-form interview can run over an hour and
 * the field does not stop anyone typing one.
 *
 * @param string $duration As the owner typed it.
 * @return string ISO 8601 duration, or '' if it was not a time.
 */
function tae_iso_duration( $duration ) {

	$duration = trim( $duration );
	if ( ! preg_match( '~^(?:(\d+):)?(\d{1,2}):(\d{2})$~', $duration, $m ) ) {
		return '';
	}

	$hours   = '' === $m[1] ? 0 : (int) $m[1];
	$minutes = (int) $m[2];
	$seconds = (int) $m[3];

	$out = 'PT';
	if ( $hours ) {
		$out .= $hours . 'H';
	}
	if ( $minutes ) {
		$out .= $minutes . 'M';
	}
	if ( $seconds ) {
		$out .= $seconds . 'S';
	}

	return 'PT' === $out ? '' : $out;
}

add_filter( 'wpseo_title', 'tae_default_seo_title' );

/**
 * A title for episodes nobody has written one for.
 *
 * "The first ninety days of a turnaround - Marcus Bell | The Access Exchange".
 * Yoast's own default would repeat the site template over a bare post title and
 * leave out the guest, who is the name people actually search for.
 *
 * @param string $title Yoast's answer.
 * @return string
 */
function tae_default_seo_title( $title ) {

	if ( ! is_singular( 'tae_interview' ) ) {
		return $title;
	}

	$post = get_queried_object();
	if ( ! $post || '' !== trim( (string) get_post_meta( $post->ID, '_yoast_wpseo_title', true ) ) ) {
		return $title;
	}

	$guest = (string) get_post_meta( $post->ID, 'tae_guest_name', true );

	return trim(
		get_the_title( $post )
		. ( $guest ? ' - ' . $guest : '' )
		. ' | ' . get_bloginfo( 'name' )
	);
}

add_filter( 'wpseo_metadesc', 'tae_default_seo_description' );

/**
 * The standfirst is already a one-or-two-sentence summary written for humans,
 * which is exactly what a meta description is.
 *
 * @param string $description Yoast's answer.
 * @return string
 */
function tae_default_seo_description( $description ) {

	if ( ! is_singular( 'tae_interview' ) ) {
		return $description;
	}

	$post = get_queried_object();
	if ( ! $post || '' !== trim( (string) get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ) ) ) {
		return $description;
	}

	$standfirst = trim( (string) get_post_meta( $post->ID, 'tae_standfirst', true ) );

	return '' === $standfirst ? $description : $standfirst;
}
