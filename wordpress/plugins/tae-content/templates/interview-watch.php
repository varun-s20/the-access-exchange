<?php
/**
 * Home §11 — the watch poster.
 *
 * Replaces index.html lines 403-413. The <h2> and .lede above it are prose and
 * stay in the Elementor widget.
 *
 * @var WP_Post $post
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="watch-grid">
	<?php echo tae_vid( $post, '', 'tae-watch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<div class="watch-cta">
	<a href="/interview-series/#episodes" class="btn">Browse every episode</a>
	<a href="https://www.youtube.com/@theaccessexchange" rel="noopener" class="btn btn--ghost">Subscribe on YouTube</a>
</div>
