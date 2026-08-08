<?php
/**
 * Interview Series §03 - the archive, with filter chips and load-more.
 *
 * Replaces interview-series.html lines 160-362.
 *
 * @var WP_Query $query
 * @var array    $filter   From tae_filter_markup(): chips, style, class.
 * @var int      $instance Render count for this view on this page.
 * @var int      $count    Posts per page.
 * @var string   $category Active category slug, or ''.
 * @var string   $heading  Section heading.
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_heading = $heading ? $heading : 'Every episode, in full.';
$tae_suffix  = 1 === $instance ? '' : '-' . $instance;
$tae_more    = $query->max_num_pages > 1;

// The generated rules go before the section so nothing sits between .index-head
// and .eps, which the sibling combinator depends on.
echo $filter['style']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<section class="index" id="episodes<?php echo esc_attr( $tae_suffix ); ?>" aria-labelledby="index-h<?php echo esc_attr( $tae_suffix ); ?>">
	<div class="shell">
		<div class="index-head<?php echo $filter['class'] ? ' ' . esc_attr( $filter['class'] ) : ''; ?>">
			<h2 id="index-h<?php echo esc_attr( $tae_suffix ); ?>" data-rv><?php echo esc_html( $tae_heading ); ?></h2>
			<div class="sorts" role="group" aria-label="Filter episodes" data-tae-sorts>
				<?php echo $filter['chips']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<ul class="eps"
			data-tae-list="archive"
			data-tae-page="1"
			data-tae-count="<?php echo esc_attr( $count ); ?>"
			data-tae-cat="<?php echo esc_attr( $category ); ?>">
			<?php
			foreach ( $query->posts as $tae_item ) {
				echo tae_template( 'interview-archive-item', array( 'item' => $tae_item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</ul>

		<div class="pager">
			<?php // Inline style, not [hidden]: .btn sets display:inline-flex, which out-ranks the UA's [hidden]{display:none}. ?>
			<a href="#episodes" class="btn btn--out" data-tae-more<?php echo $tae_more ? '' : ' style="display:none"'; ?>>Earlier episodes</a>
			<a href="https://www.youtube.com/@theaccessexchange" rel="noopener" class="btn">Subscribe</a>
		</div>
	</div>
</section>
