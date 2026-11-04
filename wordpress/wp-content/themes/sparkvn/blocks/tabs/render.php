<?php
/**
 * Tabs front-end. Each inner tab-panel (or legacy group) is one panel.
 *
 * @var array         $attributes Attributes.
 * @var string        $content    Inner HTML.
 * @var WP_Block|null $block      Parsed block.
 * @package Spark_VN
 */

$variation = $attributes['variation'] ?? 'default';
$heading   = $attributes['heading'] ?? '';
$labels    = array_values( array_filter( array_map( 'trim', preg_split( '/\s*\|\s*|\r\n|\r|\n|\\\\n/', (string) ( $attributes['labels'] ?? '' ) ) ) ) );
$cls       = 'tabs';
if ( 'opt-tabs' === $variation ) {
	$cls .= ' opt-tabs';
}
if ( 'school-tabs' === $variation ) {
	$cls .= ' school-tabs';
}
$list_cls = 'tab-list';
if ( 'letters' === $variation ) {
	$list_cls = 'spark-letters tab-list';
}

$from_children = array();
$child_count   = 0;
if ( isset( $block ) && $block instanceof WP_Block && $block->inner_blocks ) {
	foreach ( $block->inner_blocks as $inner ) {
		++$child_count;
		if ( 'sparkvn/tab-panel' === $inner->name ) {
			$from_children[] = trim( (string) ( $inner->attributes['label'] ?? '' ) );
		}
	}
}
if ( $child_count && count( $from_children ) === $child_count ) {
	$labels = array();
	foreach ( $from_children as $i => $label ) {
		$labels[] = '' !== $label ? $label : sprintf( /* translators: %d tab index */ __( 'Tab %d', 'sparkvn' ), $i + 1 );
	}
}

$panels = array();
if ( $content ) {
	$panels = sparkvn_split_top_level_divs( $content );
	if ( ! $panels ) {
		$panels = array( '<div class="tab-panel active copy">' . $content . '</div>' );
	}
}

$count = max( count( $labels ), count( $panels ) );
if ( $count < 1 ) {
	$count = 1;
}
while ( count( $labels ) < $count ) {
	$labels[] = sprintf( __( 'Tab %d', 'sparkvn' ), count( $labels ) + 1 );
}
?>
<div class="<?php echo esc_attr( $cls ); ?>">
	<?php if ( $heading ) : ?><div class="sec-head"><h2><?php echo esc_html( $heading ); ?></h2></div><?php endif; ?>
	<div class="<?php echo esc_attr( $list_cls ); ?>" role="tablist">
		<?php foreach ( $labels as $i => $label ) : ?>
			<button class="<?php echo 0 === $i ? 'active' : ''; ?>" data-tab="<?php echo esc_attr( (string) $i ); ?>"><?php
			if ( str_contains( $label, '::' ) ) {
				[ $lead, $rest ] = array_pad( explode( '::', $label, 2 ), 2, '' );
				if ( 'opt-tabs' === $variation ) {
					echo '<span class="k">' . esc_html( $lead ) . '</span>' . esc_html( $rest );
				} elseif ( 'letters' === $variation ) {
					echo '<span class="L">' . esc_html( $lead ) . '</span><span class="T">' . esc_html( $rest ) . '</span>';
				} else {
					echo esc_html( $lead . $rest );
				}
			} else {
				echo esc_html( $label );
			}
			?></button>
		<?php endforeach; ?>
	</div>
	<div class="tab-panels">
		<?php
		for ( $i = 0; $i < $count; $i++ ) {
			$panel = $panels[ $i ] ?? '<div class="tab-panel copy"></div>';
			if ( false === strpos( $panel, 'tab-panel' ) ) {
				$panel = preg_replace( '/^(<div\b)/i', '$1 class="tab-panel"', $panel, 1 );
			}
			$active = 0 === $i ? ' active' : '';
			$panel  = preg_replace( '/class="([^"]*)"/', 'class="$1' . $active . '"', $panel, 1 );
			if ( false === strpos( $panel, 'data-tab-panel' ) ) {
				$panel = preg_replace( '/^(<div\b)/i', '$1 data-tab-panel="' . esc_attr( (string) $i ) . '"', $panel, 1 );
			}
			echo $panel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
</div>
