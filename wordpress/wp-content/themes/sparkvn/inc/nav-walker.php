<?php
/**
 * Walkers matching html/shell.js markup.
 *
 * @package Spark_VN
 */

/**
 * Desktop mega-menu walker (3 levels).
 */
class SparkVN_Walker_Nav extends Walker_Nav_Menu {

	/**
	 * Starts the list before the elements are added.
	 *
	 * @param string $output Output.
	 * @param int    $depth  Depth.
	 * @param mixed  $args   Args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === $depth ) {
			$output .= '<div class="nav-drop">';
		} else {
			$output .= '<div class="nav-drop-sub">';
		}
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @param string $output Output.
	 * @param int    $depth  Depth.
	 * @param mixed  $args   Args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</div>';
	}

	/**
	 * Starts the element output.
	 *
	 * @param string  $output Output.
	 * @param WP_Post $item   Item.
	 * @param int     $depth  Depth.
	 * @param mixed   $args   Args.
	 * @param int     $id     ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$url   = ! empty( $item->url ) ? $item->url : '';
		$current = in_array( 'current-menu-item', (array) $item->classes, true )
			|| in_array( 'current-menu-ancestor', (array) $item->classes, true )
			|| in_array( 'current-menu-parent', (array) $item->classes, true );

		$has_children = in_array( 'menu-item-has-children', (array) $item->classes, true );
		$aria         = $current ? ' aria-current="page"' : '';

		if ( 0 === $depth ) {
			if ( $has_children ) {
				$output .= '<div class="nav-item"><a href="' . esc_url( $url ) . '"' . $aria . '>' . esc_html( $title ) . '<span class="care"></span></a>';
			} else {
				$output .= '<a href="' . esc_url( $url ) . '"' . $aria . '>' . esc_html( $title ) . '</a>';
			}
			return;
		}

		if ( 1 === $depth ) {
			$cls = 'nav-drop-item' . ( $has_children ? ' has-sub' : '' );
			$output .= '<div class="' . esc_attr( $cls ) . '"><a href="' . esc_url( $url ) . '"' . $aria . '>' . esc_html( $title ) . '</a>';
			return;
		}

		$output .= '<a href="' . esc_url( $url ) . '"' . $aria . '>' . esc_html( $title ) . '</a>';
	}

	/**
	 * Ends the element output.
	 *
	 * @param string  $output Output.
	 * @param WP_Post $item   Item.
	 * @param int     $depth  Depth.
	 * @param mixed   $args   Args.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$has_children = in_array( 'menu-item-has-children', (array) $item->classes, true );
		if ( 0 === $depth && $has_children ) {
			$output .= '</div>';
		} elseif ( 1 === $depth ) {
			$output .= '</div>';
		}
	}
}

/**
 * Mobile details walker.
 */
class SparkVN_Walker_Mobile extends Walker_Nav_Menu {

	/**
	 * Starts the list before the elements are added.
	 *
	 * @param string $output Output.
	 * @param int    $depth  Depth.
	 * @param mixed  $args   Args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === $depth ) {
			$output .= '<div class="mnav-sub">';
		}
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @param string $output Output.
	 * @param int    $depth  Depth.
	 * @param mixed  $args   Args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === $depth ) {
			$output .= '</div>';
		}
	}

	/**
	 * Starts the element output.
	 *
	 * @param string  $output Output.
	 * @param WP_Post $item   Item.
	 * @param int     $depth  Depth.
	 * @param mixed   $args   Args.
	 * @param int     $id     ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$url   = ! empty( $item->url ) ? $item->url : '';
		$has_children = in_array( 'menu-item-has-children', (array) $item->classes, true );
		$open = in_array( 'current-menu-item', (array) $item->classes, true )
			|| in_array( 'current-menu-ancestor', (array) $item->classes, true )
			? ' open' : '';

		if ( 0 === $depth && $has_children ) {
			$output .= '<details class="mnav-group' . esc_attr( $open ) . '"><summary>' . esc_html( $title ) . '<span class="chev"></span></summary>';
			$output .= '<a href="' . esc_url( $url ) . '" data-close>' . esc_html( sprintf( /* translators: %s menu label */ __( 'All %s', 'sparkvn' ), $title ) ) . '</a>';
			return;
		}

		$cls = ( 2 === $depth ) ? ' class="sub2"' : '';
		$output .= '<a href="' . esc_url( $url ) . '"' . $cls . ' data-close>' . esc_html( $title ) . '</a>';
	}

	/**
	 * Ends the element output.
	 *
	 * @param string  $output Output.
	 * @param WP_Post $item   Item.
	 * @param int     $depth  Depth.
	 * @param mixed   $args   Args.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$has_children = in_array( 'menu-item-has-children', (array) $item->classes, true );
		if ( 0 === $depth && $has_children ) {
			$output .= '</details>';
		}
	}
}
