<?php
/**
 * Custom post types and taxonomies.
 *
 * @package Spark_VN
 */

/**
 * Register CPTs.
 */
function sparkvn_register_post_types(): void {
	register_post_type(
		'spark_program',
		array(
			'labels'        => array(
				'name'          => __( 'Programs', 'sparkvn' ),
				'singular_name' => __( 'Program', 'sparkvn' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'programs', 'with_front' => false ),
			'menu_icon'     => 'dashicons-welcome-learn-more',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ),
			'template'      => sparkvn_program_editor_template(),
			'template_lock' => false,
		)
	);

	register_post_type(
		'spark_prize',
		array(
			'labels'       => array(
				'name'          => __( 'Prizes', 'sparkvn' ),
				'singular_name' => __( 'Prize', 'sparkvn' ),
			),
			'public'       => true,
			'publicly_queryable' => false,
			'show_in_rest' => true,
			'has_archive'  => false,
			'rewrite'      => false,
			'menu_icon'    => 'dashicons-awards',
			'supports'     => array( 'title', 'thumbnail', 'excerpt', 'custom-fields' ),
		)
	);

	register_post_type(
		'spark_resource',
		array(
			'labels'       => array(
				'name'          => __( 'Resources', 'sparkvn' ),
				'singular_name' => __( 'Resource', 'sparkvn' ),
			),
			'public'       => true,
			'publicly_queryable' => false,
			'show_in_rest' => true,
			'has_archive'  => false,
			'rewrite'      => false,
			'menu_icon'    => 'dashicons-media-document',
			'supports'     => array( 'title', 'excerpt', 'custom-fields', 'thumbnail' ),
		)
	);

	register_post_type(
		'spark_lead',
		array(
			'labels'            => array(
				'name'          => __( 'Leads', 'sparkvn' ),
				'singular_name' => __( 'Lead', 'sparkvn' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_rest'      => false,
			'capability_type'   => 'post',
			'map_meta_cap'      => true,
			'menu_icon'         => 'dashicons-email-alt',
			'supports'          => array( 'title', 'custom-fields' ),
		)
	);

	register_taxonomy(
		'prize_group',
		'spark_prize',
		array(
			'labels'       => array(
				'name'          => __( 'Prize groups', 'sparkvn' ),
				'singular_name' => __( 'Prize group', 'sparkvn' ),
			),
			'public'       => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'rewrite'      => false,
		)
	);

	register_taxonomy(
		'program_level',
		'spark_program',
		array(
			'labels'       => array( 'name' => __( 'Program levels', 'sparkvn' ) ),
			'public'       => true,
			'show_in_rest' => true,
			'hierarchical' => false,
			'rewrite'      => false,
		)
	);
}
add_action( 'init', 'sparkvn_register_post_types' );

/**
 * Locked starter for a new Program. Generic placeholders — not Phonics copy.
 * Uses real blocks (not core/pattern) so contentOnly can expand without recovery cards.
 *
 * @return array<int,array<int,mixed>>
 */
function sparkvn_program_editor_template(): array {
	$img = 'assets/img/campus-classroom.jpg';

	$list = static function ( array $items ): array {
		$children = array();
		foreach ( $items as $item ) {
			$children[] = array( 'core/list-item', array( 'content' => $item ) );
		}
		return array( 'core/list', array(), $children );
	};

	$faq_item = static function ( string $question, string $answer ): array {
		return array(
			'core/details',
			array(
				'className' => 'is-style-faq faq-item',
				'summary'   => $question,
			),
			array(
				array(
					'core/group',
					array( 'className' => 'faq-a' ),
					array(
						array( 'core/paragraph', array( 'content' => $answer ) ),
					),
				),
			),
		);
	};

	$tab_panel = static function ( string $title ) use ( $list, $img ): array {
		return array(
			'sparkvn/tab-panel',
			array(
				'label'  => $title,
				'layout' => 'split',
			),
			array(
				array(
					'core/group',
					array( 'className' => 'split-media' ),
					array(
						array(
							'core/image',
							array(
								'url' => $img,
								'alt' => $title,
							),
						),
					),
				),
				array(
					'core/group',
					array( 'className' => 'split-copy copy' ),
					array(
						array( 'core/heading', array( 'level' => 3, 'content' => $title ) ),
						array( 'core/paragraph', array( 'placeholder' => __( 'Describe this level…', 'sparkvn' ) ) ),
						array( 'core/heading', array( 'level' => 4, 'content' => __( 'Skills Developed', 'sparkvn' ) ) ),
						$list(
							array(
								__( 'Skill one', 'sparkvn' ),
								__( 'Skill two', 'sparkvn' ),
								__( 'Skill three', 'sparkvn' ),
							)
						),
						array( 'core/heading', array( 'level' => 4, 'content' => __( 'Outcome', 'sparkvn' ) ) ),
						array( 'core/paragraph', array( 'placeholder' => __( 'What students can do after this level…', 'sparkvn' ) ) ),
					),
				),
			),
		);
	};

	return array(
		array(
			'sparkvn/hero',
			array(
				'variation' => 'page',
				'eyebrow'   => __( 'Programs', 'sparkvn' ),
				'title'     => __( 'Program title', 'sparkvn' ),
				'lead'      => __( 'Add a short introduction for this program.', 'sparkvn' ),
				'imageUrl'  => $img,
				'ariaLabel' => __( 'Program', 'sparkvn' ),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section--tight summary-wrap' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array(
							'core/group',
							array( 'className' => 'summary reveal' ),
							array(
								array(
									'core/paragraph',
									array(
										'className' => 'label',
										'content'   => '<span class="spark"></span>In short',
									),
								),
								array( 'core/heading', array( 'content' => __( 'The program at a glance', 'sparkvn' ) ) ),
								$list(
									array(
										__( 'Who it is for', 'sparkvn' ),
										__( 'What students develop', 'sparkvn' ),
										__( 'How placement works', 'sparkvn' ),
									)
								),
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array(
							'core/columns',
							array( 'className' => 'is-style-split split' ),
							array(
								array(
									'core/column',
									array( 'className' => 'split-copy copy' ),
									array(
										array( 'core/heading', array( 'content' => __( 'Program Overview', 'sparkvn' ) ) ),
										array(
											'core/paragraph',
											array( 'placeholder' => __( 'What this program teaches and who it helps.', 'sparkvn' ) ),
										),
										$list(
											array(
												__( 'Core skill one', 'sparkvn' ),
												__( 'Core skill two', 'sparkvn' ),
												__( 'Core skill three', 'sparkvn' ),
											)
										),
									),
								),
								array(
									'core/column',
									array( 'className' => 'split-media' ),
									array(
										array(
											'core/image',
											array(
												'url' => $img,
												'alt' => __( 'Program photo', 'sparkvn' ),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section bg-ivory2' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array(
							'core/group',
							array( 'className' => 'title-hair' ),
							array(
								array( 'core/heading', array( 'content' => __( 'Why this program matters', 'sparkvn' ) ) ),
							),
						),
						array(
							'core/group',
							array( 'className' => 'copy' ),
							array(
								array(
									'core/paragraph',
									array( 'placeholder' => __( 'Explain why these skills matter for school success.', 'sparkvn' ) ),
								),
								$list(
									array(
										__( 'Outcome one', 'sparkvn' ),
										__( 'Outcome two', 'sparkvn' ),
										__( 'Outcome three', 'sparkvn' ),
									)
								),
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array(
							'core/group',
							array( 'className' => 'sec-head' ),
							array(
								array( 'core/heading', array( 'content' => __( 'Program Levels', 'sparkvn' ) ) ),
							),
						),
						array(
							'sparkvn/tabs',
							array(
								'variation' => 'default',
								'labels'    => 'Level 1 | Level 2',
							),
							array(
								$tab_panel( __( 'Level 1', 'sparkvn' ) ),
								$tab_panel( __( 'Level 2', 'sparkvn' ) ),
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array(
							'core/group',
							array( 'className' => 'two-lists' ),
							array(
								array(
									'core/group',
									array( 'className' => 'copy' ),
									array(
										array( 'core/heading', array( 'content' => __( 'What Progress Looks Like', 'sparkvn' ) ) ),
										$list(
											array(
												__( 'Progress point one', 'sparkvn' ),
												__( 'Progress point two', 'sparkvn' ),
												__( 'Progress point three', 'sparkvn' ),
											)
										),
									),
								),
								array(
									'core/group',
									array( 'className' => 'copy' ),
									array(
										array( 'core/heading', array( 'content' => __( 'The Spark Difference', 'sparkvn' ) ) ),
										$list(
											array(
												__( 'Small classes', 'sparkvn' ),
												__( 'Measurable progress', 'sparkvn' ),
												__( 'School-aligned instruction', 'sparkvn' ),
											)
										),
									),
								),
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'container' ),
			array(
				array(
					'core/group',
					array( 'className' => 'inline-cta' ),
					array(
						array( 'core/heading', array( 'level' => 3, 'content' => __( 'Not Sure Where to Start?', 'sparkvn' ) ) ),
						array(
							'core/paragraph',
							array(
								'content' => __( 'Book a free assessment and receive personalized recommendations based on your child\'s age, school background, English level, and academic goals.', 'sparkvn' ),
							),
						),
						array(
							'core/paragraph',
							array(
								'content' => '<a class="btn btn-primary" href="/assessment/">Book a Free Assessment</a>',
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section bg-ivory2' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array(
							'core/group',
							array( 'className' => 'sec-head center' ),
							array(
								array( 'core/heading', array( 'content' => __( 'FAQs', 'sparkvn' ) ) ),
								array(
									'core/paragraph',
									array(
										'className' => 'lead',
										'content'   => __( 'Common questions about this program', 'sparkvn' ),
									),
								),
							),
						),
						array(
							'core/group',
							array( 'className' => 'faq-list cols-2' ),
							array(
								$faq_item(
									__( 'Who is this program for?', 'sparkvn' ),
									__( 'Add who this program is designed for, and how placement works.', 'sparkvn' )
								),
								$faq_item(
									__( 'How do parents track progress?', 'sparkvn' ),
									__( 'Describe reports, meetings, and the skills you measure.', 'sparkvn' )
								),
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section--tight section' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array(
							'core/group',
							array( 'className' => 'related' ),
							array(
								array(
									'core/paragraph',
									array(
										'className' => 'rel-head',
										'content'   => __( 'Related pages', 'sparkvn' ),
									),
								),
								array(
									'core/group',
									array( 'className' => 'rel-links' ),
									array(
										array(
											'core/paragraph',
											array(
												'content' => '<a class="btn btn-ghost btn-sm" href="/programs/">Programs</a><a class="btn btn-ghost btn-sm" href="/the-spark-method/">The Spark Method</a><a class="btn btn-ghost btn-sm" href="/assessment/">Book a Free Assessment</a>',
											),
										),
									),
								),
							),
						),
					),
				),
			),
		),
		array(
			'core/group',
			array( 'className' => 'section assess bg-purple cta-band' ),
			array(
				array(
					'core/group',
					array( 'className' => 'container' ),
					array(
						array( 'core/heading', array( 'content' => __( 'Prepare Your Child for Reading Success', 'sparkvn' ) ) ),
						array(
							'core/paragraph',
							array(
								'className' => 'lead',
								'content'   => __( 'Book a free assessment to find out your child\'s current reading level and the best starting point in our program.', 'sparkvn' ),
							),
						),
						array(
							'core/group',
							array( 'className' => 'cta-row' ),
							array(
								array(
									'core/paragraph',
									array(
										'content' => '<a href="/assessment/" class="btn btn-gold btn-lg">Book a Free Assessment</a>',
									),
								),
							),
						),
					),
				),
			),
		),
	);
}

/**
 * Default editor template for native posts (blog-post family).
 */
function sparkvn_post_template(): void {
	$post_type = get_post_type_object( 'post' );
	if ( ! $post_type ) {
		return;
	}
	$post_type->template = array(
		array(
			'core/paragraph',
			array(
				'placeholder' => __( 'Write the article…', 'sparkvn' ),
			),
		),
	);
}
add_action( 'init', 'sparkvn_post_template', 20 );

/**
 * Register post meta used by CPTs.
 */
function sparkvn_register_meta(): void {
	$program_keys = array( 'eyebrow', 'hero_image', 'hero_lead', 'cta_label', 'cta_url', 'menu_order_label' );
	foreach ( $program_keys as $key ) {
		register_post_meta(
			'spark_program',
			'spark_' . $key,
			array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			)
		);
	}

	register_post_meta(
		'spark_prize',
		'spark_image',
		array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		)
	);
	register_post_meta(
		'spark_program',
		'spark_image',
		array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		)
	);
	register_post_meta(
		'post',
		'spark_image',
		array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		)
	);
	register_post_meta(
		'spark_prize',
		'spark_points',
		array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		)
	);
	register_post_meta(
		'spark_prize',
		'spark_note',
		array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		)
	);

	foreach ( array( 'kind', 'best_for', 'file_url' ) as $key ) {
		register_post_meta(
			'spark_resource',
			'spark_' . $key,
			array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			)
		);
	}

	register_post_meta(
		'page',
		'_spark_solid_header',
		array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'sparkvn_register_meta' );
