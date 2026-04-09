<?php

/**
 * Email helper functions.
 *
 * Low-level wrappers used to send contact-form emails and service-error
 * alerts throughout the theme.
 */

/**
 * Sends an email where the From address equals the To address.
 *
 * @param string $to_email   Recipient (and From) address.
 * @param string $nom_from   Sender display name.
 * @param string $assumpte   Email subject.
 * @param array  $fields     Key-value pairs to include in the email body.
 * @return string JSON-encoded result message.
 */
function sendEmailForm( $to_email, $nom_from, $assumpte, $fields ) {
	return sendEmailWithFromAndTo( $to_email, $to_email, $nom_from, $assumpte, $fields );
}

/**
 * Sends an email with distinct From and To addresses.
 *
 * @param string $to_email   Recipient address.
 * @param string $from_email From address.
 * @param string $nom_from   Sender display name.
 * @param string $assumpte   Email subject.
 * @param array  $fields     Key-value pairs to include in the email body.
 * @return string JSON-encoded result message.
 */
function sendEmailWithFromAndTo( $to_email, $from_email, $nom_from, $assumpte, $fields ) {
	$message_body = '';
	foreach ( $fields as $key => $field ) {
		$message_body .= $key . ': ' . $field . "\r\n\r";
	}

	$headers = 'From: ' . $nom_from . ' <' . $from_email . ">\r\n" .
	           'Reply-To: web@softcatala.org' . "\r\n" .
	           'X-Mailer: PHP/' . phpversion();

	$send_mail = wp_mail( $to_email, $assumpte, $message_body, $headers );

	if ( ! $send_mail ) {
		$output = json_encode( array( 'type' => 'error', 'text' => 'S\'ha produït un error en enviar el missatge.' ) );
	} else {
		$output = json_encode( array( 'type' => 'message', 'text' => 'S\'ha enviat la informació.' ) );
	}

	return $output;
}

/**
 * Throws a 500 error and optionally sends an alert email to the SC team.
 *
 * @param string $service   Name of the failing service (used as email subject).
 * @param string $message   Optional detail message.
 * @param bool   $sinonims  If true, respect the "send thesaurus error emails" setting.
 */
function throw_service_error( $service, $message = '', $sinonims = false ) {
	global $sc_site;

	throw_error( '500', 'Error connecting to API server' );

	if ( $sinonims && $sc_site->get_setting_value( SC_Settings::SETTINGS_SEND_EMAILS_THESAURUS_ERRORS ) ) {
		return;
	}

	$fields['Hora'] = current_time( 'mysql' );
	if ( $message ) {
		$fields['Missatge'] = $message;
	}

	sendEmailForm( 'web@softcatala.org', $service, 'El servei «' . $service . '» no està funcionant correctament', $fields );
}
