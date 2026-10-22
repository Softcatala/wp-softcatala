<?php
/**
 * Feature flags shown as switches under "Funcionalitat opcional" in Softcatalà Settings.
 *
 * Each key is the wp_options name; flags are off until enabled in the dashboard.
 *
 * @package Softcatala
 */

return array(
	SC_Settings::SETTINGS_LOG_CORRECTOR_USER_EVENTS     => array(
		'title'       => 'Corrector',
		'description' => "Habilita/deshabilita el seguiment d'interaccions de l'usuari",
	),
	SC_Settings::SETTINGS_SEND_EMAILS_THESAURUS_ERRORS  => array(
		'title'       => 'Diccionari de sinònims',
		'description' => "Habilita/deshabilita l'enviament de correus electrònics quan falla el servei",
	),
	SC_Settings::SETTINGS_LOG_TRADUCTOR_SOURCE          => array(
		'title'       => 'Traductor',
		'description' => 'Habilita/deshabilita el registre de textos per a la millora',
	),
	SC_Settings::SETTINGS_CORRECTOR_SEND_SESSIONID      => array(
		'title'       => 'LanguageTool',
		'description' => "Habilita/deshabilita l'enviament del textSessionId per a experiments",
	),
	SC_Settings::SETTINGS_CLICKIO_CMP                   => array(
		'title'       => 'Gestor de consentiment (Clickio)',
		'description' => 'Habilita/deshabilita el bàner de consentiment de galetes de Clickio i el Consent Mode de Google',
	),
);
