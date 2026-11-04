<?php
/**
 * Core block styles matching Spark classes.
 *
 * @package Spark_VN
 */

/**
 * Register block styles.
 */
function sparkvn_register_block_styles(): void {
	register_block_style(
		'core/group',
		array(
			'name'  => 'ivory2',
			'label' => __( 'Ivory 2', 'sparkvn' ),
			'inline_style' => '.is-style-ivory2{background:var(--ivory-2)}',
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'purple',
			'label' => __( 'Purple', 'sparkvn' ),
			'inline_style' => '.is-style-purple{background:var(--purple);color:#F4EBDE}',
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'tight',
			'label' => __( 'Tight', 'sparkvn' ),
		)
	);
	register_block_style(
		'core/columns',
		array(
			'name'  => 'split',
			'label' => __( 'Split', 'sparkvn' ),
		)
	);
	register_block_style(
		'core/columns',
		array(
			'name'  => 'split-rev',
			'label' => __( 'Split reverse', 'sparkvn' ),
		)
	);
	register_block_style(
		'core/details',
		array(
			'name'  => 'faq',
			'label' => __( 'FAQ item', 'sparkvn' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'gold',
			'label' => __( 'Gold', 'sparkvn' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'ghost',
			'label' => __( 'Ghost', 'sparkvn' ),
		)
	);
	register_block_style(
		'core/list',
		array(
			'name'  => 'chips',
			'label' => __( 'Chips', 'sparkvn' ),
		)
	);
	register_block_style(
		'core/heading',
		array(
			'name'  => 'title-hair',
			'label' => __( 'Title hair', 'sparkvn' ),
		)
	);
}
add_action( 'init', 'sparkvn_register_block_styles' );

/**
 * Block editor category.
 *
 * @param array $categories Categories.
 * @return array
 */
function sparkvn_block_categories( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'sparkvn',
			'title' => __( 'Spark VN', 'sparkvn' ),
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'sparkvn_block_categories' );
