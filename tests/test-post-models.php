<?php
/**
 * Tests for the Timber post models in classes/posts: the classmap registration
 * and the domain rules the models absorbed from the providers, the templates
 * and inc/post_types_functions.php.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Posts\Esdeveniment;
use Softcatala\Posts\Programa;
use Softcatala\Posts\Projecte;

/**
 * Class PostModelsTest
 */
class PostModelsTest extends SCTests {

	/**
	 * Creates a post of the given type and returns its Timber model.
	 *
	 * @param string $post_type post type to create.
	 * @param string $title     post title.
	 * @param array  $meta      postmeta to set.
	 * @return \Timber\Post
	 */
	private function make_post( $post_type, $title = 'Test', $meta = array() ) {
		$post_id = self::factory()->post->create(
			array(
				'post_type'  => $post_type,
				'post_title' => $title,
			)
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return Timber::get_post( $post_id );
	}

	/*
	 * Classmap
	 */

	function test_programa_uses_its_model() {
		$this->assertInstanceOf( Programa::class, $this->make_post( 'programa' ) );
	}

	function test_projecte_uses_its_model() {
		$this->assertInstanceOf( Projecte::class, $this->make_post( 'projecte' ) );
	}

	function test_esdeveniment_uses_its_model() {
		$this->assertInstanceOf( Esdeveniment::class, $this->make_post( 'esdeveniment' ) );
	}

	function test_other_post_types_are_left_alone() {
		$post = $this->make_post( 'post' );

		$this->assertNotInstanceOf( Programa::class, $post );
		$this->assertNotInstanceOf( Projecte::class, $post );
		$this->assertNotInstanceOf( Esdeveniment::class, $post );
	}

	/*
	 * Programa
	 */

	function test_programa_is_featured_reads_the_meta() {
		$featured = $this->make_post( 'programa', 'Destacat', array( 'programa_destacat' => '1' ) );

		$this->assertTrue( $featured->is_featured() );
		$this->assertFalse( $this->make_post( 'programa' )->is_featured() );
	}

	function test_programa_is_archived_reads_the_taxonomy() {
		$programa = $this->make_post( 'programa' );
		$this->assertFalse( $programa->is_archived() );

		wp_set_object_terms( $programa->ID, 'arxivat', 'classificacio' );

		$this->assertTrue( Timber::get_post( $programa->ID )->is_archived() );
	}

	function test_programa_without_logo_returns_an_empty_string() {
		$this->assertSame( '', $this->make_post( 'programa' )->logo() );
	}

	function test_download_urls_point_to_the_counter() {
		$baixades = Programa::build_download_urls(
			array(
				array(
					'download_os'      => 'windows',
					'arquitectura'     => 'x86_64',
					'download_version' => '44.0.1',
					'download_url'     => 'https://example.org/firefox.exe',
				),
			),
			'3522',
			42
		);

		$this->assertEquals(
			'https://baixades.softcatala.org/?id=3522&wid=42&versio=44.0.1&so=win64&url=' . urlencode( 'https://example.org/firefox.exe' ),
			$baixades[0]['download_url_ext']
		);
		$this->assertEquals( 'Windows', $baixades[0]['download_os_label'] );
		$this->assertEquals( 'fab fa-windows', $baixades[0]['so_icona'] );
	}

	function test_download_urls_default_to_version_1() {
		$baixades = Programa::build_download_urls(
			array(
				array(
					'download_os'  => 'linux',
					'download_url' => 'https://example.org/app.tar.gz',
				),
			),
			'1',
			2
		);

		$this->assertStringContainsString( '&versio=1.0&', $baixades[0]['download_url_ext'] );
	}

	function test_programa_without_downloads_returns_an_empty_array() {
		$this->assertSame( array(), $this->make_post( 'programa' )->downloads() );
	}

	function test_link_from_stats_resolves_the_wordpress_id() {
		$programa = $this->make_post( 'programa' );

		$link = Programa::link_from_stats( (object) array( 'wordpress_id' => $programa->ID ) );

		$this->assertEquals( get_post_permalink( $programa->ID ), $link );
	}

	function test_link_from_stats_resolves_the_rebost_id() {
		$programa = $this->make_post( 'programa', 'Rebost', array( 'idrebost' => '3522' ) );

		$link = Programa::link_from_stats( (object) array( 'idrebost' => '3522' ) );

		$this->assertEquals( get_post_permalink( $programa->ID ), $link );
	}

	function test_link_from_stats_returns_false_for_unknown_programs() {
		$this->assertFalse( Programa::link_from_stats( (object) array( 'wordpress_id' => 999999 ) ) );
		$this->assertFalse( Programa::link_from_stats( (object) array( 'idrebost' => 'no-existeix' ) ) );
	}

	/*
	 * Projecte
	 */

	function test_projecte_is_featured_reads_the_meta() {
		$featured = $this->make_post( 'projecte', 'Destacat', array( 'projecte_destacat' => '1' ) );

		$this->assertTrue( $featured->is_featured() );
		$this->assertFalse( $this->make_post( 'projecte' )->is_featured() );
	}

	function test_projecte_is_internal_reads_the_meta() {
		$internal = $this->make_post( 'projecte', 'Intern', array( 'projecte_intern' => '1' ) );

		$this->assertTrue( $internal->is_internal() );
		$this->assertTrue( Projecte::is_internal_post( $internal->ID ) );

		$public = $this->make_post( 'projecte' );

		$this->assertFalse( $public->is_internal() );
		$this->assertFalse( Projecte::is_internal_post( $public->ID ) );
	}

	/**
	 * Projects with no meta row at all are public, which is what the two-arm
	 * OR of the meta query is there for.
	 */
	function test_public_meta_query_keeps_projects_without_the_meta() {
		$public   = $this->make_post( 'projecte', 'Public' );
		$internal = $this->make_post( 'projecte', 'Intern', array( 'projecte_intern' => '1' ) );

		$found = get_posts(
			array(
				'post_type'      => 'projecte',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => Projecte::public_meta_query(),
			)
		);

		$this->assertContains( $public->ID, $found );
		$this->assertNotContains( $internal->ID, $found );
	}

	function test_projecte_without_responsables_returns_false() {
		$this->assertFalse( $this->make_post( 'projecte' )->responsables() );
	}

	/**
	 * The AJAX endpoint looks projects up by slug before asking for the
	 * responsables.
	 */
	function test_projecte_is_found_by_slug() {
		$projecte = $this->make_post( 'projecte', 'Traducció' );

		$found = Projecte::find_by_slug( $projecte->post_name );

		$this->assertInstanceOf( Projecte::class, $found );
		$this->assertEquals( $projecte->ID, $found->ID );
		$this->assertNull( Projecte::find_by_slug( 'projecte-que-no-existeix' ) );
	}

	/*
	 * Esdeveniment
	 */

	function test_esdeveniment_reads_timestamps() {
		$start = mktime( 0, 0, 0, 3, 15, 2025 );
		$end   = mktime( 0, 0, 0, 3, 17, 2025 );

		$event = $this->make_post(
			'esdeveniment',
			'Jornada',
			array(
				'data_inici' => $start,
				'data_fi'    => $end,
			)
		);

		$this->assertEquals( $start, $event->start_date() );
		$this->assertEquals( $end, $event->end_date() );
		$this->assertEquals( 2025, $event->year() );
	}

	/**
	 * The ACF date picker stores Ymd, the Toolset import stored timestamps.
	 */
	function test_esdeveniment_reads_acf_dates() {
		$event = $this->make_post( 'esdeveniment', 'Jornada', array( 'data_inici' => '20250315' ) );

		$this->assertEquals( strtotime( '2025-03-15' ), $event->start_date() );
		$this->assertEquals( 2025, $event->year() );
	}

	function test_esdeveniment_without_dates_has_no_dates() {
		$event = $this->make_post( 'esdeveniment' );

		$this->assertNull( $event->start_date() );
		$this->assertNull( $event->end_date() );
		$this->assertNull( $event->year() );
		$this->assertFalse( $event->is_past() );
		$this->assertFalse( $event->is_upcoming() );
	}

	function test_esdeveniment_is_upcoming_until_it_ends() {
		$event = $this->make_post(
			'esdeveniment',
			'Jornada',
			array(
				'data_inici' => strtotime( '-1 day' ),
				'data_fi'    => strtotime( '+2 days' ),
			)
		);

		$this->assertTrue( $event->is_upcoming() );
		$this->assertFalse( $event->is_past() );
	}

	function test_esdeveniment_is_past_once_it_ended() {
		$event = $this->make_post(
			'esdeveniment',
			'Jornada',
			array(
				'data_inici' => strtotime( '-3 days' ),
				'data_fi'    => strtotime( '-2 days' ),
			)
		);

		$this->assertTrue( $event->is_past() );
		$this->assertFalse( $event->is_upcoming() );
	}

	/**
	 * An event taking place today has not passed yet.
	 */
	function test_esdeveniment_today_is_upcoming() {
		$event = $this->make_post( 'esdeveniment', 'Jornada', array( 'data_inici' => strtotime( 'today midnight' ) ) );

		$this->assertTrue( $event->is_upcoming() );
		$this->assertFalse( $event->is_past() );
	}

	/*
	 * Shared sorting
	 */

	function test_programes_are_sorted_featured_first_then_by_title() {
		$posts = array(
			$this->make_post( 'programa', 'Zebra' ),
			$this->make_post( 'programa', 'Alfa' ),
			$this->make_post( 'programa', 'Omega', array( 'programa_destacat' => '1' ) ),
		);

		usort( $posts, array( Programa::class, 'compare_featured_then_title' ) );

		$this->assertEquals( array( 'Omega', 'Alfa', 'Zebra' ), wp_list_pluck( $posts, 'post_title' ) );
	}

	function test_projectes_share_the_same_sorting() {
		$posts = array(
			$this->make_post( 'projecte', 'Zebra' ),
			$this->make_post( 'projecte', 'Alfa' ),
			$this->make_post( 'projecte', 'Omega', array( 'projecte_destacat' => '1' ) ),
		);

		usort( $posts, array( Projecte::class, 'compare_featured_then_title' ) );

		$this->assertEquals( array( 'Omega', 'Alfa', 'Zebra' ), wp_list_pluck( $posts, 'post_title' ) );
	}

	/*
	 * The providers sort lazy Timber collections. An unrealized collection
	 * still holds raw WP_Post objects, and uasort() hands those straight to
	 * the comparator, which fatals calling is_featured() on them.
	 */

	function test_programes_provider_sorts_its_lazy_collection() {
		$this->make_post( 'programa', 'Zebra' );
		$this->make_post( 'programa', 'Omega', array( 'programa_destacat' => '1' ) );

		$programs = \Softcatala\Providers\Programes::get_sorted();

		$this->assertEquals(
			array( 'Omega', 'Zebra' ),
			wp_list_pluck( iterator_to_array( $programs ), 'post_title' )
		);
	}

	function test_projectes_provider_sorts_its_lazy_collection() {
		$this->make_post( 'projecte', 'Zebra' );
		$this->make_post( 'projecte', 'Omega', array( 'projecte_destacat' => '1' ) );

		$projects = \Softcatala\Providers\Projectes::get_sorted_projects();

		$this->assertEquals(
			array( 'Omega', 'Zebra' ),
			wp_list_pluck( iterator_to_array( $projects ), 'post_title' )
		);
	}
}
