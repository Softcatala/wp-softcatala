<?php
/**
 * Where a contact-form message goes and how it is labelled.
 *
 * sc_contact_form() used to take recipient, sender and subject from hidden
 * inputs, which made the endpoint a mail relay to any address. They are now
 * derived on the server from a recipient key and the page the form sits on.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class ContactFormTest
 */
class ContactFormTest extends SCTests {

	public function tear_down() {
		delete_option( 'email_recursos' );

		parent::tear_down();
	}

	/*
	 * sc_contact_recipient()
	 */

	function test_known_key_reads_its_option() {
		update_option( 'email_recursos', 'recursos@softcatala.org' );

		$this->assertEquals( 'recursos@softcatala.org', sc_contact_recipient( 'recursos' ) );
	}

	function test_known_key_with_an_empty_option_falls_back_to_web() {
		$this->assertEquals( SC_CONTACT_DEFAULT_RECIPIENT, sc_contact_recipient( 'recursos' ) );
	}

	function test_known_key_with_a_malformed_option_falls_back_to_web() {
		update_option( 'email_recursos', 'not an address' );

		$this->assertEquals( SC_CONTACT_DEFAULT_RECIPIENT, sc_contact_recipient( 'recursos' ) );
	}

	function test_web_and_unknown_keys_go_to_web() {
		$this->assertEquals( SC_CONTACT_DEFAULT_RECIPIENT, sc_contact_recipient( 'web' ) );
		$this->assertEquals( SC_CONTACT_DEFAULT_RECIPIENT, sc_contact_recipient( '' ) );
		$this->assertEquals( SC_CONTACT_DEFAULT_RECIPIENT, sc_contact_recipient( 'victim@example.com' ) );
	}

	/*
	 * sc_contact_page_title() and sc_contact_subject()
	 */

	function test_subject_carries_the_page_title() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Conjugador de verbs' ) );

		$this->assertEquals( 'Conjugador de verbs', sc_contact_page_title( $post_id ) );
		$this->assertEquals( '[Conjugador de verbs] Contacte des del formulari', sc_contact_subject( 'report', $post_id ) );
	}

	function test_subject_strips_markup_from_the_title() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Hola <b>món</b>' ) );

		$this->assertEquals( 'Hola món', sc_contact_page_title( $post_id ) );
	}

	function test_subject_without_a_page_names_the_site() {
		$this->assertEquals( 'Softcatalà', sc_contact_page_title( 0 ) );
		$this->assertEquals( 'Softcatalà', sc_contact_page_title( 999999 ) );
		$this->assertEquals( '[Softcatalà] Contacte des del formulari', sc_contact_subject( 'contacte', 0 ) );
	}

	function test_anonymous_form_gets_its_own_subject() {
		$this->assertEquals(
			'[Softcatalà] Contacte des del formulari anònim del Codi de Conducta',
			sc_contact_subject( 'anonim', 0 )
		);
	}
}
