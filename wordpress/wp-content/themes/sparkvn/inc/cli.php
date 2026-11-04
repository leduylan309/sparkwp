<?php
/**
 * WP-CLI: wp sparkvn seed
 *
 * @package Spark_VN
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Seed Spark pages, CPTs, menu, and reading settings from inc/seed-content.json.
 *
 * ## OPTIONS
 *
 * [--force]
 * : Recreate content even if slugs already exist.
 *
 * @when after_wp_load
 */
function sparkvn_cli_seed( $args, $assoc_args ) {
	$force   = ! empty( $assoc_args['force'] );
	$path    = get_template_directory() . '/inc/seed-content.json';
	if ( ! file_exists( $path ) ) {
		WP_CLI::error( 'Missing inc/seed-content.json — run tools/build-spark-content.py' );
	}
	$data = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $data ) ) {
		WP_CLI::error( 'Invalid seed JSON' );
	}

	kses_remove_filters();
	update_option( 'permalink_structure', '/%postname%/' );
	flush_rewrite_rules( false );

	foreach ( $data['categories'] as $slug ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( ucfirst( $slug ), 'category', array( 'slug' => $slug ) );
		}
	}

	$id_by_slug = array();

	foreach ( $data['pages'] as $page ) {
		$existing = get_page_by_path( $page['slug'] );
		if ( $existing && ! $force ) {
			$id_by_slug[ $page['slug'] ] = $existing->ID;
			WP_CLI::log( 'Skip page ' . $page['slug'] );
			continue;
		}
		$postarr = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $page['title'],
			'post_name'    => $page['slug'],
			'post_content' => $page['content'],
			'post_excerpt' => $page['excerpt'] ?? '',
			'post_parent'  => 0,
		);
		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$id            = wp_update_post( $postarr, true );
		} else {
			$id = wp_insert_post( $postarr, true );
		}
		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( $id->get_error_message() );
			continue;
		}
		$id_by_slug[ $page['slug'] ] = $id;
		if ( ! empty( $page['parent'] ) ) {
			update_post_meta( $id, '_spark_parent_slug', $page['parent'] );
		} else {
			delete_post_meta( $id, '_spark_parent_slug' );
		}
		if ( ! empty( $page['solid'] ) ) {
			update_post_meta( $id, '_spark_solid_header', '1' );
		}
		if ( ! empty( $page['front'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $id );
		}
		sparkvn_cli_sideload_post( $id, $page['content'] ?? '' );
		WP_CLI::log( 'Page ' . $page['slug'] . ' #' . $id );
	}

	$blog = get_page_by_path( 'blog' );
	if ( ! $blog ) {
		$blog_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Blog',
				'post_name'   => 'blog',
				'post_content'=> '',
			)
		);
	} else {
		$blog_id = $blog->ID;
	}
	update_option( 'page_for_posts', $blog_id );

	$order = 1;
	foreach ( $data['programs'] as $program ) {
		$existing = get_page_by_path( $program['slug'], OBJECT, 'spark_program' );
		$postarr  = array(
			'post_type'    => 'spark_program',
			'post_status'  => 'publish',
			'post_title'   => $program['title'],
			'post_name'    => $program['slug'],
			'post_content' => $program['content'],
			'post_excerpt' => $program['excerpt'] ?? '',
			'menu_order'   => $order,
		);
		if ( $existing && ! $force ) {
			WP_CLI::log( 'Skip program ' . $program['slug'] );
			++$order;
			continue;
		}
		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$id            = wp_update_post( $postarr, true );
		} else {
			$id = wp_insert_post( $postarr, true );
		}
		if ( ! is_wp_error( $id ) ) {
			sparkvn_cli_sideload_post( $id, $program['content'] ?? '' );
			if ( ! empty( $program['image'] ) ) {
				$media = sparkvn_sideload_theme_file( $program['image'] );
				if ( $media['id'] ) {
					set_post_thumbnail( $id, $media['id'] );
				}
				update_post_meta( $id, 'spark_image', $program['image'] );
			}
			WP_CLI::log( 'Program ' . $program['slug'] . ' #' . $id );
		}
		++$order;
	}

	// Speak Clearly card → Contact.
	if ( ! get_page_by_path( 'speak-clearly', OBJECT, 'spark_program' ) ) {
		$sc = wp_insert_post(
			array(
				'post_type'    => 'spark_program',
				'post_status'  => 'publish',
				'post_title'   => 'Speak Clearly Program',
				'post_name'    => 'speak-clearly',
				'post_excerpt' => 'Pronunciation, articulation, fluency, and oral communication for ages 5–13.',
				'menu_order'   => $order,
			)
		);
		if ( ! is_wp_error( $sc ) ) {
			update_post_meta( $sc, 'spark_cta_url', home_url( '/contact/' ) );
			update_post_meta( $sc, 'spark_menu_order_label', 'Building Clear, Confident Communicators' );
			update_post_meta( $sc, 'spark_image', 'assets/img/cdn/IMG_2105-1920w.jpeg' );
		}
	}

	foreach ( $data['prizes'] as $prize ) {
		$term = term_exists( $prize['group'], 'prize_group' );
		if ( ! $term ) {
			$term = wp_insert_term(
				$prize['group_name'],
				'prize_group',
				array(
					'slug'        => $prize['group'],
					'description' => $prize['group_desc'] ?? '',
				)
			);
		}
		$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		$found   = get_page_by_title( $prize['title'], OBJECT, 'spark_prize' );
		if ( $found && ! $force ) {
			continue;
		}
		$postarr = array(
			'post_type'   => 'spark_prize',
			'post_status' => 'publish',
			'post_title'  => $prize['title'],
		);
		if ( $found ) {
			$postarr['ID'] = $found->ID;
			$pid           = wp_update_post( $postarr );
		} else {
			$pid = wp_insert_post( $postarr );
		}
		if ( is_wp_error( $pid ) || ! $pid ) {
			continue;
		}
		wp_set_object_terms( $pid, array( $term_id ), 'prize_group' );
		update_post_meta( $pid, 'spark_points', $prize['points'] );
		update_post_meta( $pid, 'spark_note', $prize['note'] );
		$img = $prize['img'];
		$img = preg_replace( '#^assets/#', 'assets/', $img );
		update_post_meta( $pid, 'spark_image', $img );
		$media = sparkvn_sideload_theme_file( $img );
		if ( $media['id'] ) {
			set_post_thumbnail( $pid, $media['id'] );
		}
	}
	WP_CLI::log( 'Prizes: ' . count( $data['prizes'] ) );

	foreach ( $data['resources'] as $res ) {
		$found = get_page_by_title( $res['title'], OBJECT, 'spark_resource' );
		if ( $found && ! $force ) {
			continue;
		}
		$postarr = array(
			'post_type'    => 'spark_resource',
			'post_status'  => 'publish',
			'post_title'   => $res['title'],
			'post_excerpt' => $res['excerpt'],
		);
		if ( $found ) {
			$postarr['ID'] = $found->ID;
			$rid           = wp_update_post( $postarr );
		} else {
			$rid = wp_insert_post( $postarr );
		}
		if ( is_wp_error( $rid ) || ! $rid ) {
			continue;
		}
		update_post_meta( $rid, 'spark_kind', $res['kind'] );
		update_post_meta( $rid, 'spark_best_for', $res['best_for'] );
		$file = $res['file'];
		if ( str_starts_with( $file, 'assets/' ) ) {
			$media = sparkvn_sideload_theme_file( $file );
			$file  = $media['url'] ?: sparkvn_asset_uri( $file );
		}
		update_post_meta( $rid, 'spark_file_url', $file );
	}

	foreach ( $data['posts'] as $post ) {
		$existing = get_page_by_path( $post['slug'], OBJECT, 'post' );
		$postarr  = array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $post['title'],
			'post_name'    => $post['slug'],
			'post_content' => $post['content'],
			'post_excerpt' => $post['excerpt'],
			'post_date'    => ( $post['date'] ?? '2026-10-02' ) . ' 09:00:00',
		);
		if ( $existing && ! $force ) {
			continue;
		}
		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$pid           = wp_update_post( $postarr );
		} else {
			$pid = wp_insert_post( $postarr );
		}
		if ( ! is_wp_error( $pid ) ) {
			sparkvn_cli_sideload_post( $pid, $post['content'] ?? '' );
			$cats = $post['categories'] ?? array( $post['category'] ?? 'parents' );
			wp_set_object_terms( $pid, $cats, 'category' );
			if ( ! empty( $post['image'] ) ) {
				update_post_meta( $pid, 'spark_image', $post['image'] );
				$media = sparkvn_sideload_theme_file( $post['image'] );
				if ( $media['id'] ) {
					set_post_thumbnail( $pid, $media['id'] );
				}
			}
		}
	}

	sparkvn_cli_seed_menu( $id_by_slug );
	flush_rewrite_rules();
	WP_CLI::success( 'Spark content seeded.' );
}

