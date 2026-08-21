<?php
/**
 * Deterministic read-only API fixtures for local browser tests.
 */
if ( ! defined( 'SC_LOCAL_FIXTURES' ) || ! SC_LOCAL_FIXTURES ) {
	return;
}

add_filter(
	'pre_http_request',
	function ( $preempt, $args, $url ) {
		if ( ! str_starts_with( $url, 'https://fixtures.softcatala.invalid/' ) ) {
			return $preempt;
		}

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$code = 200;
		$body = array();

		if ( str_contains( $path, '/sinonims/search/' ) ) {
			$word = rawurldecode( basename( $path ) );
			if ( 'acorar' !== mb_strtolower( $word, 'UTF-8' ) ) {
				$code = 404;
				$body = array( 'alternatives' => array() );
			} else {
				$body = array(
					'canonicalLemma' => 'acorar',
					'alternatives'    => array(),
					'results'         => array(
						array(
							'lemma'          => 'acorar',
							'feminineLemma'  => '',
							'synonymEntries' => array(
								array(
									'grammarCategory' => 'verb',
									'comment'         => '',
									'synonimWords'    => array(
										array( 'wordString' => 'afligir', 'feminineForm' => '', 'wordComment' => '', 'link' => true ),
										array( 'wordString' => 'apesarar', 'feminineForm' => '', 'wordComment' => '', 'link' => true ),
									),
									'antonymWords'     => array(),
								),
							),
							'antonymEntries' => array(),
						),
					),
				);
			}
		} elseif ( str_contains( $path, '/sinonims/index/' ) ) {
			$body = array( 'words' => array( 'acorar', 'AcOrAr', 'afligir', 'mot/amb/barra' ) );
		} elseif ( str_contains( $path, '/conjugador/search/' ) ) {
			$word = mb_strtolower( rawurldecode( basename( $path ) ), 'UTF-8' );
			if ( 'ser' !== $word ) {
				$code = 404;
				$body = array();
			} else {
				$body = array(
					array(
						'ser' => array(
							array(
								'title'       => 'ser',
								'definition'  => 'Existir o tenir una qualitat determinada.',
								'tense'       => 'Present',
								'mode'        => 'Indicatiu',
								'singular1'   => array( array( 'word' => 'soc', 'variant' => '', 'diacritic' => false ) ),
								'singular2'   => array( array( 'word' => 'ets', 'variant' => '', 'diacritic' => false ) ),
								'singular3'   => array( array( 'word' => 'és', 'variant' => '', 'diacritic' => false ) ),
								'plural1'     => array( array( 'word' => 'som', 'variant' => '', 'diacritic' => false ) ),
								'plural2'     => array( array( 'word' => 'sou', 'variant' => '', 'diacritic' => false ) ),
								'plural3'     => array( array( 'word' => 'són', 'variant' => '', 'diacritic' => false ) ),
							),
						),
					),
				);
			}
		} elseif ( str_contains( $path, '/conjugador/index/' ) ) {
			$body = array(
				array( 'verb_form' => 'ser', 'infinitive' => 'ser' ),
				array( 'verb_form' => 'ser', 'infinitive' => 'ser' ),
			);
		} elseif ( str_contains( $path, '/engcat/search/' ) ) {
			$word = mb_strtolower( rawurldecode( basename( $path ) ), 'UTF-8' );
			if ( 'house' !== $word && 'casa' !== $word ) {
				$code = 404;
				$body = array();
			} else {
				$is_english = 'house' === $word;
				$word_model = array(
					'text'             => $word,
					'grammarClass'     => 'n',
					'grammarAux'       => '',
					'feminine'         => '',
					'plural'           => '',
					'tags'             => '',
					'def'              => '',
					'remark'           => '',
					'alternativeForms' => array(),
				);
				$translated_word = $word_model;
				$translated_word['text'] = $is_english ? 'casa' : 'house';
				$lemma = array(
					'originalWord' => $word_model,
					'subLemmaList' => array(
						array(
							'originalWord'    => $word_model,
							'translationsSets' => array(
								array(
									'translatedWords' => array( $translated_word ),
									'examples'        => array(),
								),
							),
						),
					),
				);
				$body = array(
					'canonicalLemma' => $word,
					'results' => $is_english
						? array( array( 'lemmas' => array( $lemma ) ), array( 'lemmas' => array() ) )
						: array( array( 'lemmas' => array() ), array( 'lemmas' => array( $lemma ) ) ),
				);
			}
		} elseif ( str_contains( $path, '/engcat/index/' ) ) {
			$body = array( 'words' => array( 'house', 'House', 'input/output controller' ) );
		} elseif ( str_contains( $path, '/engcat/stats/' ) ) {
			$body = array( 'entries' => 2 );
		} elseif ( str_contains( $path, '/corpus/' ) ) {
			$body = array();
		} else {
			$code = 404;
		}

		return array(
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Not Found',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	},
	10,
	3
);
