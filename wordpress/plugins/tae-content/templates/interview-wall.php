<?php
/**
 * Home §05 — the wall: filter chips, search, twelve cards.
 *
 * Replaces index.html lines 161-252. The .tag and .wall-head above it are prose
 * and stay in the Elementor widget.
 *
 * .filters and .grid must remain siblings — the CSS filter is a sibling
 * combinator — so the generated <style> is printed before both.
 *
 * @var WP_Query $query
 * @var array    $filter
 * @var int      $instance
 * @var int      $count
 * @var string   $category
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_suffix = 1 === $instance ? '' : '-' . $instance;

echo $filter['style']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="filters<?php echo $filter['class'] ? ' ' . esc_attr( $filter['class'] ) : ''; ?>" role="group" aria-label="Filter interviews by category" data-tae-sorts>
	<?php echo $filter['chips']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<span class="srch">
		<?php echo tae_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<label class="sr" for="q<?php echo esc_attr( $tae_suffix ); ?>">Find an interview</label>
		<input id="q<?php echo esc_attr( $tae_suffix ); ?>" type="search" placeholder="Find an interview" autocomplete="off" data-tae-search>
	</span>
</div>

<div class="grid"
	data-tae-list="wall"
	data-tae-page="1"
	data-tae-count="<?php echo esc_attr( $count ); ?>"
	data-tae-cat="<?php echo esc_attr( $category ); ?>">
	<?php
	foreach ( $query->posts as $tae_i => $tae_item ) {
		echo tae_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'interview-wall-item',
			array(
				'item'  => $tae_item,
				'index' => $tae_i,
			)
		);
	}
	?>
</div>

<div class="wall-more">
	<a href="/interview-series/#episodes" class="btn btn--ghost">View the full archive</a>
</div>
