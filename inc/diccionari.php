<?php

/**
 * Twig filter functions for the Diccionari multilingüe and Diccionari Eng-Cat sections.
 *
 * Registered as Twig filters in StarterSite::add_to_twig().
 */

/**
 * Twig function specific for Diccionari multilingüe
 *
 * @param string $def
 *
 * @return string
 */
function print_definition( $def ) {
	$def = trim( $def );
	$pos = strpos( $def, '#' );

	if ( $pos === false ) {
		$result = ' - ' . $def;
	} else {
		$def      = str_replace( '#', '', $def );
		$entries  = explode( "\n", $def );
		$filtered = array_filter( array_map( 'trim_entries', $entries ) );
		$result   = ' - ' . implode( '<br />- ', $filtered ) . '<br />';
	}

	return $result;
}

function trim_entries( $entry ) {
	$trimmed = trim( $entry );

	return empty( $trimmed ) ? null : $trimmed;
}

/**
 * Twig functions specific for Diccionari Eng-Cat
 */

function fullGrammarTag( $word ) {
	$grammarTag = $word->grammarClass;

	if ( ! empty( $word->feminine ) && $grammarTag == 'm' ) {
		$grammarTag = 'mf';
	}

	if ( ! empty( $word->grammarAux ) ) {
		$grammarTag .= '&nbsp;' . $word->grammarAux;
	}

	return $grammarTag;
}

function prepareLemmaHeading( $word ) {
	$output = '';

	$output .= '<h2 class="originalword">';
	$output .= $word->text;

	if ( ! empty( $word->feminine ) ) {
		$output .= ' <span class="engcat-gray">' . $word->feminine . '</span> ';
	}

	$output .= '<span class="engcat-small-variants">';
	$fullGTag = fullGrammarTag( $word );
	$output .= '&nbsp;<em>' . $fullGTag . '</em>&nbsp;';

	if ( ! empty( $word->tags ) ) {
		$output .= '[' . $word->tags . '] ';
	}

	if ( ! empty( $word->def ) ) {
		$output .= '[' . $word->def . '] ';
	}

	if ( ! empty( $word->remark ) ) {
		$output .= ' [&rArr; ' . $word->remark . '] ';
	}
	$output .= '</span>';

	// afegim formes alternatives, incloent-hi el plural, en la línia següent
	if ( ! empty( $word->alternativeForms ) || ! empty( $word->plural ) ) {
		$separator = '';
		$output   .= '<br/><span class="engcat-small-variants">';
		if ( ! empty( $word->plural ) ) {
			$output   .= 'pl. ' . $word->plural;
			$separator = '; ';
		}
		if ( ! empty( $word->alternativeForms ) ) {
			foreach ( $word->alternativeForms as $alternativeForm ) {
				$output .= $separator . $alternativeForm->text;
				if ( ! empty( $alternativeForm->tags ) ) {
					$output .= ' [' . $alternativeForm->tags . ']';
				}
				$separator = '; ';
			}
		}
		$output .= '</span>';
	}
	$output .= '</h2>';

	return trim( $output );
}

function prepareSubLemma( $word ) {
	$output = '';

	if ( ! empty( $word->before ) || ! empty( $word->after ) ) {
		$output .= '<b>';

		if ( ! empty( $word->before ) ) {
			$output .= '(' . $word->before . ') ';
		}

		$output .= $word->text;

		if ( ! empty( $word->after ) ) {
			$output .= ' (' . $word->after . ')';
		}

		$output .= '</b>&nbsp;';
	}

	if ( ! empty( $word->area ) ) {
		$output .= '<span class="engcat-smallcaps">' . $word->area . '</span>&nbsp;';
	}

	if ( ! empty( $word->remark ) ) {
		$output .= ' [&rArr; ' . $word->remark . '] ';
	}

	return trim( $output );
}

function prepareWord( $word, $prevFullGTag ) {
	$output = '';

	if ( ! empty( $word->area ) ) {
		$output .= '<span class="engcat-smallcaps">' . $word->area . '</span>&nbsp;';
	}

	if ( ! empty( $word->tags ) ) {
		$output .= '[' . $word->tags . '] ';
	}

	if ( ! empty( $word->def ) ) {
		$output .= '[' . $word->def . '] ';
	}

	if ( ! empty( $word->before ) ) {
		$output .= '(' . $word->before . ') ';
	}

	$output .= $word->text;

	if ( ! empty( $word->after ) ) {
		$output .= ' (' . $word->after . ')';
	}

	if ( ! empty( $word->feminine ) ) {
		$output .= '&nbsp;<span class="engcat-gray">' . $word->feminine . '</span>';
	}

	if ( ! empty( $word->plural ) ) {
		$output .= ' [pl. ' . $word->plural . '] ';
	}

	$fullGTag = fullGrammarTag( $word );
	if ( $fullGTag != $prevFullGTag && $fullGTag != 'n' ) {
		$output .= '&nbsp;<span class="engcat-italics">' . $fullGTag . '</span>';
	}

	if ( ! empty( $word->remark ) ) {
		$output .= ' [&rArr; ' . $word->remark . '] ';
	}

	return trim( $output );
}

function presentFeminine( $word ) {
	if ( ! empty( $word->feminineForm ) ) {
		return '&nbsp;<span class="engcat-gray">' . $word->feminineForm . '</span>';
	} else {
		return '';
	}
}
