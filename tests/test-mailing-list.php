<?php
/**
 * The mailing list a subscription request resolves to.
 *
 * sc_subscribe_list() appends the Mailman admin password to the list URL it
 * ends up with, so the URL must come from the server: a fixed key for the
 * news list, or the project's own field for a project. These tests pin that
 * no shape of request input turns into a URL of the caller's choosing.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Posts\Projecte;

/**
 * Class MailingListTest
 */
class MailingListTest extends SCTests {

	const NOVETATS = 'https://llistes.softcatala.org/mailman/listinfo/novetats';

	/**
	 * Creates a projecte with the given slug and mailing-list field.
	 *
	 * @param string      $slug   post slug.
	 * @param string|null $llista value of llista_de_correu, or null to leave unset.
	 * @return int post ID
	 */
	private function make_projecte( $slug, $llista = null ) {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'projecte',
				'post_title'  => $slug,
				'post_name'   => $slug,
				'post_status' => 'publish',
			)
		);

		if ( null !== $llista ) {
			update_post_meta( $post_id, 'llista_de_correu', $llista );
		}

		return $post_id;
	}

	/*
	 * Projecte::mailing_list()
	 */

	function test_project_mailing_list_reads_the_field() {
		$post_id = $this->make_projecte( 'traduccio', 'https://llistes.softcatala.org/mailman/listinfo/traduccio' );

		$this->assertEquals(
			'https://llistes.softcatala.org/mailman/listinfo/traduccio',
			Timber::get_post( $post_id )->mailing_list()
		);
	}

	function test_project_without_a_list_has_no_mailing_list() {
		$post_id = $this->make_projecte( 'sense-llista' );

		$this->assertNull( Timber::get_post( $post_id )->mailing_list() );
	}

	/** An editor mistake in the field must not become a request to that host. */
	function test_project_list_on_another_host_is_ignored() {
		$post_id = $this->make_projecte( 'aliena', 'https://attacker.example/mailman/listinfo/traduccio' );

		$this->assertNull( Timber::get_post( $post_id )->mailing_list() );
	}

	function test_mailing_list_url_shape() {
		$this->assertTrue( Projecte::is_mailing_list_url( self::NOVETATS ) );
		$this->assertTrue( Projecte::is_mailing_list_url( 'https://llistes.softcatala.org/mailman/listinfo/gnome-dl' ) );

		$this->assertFalse( Projecte::is_mailing_list_url( 'http://llistes.softcatala.org/mailman/listinfo/novetats' ) );
		$this->assertFalse( Projecte::is_mailing_list_url( 'https://llistes.softcatala.org.evil.example/mailman/listinfo/novetats' ) );
		$this->assertFalse( Projecte::is_mailing_list_url( 'https://llistes.softcatala.org/mailman/listinfo/novetats/../../admin' ) );
		$this->assertFalse( Projecte::is_mailing_list_url( 'https://llistes.softcatala.org/mailman/listinfo/novetats?x=1' ) );
		$this->assertFalse( Projecte::is_mailing_list_url( 'https://llistes.softcatala.org/mailman/listinfo/' ) );
		$this->assertFalse( Projecte::is_mailing_list_url( '' ) );
		$this->assertFalse( Projecte::is_mailing_list_url( null ) );
	}

	/*
	 * sc_resolve_mailing_list()
	 */

	function test_news_form_resolves_to_the_novetats_list() {
		$this->assertEquals( self::NOVETATS, sc_resolve_mailing_list( 'novetats', '' ) );
	}

	function test_unknown_list_key_resolves_to_nothing() {
		$this->assertNull( sc_resolve_mailing_list( 'https://attacker.example/listinfo', '' ) );
		$this->assertNull( sc_resolve_mailing_list( 'attacker', '' ) );
	}

	/** A key wins over a slug, so a project slug cannot be smuggled in beside a bogus key. */
	function test_list_key_takes_precedence_over_the_project() {
		$this->make_projecte( 'traduccio', 'https://llistes.softcatala.org/mailman/listinfo/traduccio' );

		$this->assertNull( sc_resolve_mailing_list( 'attacker', 'traduccio' ) );
	}

	function test_project_form_resolves_to_the_project_list() {
		$this->make_projecte( 'traduccio', 'https://llistes.softcatala.org/mailman/listinfo/traduccio' );

		$this->assertEquals(
			'https://llistes.softcatala.org/mailman/listinfo/traduccio',
			sc_resolve_mailing_list( '', 'traduccio' )
		);
	}

	function test_project_without_a_list_resolves_to_nothing() {
		$this->make_projecte( 'sense-llista' );

		$this->assertNull( sc_resolve_mailing_list( '', 'sense-llista' ) );
	}

	function test_unknown_project_resolves_to_nothing() {
		$this->assertNull( sc_resolve_mailing_list( '', 'no-existeix' ) );
		$this->assertNull( sc_resolve_mailing_list( '', '' ) );
	}
}