/**
 * Write sideloaded Gutenberg markup back onto a post.
 */
function sparkvn_cli_sideload_post( int $post_id, string $content ): void {
	if ( '' === $content ) {
		return;
	}
	$updated = sparkvn_sideload_content( $content );
	if ( $updated !== $content ) {
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $updated,
			)
		);
	}
}

/**
 * Build the 3-level primary menu from shell.js IA.
 *
 * @param array $id_by_slug Page IDs.
 */
function sparkvn_cli_seed_menu( array $id_by_slug ): void {
	$menu_name = 'Primary';
	$menu      = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	} else {
		$menu_id = (int) $menu->term_id;
		$items   = wp_get_nav_menu_items( $menu_id );
		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item->ID, true );
			}
		}
	}

	$loc = get_theme_mod( 'nav_menu_locations', array() );
	$loc['menu-1'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $loc );

	$add = static function ( $title, $url, $parent = 0 ) use ( $menu_id ) {
		return wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $url,
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
			)
		);
	};

	$u = static function ( $path ) {
		return home_url( $path );
	};

	$add( 'Home', $u( '/' ) );
	$about = $add( 'About', $u( '/about/' ) );
	$add( 'Our Story', $u( '/our-story/' ), $about );
	$add( 'Joe Evans', $u( '/joe-evans/' ), $about );
	$add( 'English Support', $u( '/english-support/' ), $about );
	$add( 'The Spark Method', $u( '/the-spark-method/' ), $about );
	$add( 'Class Size', $u( '/class-size/' ), $about );
	$add( 'Community', $u( '/community/' ), $about );
	$add( 'Assessment', $u( '/assessment/' ) );
	$prog = $add( 'Programs', $u( '/programs/' ) );
	$add( 'Phonics', $u( '/programs/phonics/' ), $prog );
	$add( 'ESL', $u( '/programs/esl/' ), $prog );
	$add( 'IB English', $u( '/programs/ib-english/' ), $prog );
	$add( 'Tides', $u( '/programs/tides/' ), $prog );
	$add( 'IELTS', $u( '/programs/ielts/' ), $prog );
	$add( 'Policy', $u( '/policy/' ), $prog );
	$ex = $add( 'Exclusive', $u( '/exclusive/' ) );
	$tc = $add( 'Teachers Corner', $u( '/teachers-corner/' ), $ex );
	$add( 'Ask a Teacher', $u( '/ask-a-teacher/' ), $tc );
	$add( 'Teacher Blogs', $u( '/teacher-blogs/' ), $tc );
	$pc = $add( 'Parent Center', $u( '/parent-center/' ), $ex );
	$add( 'Why Academic English Matters', $u( '/why-academic-english-matters/' ), $pc );
	$add( 'Academic Roadmap', $u( '/academic-roadmap/' ), $pc );
	$add( 'Results Dashboard', $u( '/results-dashboard/' ), $pc );
	$add( 'Parent Success Roadmap', $u( '/parent-success-roadmap/' ), $pc );
	$rl = $add( 'Resource Library', $u( '/resource-library/' ), $ex );
	$add( 'International School Support Guide', $u( '/international-school-support-guide/' ), $rl );
	$add( 'IB School Guide', $u( '/ib-school-guide/' ), $rl );
	$add( "Parent's Guide", $u( '/ask-a-teacher/' ), $rl );
	$add( 'Reading Age Growth Center', $u( '/reading-age-growth-center/' ), $rl );
	// Student Portal + Spark Store intentionally omitted from nav until ready.
	$add( 'Contact', $u( '/contact/' ) );
	$add( 'Blog', $u( '/blog/' ) );
}

WP_CLI::add_command( 'sparkvn seed', 'sparkvn_cli_seed' );
