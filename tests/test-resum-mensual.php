<?php
/**
 * Tests for the resum_mensual CPT: the properties SoftcatalaBot depends on
 * when publishing monthly summaries over the REST API.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Providers\ResumsMensuals;

/**
 * Class ResumMensualTest
 */
class ResumMensualTest extends SCTests {

	/**
	 * Ensure the resum_mensual post type is registered.
	 */
	function test_resum_mensual_post_type_exists() {
		$this->assertTrue( post_type_exists( 'resum_mensual' ) );
	}

	/**
	 * The bot POSTs to /wp-json/wp/v2/resums-mensuals.
	 */
	function test_resum_mensual_rest_base() {
		$obj = get_post_type_object( 'resum_mensual' );
		$this->assertTrue( $obj->show_in_rest );
		$this->assertEquals( 'resums-mensuals', $obj->rest_base );
	}

	/**
	 * Not public (out of the main feed), but the permalink must resolve.
	 */
	function test_resum_mensual_visibility_flags() {
		$obj = get_post_type_object( 'resum_mensual' );
		$this->assertFalse( $obj->public );
		$this->assertTrue( $obj->publicly_queryable );
		$this->assertTrue( $obj->exclude_from_search );
	}

	/**
	 * The bot sets title and content.
	 */
	function test_resum_mensual_supports_title_and_editor() {
		$this->assertTrue( post_type_supports( 'resum_mensual', 'title' ) );
		$this->assertTrue( post_type_supports( 'resum_mensual', 'editor' ) );
	}

	/**
	 * capability_type 'post' lets the app-password editor create summaries.
	 */
	function test_resum_mensual_capability_type() {
		$obj = get_post_type_object( 'resum_mensual' );
		$this->assertEquals( 'post', $obj->capability_type );
		$this->assertTrue( $obj->map_meta_cap );
	}

	/**
	 * Summaries never show up in site search results.
	 */
	function test_resum_mensual_excluded_from_search() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'resum_mensual',
				'post_title'  => 'Resum mensual 2026-08',
				'post_status' => 'publish',
			)
		);

		$found = get_posts(
			array(
				's'           => 'Resum mensual 2026-08',
				'post_type'   => 'any',
				'fields'      => 'ids',
				'post_status' => 'publish',
			)
		);

		$this->assertNotContains( $post_id, $found );
	}

	/**
	 * The bot writes the figures in the same POST as the content.
	 */
	function test_resum_mensual_meta_writable_over_rest() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/resums-mensuals' );
		$request->set_body_params(
			array(
				'title'   => 'Resum mensual — octubre de 2026',
				'content' => '<p class="resum-actualitzacio">Actualitzat</p>',
				'status'  => 'publish',
				'meta'    => array(
					'periode'        => '2026-10',
					'actualitzat'    => '2026-11-07',
					'total'          => 34,
					'respostes'      => 14,
					'demanen_ajuda'  => 3,
					'sense_novetats' => 6,
					'sense_resposta' => 20,
					'tardanes'       => 2,
				),
			)
		);
		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$id = $response->get_data()['id'];
		$this->assertEquals( '2026-10', get_post_meta( $id, 'periode', true ) );
		$this->assertEquals( 14, (int) get_post_meta( $id, 'respostes', true ) );
		$this->assertEquals( 34, $response->get_data()['meta']['total'] );
	}

	/**
	 * A malformed periode is rejected instead of stored.
	 */
	function test_resum_mensual_rejects_bad_periode() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/resums-mensuals' );
		$request->set_body_params(
			array(
				'title' => 'Resum',
				'meta'  => array( 'periode' => 'octubre 2026' ),
			)
		);

		$this->assertEquals( 400, rest_do_request( $request )->get_status() );
	}

	/**
	 * Figures, month and update date read back in the shape the templates use.
	 */
	function test_provider_reads_meta() {
		$id = $this->create_resum(
			'2026-11-10 09:00:00',
			array(
				'periode'       => '2026-10',
				'actualitzat'   => '2026-11-07',
				'total'         => 34,
				'respostes'     => 14,
				'demanen_ajuda' => 3,
			)
		);

		$stats = ResumsMensuals::get_stats( $id );
		$this->assertEquals( 14, $stats['respostes'] );
		$this->assertEquals( 41, $stats['percentatge'] );
		$this->assertNull( $stats['tardanes'] );

		$periode = ResumsMensuals::get_periode( $id );
		$this->assertEquals( 'oct', $periode['abbr'] );
		$this->assertEquals( 'octubre de 2026', $periode['label'] );

		$this->assertEquals( '7 de novembre de 2026', ResumsMensuals::get_actualitzat( $id ) );
	}

	/**
	 * Summaries without meta fall back to dates and the first paragraph.
	 */
	function test_provider_without_meta() {
		$id   = $this->create_resum( '2026-10-03 09:00:00', array(), '<p>Primer <strong>paràgraf</strong>.</p><h2>Altres</h2>' );
		$item = ResumsMensuals::get_item( get_post( $id ) );

		$this->assertNull( $item['stats'] );
		$this->assertNull( $item['periode'] );
		$this->assertNull( $item['actualitzat'] );
		$this->assertEquals( 2026, $item['year'] );
		$this->assertEquals( "3 d'octubre de 2026", $item['publicat'] );
		$this->assertEquals( 'Primer paràgraf.', $item['extracte'] );
	}

	/**
	 * Previous and next follow publication order.
	 */
	function test_provider_adjacent() {
		$first  = $this->create_resum( '2026-08-05 09:00:00' );
		$middle = $this->create_resum( '2026-09-05 09:00:00' );
		$last   = $this->create_resum( '2026-10-05 09:00:00' );

		$adjacent = ResumsMensuals::get_adjacent( $middle );
		$this->assertEquals( $first, $adjacent['previous']['ID'] );
		$this->assertEquals( $last, $adjacent['next']['ID'] );

		$this->assertNull( ResumsMensuals::get_adjacent( $first )['previous'] );
	}

	/**
	 * The sidebar table of contents comes from h2 elements with an id.
	 */
	function test_provider_toc() {
		$toc = ResumsMensuals::get_toc( '<h2 id="ajuda">Projectes que necessiten ajuda</h2><h2>Sense id</h2><h2 id="sense-resposta">Sense resposta (20)</h2>' );

		$this->assertEquals(
			array(
				array( 'id' => 'ajuda', 'title' => 'Projectes que necessiten ajuda' ),
				array( 'id' => 'sense-resposta', 'title' => 'Sense resposta' ),
			),
			$toc
		);
	}

	private function create_resum( $date, $meta = array(), $content = '' ) {
		return $this->factory->post->create(
			array(
				'post_type'    => 'resum_mensual',
				'post_status'  => 'publish',
				'post_date'    => $date,
				'post_content' => $content,
				'meta_input'   => $meta,
			)
		);
	}
}
