<?php
/**
 * Insights §01 + §02 — the search header, the topic sidebar and the stream.
 *
 * Replaces insights.html lines 46-208.
 *
 * The whole stream renders at once. global.js §09 caches the card list on boot,
 * measures a FLIP transition around one synchronous mutation and filters the DOM,
 * so there is nothing here to paginate against — and the counters have to be able
 * to tell the truth about the full set.
 *
 * @var WP_Query $query
 * @var int      $instance
 * @var string   $heading
 *
 * @package TAE_Content
 */

defined( 'ABSPATH' ) || exit;

$tae_suffix = 1 === $instance ? '' : '-' . $instance;
$tae_posts  = tae_lead_first( $query->posts );
$tae_total  = count( $tae_posts );

// Counts come from the posts actually rendered, not from the taxonomy. global.js
// §09 filters the DOM, so a sidebar number that disagrees with what filtering can
// reach would just be a lie the visitor can catch.
$tae_tally = array();
foreach ( $tae_posts as $tae_item ) {
	$tae_slug               = tae_topic_slug( $tae_item );
	$tae_tally[ $tae_slug ] = isset( $tae_tally[ $tae_slug ] ) ? $tae_tally[ $tae_slug ] + 1 : 1;
}

// All, then the interview bucket (a post type, not a term), then every topic.
$tae_buckets = array(
	array(
		'slug'  => 'all',
		'name'  => 'All',
		'count' => $tae_total,
	),
	array(
		'slug'  => 'interview',
		'name'  => 'Interviews',
		'count' => isset( $tae_tally['interview'] ) ? $tae_tally['interview'] : 0,
	),
);

$tae_terms = get_terms(
	array(
		'taxonomy'   => 'tae_topic',
		'hide_empty' => false,
	)
);
if ( ! is_wp_error( $tae_terms ) ) {
	foreach ( $tae_terms as $tae_term ) {
		$tae_buckets[] = array(
			'slug'  => $tae_term->slug,
			'name'  => $tae_term->name,
			'count' => isset( $tae_tally[ $tae_term->slug ] ) ? $tae_tally[ $tae_term->slug ] : 0,
		);
	}
}
?>
<section class="top">
	<div class="shell">
		<div class="top-row">
			<h1><?php echo esc_html( $heading ? $heading : 'Insights' ); ?></h1>
			<p class="tally lab">
				<b id="tally<?php echo esc_attr( $tae_suffix ); ?>"><?php echo esc_html( $tae_total ); ?></b>
				<span id="tallyWord<?php echo esc_attr( $tae_suffix ); ?>"><?php echo esc_html( 1 === $tae_total ? 'piece published' : 'pieces published' ); ?></span>
			</p>
		</div>

		<div class="hunt" id="hunt<?php echo esc_attr( $tae_suffix ); ?>">
			<?php echo tae_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<label class="sr" for="q<?php echo esc_attr( $tae_suffix ); ?>">Search everything we have published</label>
			<input id="q<?php echo esc_attr( $tae_suffix ); ?>" type="search" placeholder="Search by title, guest or topic" autocomplete="off">
			<button type="button" id="clear<?php echo esc_attr( $tae_suffix ); ?>" aria-label="Clear search">
				<?php echo tae_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
	</div>
</section>

<section class="body" aria-label="All published pieces">
	<div class="shell body-grid">

		<nav class="topics lab" id="topics<?php echo esc_attr( $tae_suffix ); ?>" aria-label="Filter by topic">
			<?php foreach ( $tae_buckets as $tae_i => $tae_bucket ) : ?>
				<button type="button" data-topic="<?php echo esc_attr( $tae_bucket['slug'] ); ?>" aria-pressed="<?php echo 0 === $tae_i ? 'true' : 'false'; ?>">
					<?php echo esc_html( $tae_bucket['name'] ); ?><span class="ct"><?php echo esc_html( $tae_bucket['count'] ); ?></span>
				</button>
			<?php endforeach; ?>
		</nav>

		<div class="stream" id="stream<?php echo esc_attr( $tae_suffix ); ?>">
			<?php
			foreach ( $tae_posts as $tae_i => $tae_item ) {
				echo tae_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'insights-stream-item',
					array(
						'item' => $tae_item,
						'lead' => 0 === $tae_i,
					)
				);
			}
			?>

			<p class="empty" id="empty<?php echo esc_attr( $tae_suffix ); ?>" hidden>
				<b>Nothing matches that.</b>
				Try a guest's name, a job title, or clear the search to see everything.
			</p>
		</div>
	</div>
</section>
