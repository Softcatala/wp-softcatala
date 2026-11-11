<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Providers;

/**
 * Reads the structured data SoftcatalaBot stores on each resum_mensual and
 * turns it into what the archive and single templates show.
 *
 * Every getter tolerates missing meta: summaries published before the bot sent
 * these fields, or edited by hand, fall back to plain dates and content.
 */
class ResumsMensuals {

	const POST_TYPE = 'resum_mensual';

	/**
	 * Integer meta keys, with the description registered for each.
	 */
	const COUNT_FIELDS = array(
		'total'          => 'Nombre de projectes als quals s\'ha preguntat.',
		'respostes'      => 'Nombre de projectes que han respost.',
		'demanen_ajuda'  => 'Nombre de projectes que demanen ajuda.',
		'sense_novetats' => 'Nombre de projectes que han respost sense novetats.',
		'sense_resposta' => 'Nombre de projectes que no han respost.',
		'tardanes'       => 'Nombre de respostes arribades després de publicar el resum.',
	);

	const MONTHS = array( 'gener', 'febrer', 'març', 'abril', 'maig', 'juny', 'juliol', 'agost', 'setembre', 'octubre', 'novembre', 'desembre' );

	const MONTH_ABBREVIATIONS = array( 'gen', 'febr', 'març', 'abr', 'maig', 'juny', 'jul', 'ag', 'set', 'oct', 'nov', 'des' );

	/**
	 * Figures for a summary, or null when the bot did not send the two the
	 * rest hang off (total and respostes).
	 *
	 * @param int $post_id Summary ID.
	 * @return array|null
	 */
	public static function get_stats( $post_id ) {
		$stats = array();
		foreach ( array_keys( self::COUNT_FIELDS ) as $key ) {
			$value         = get_post_meta( $post_id, $key, true );
			$stats[ $key ] = ( '' === $value || null === $value ) ? null : (int) $value;
		}

		if ( null === $stats['total'] || null === $stats['respostes'] ) {
			return null;
		}

		$stats['percentatge'] = $stats['total'] > 0
			? (int) round( 100 * min( $stats['respostes'], $stats['total'] ) / $stats['total'] )
			: 0;

		return $stats;
	}

	/**
	 * The month a summary covers.
	 *
	 * @param int $post_id Summary ID.
	 * @return array|null year, month (1-12), abbr ('oct') and label ('octubre de 2026').
	 */
	public static function get_periode( $post_id ) {
		$periode = (string) get_post_meta( $post_id, 'periode', true );
		if ( ! preg_match( '/^(\d{4})-(0[1-9]|1[0-2])$/', $periode, $matches ) ) {
			return null;
		}

		$year  = (int) $matches[1];
		$month = (int) $matches[2];

		return array(
			'year'  => $year,
			'month' => $month,
			'abbr'  => self::MONTH_ABBREVIATIONS[ $month - 1 ],
			'label' => self::MONTHS[ $month - 1 ] . ' de ' . $year,
		);
	}

	/**
	 * Date of the last late-reply update, formatted in Catalan.
	 *
	 * @param int $post_id Summary ID.
	 * @return string|null '7 de novembre de 2026'.
	 */
	public static function get_actualitzat( $post_id ) {
		$actualitzat = (string) get_post_meta( $post_id, 'actualitzat', true );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $actualitzat, $matches )
			|| ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return null;
		}

		return self::format_date( (int) $matches[3], (int) $matches[2], (int) $matches[1] );
	}

	/**
	 * '10 de novembre de 2026', '3 d'octubre de 2026'.
	 *
	 * @param int $day   Day of the month.
	 * @param int $month 1-12.
	 * @param int $year  Year.
	 * @return string
	 */
	public static function format_date( $day, $month, $year ) {
		$name = self::MONTHS[ $month - 1 ];
		$of   = preg_match( '/^[aeiou]/', $name ) ? "d'" : 'de ';

		return $day . ' ' . $of . $name . ' de ' . $year;
	}

	/**
	 * Everything a template needs to list one summary.
	 *
	 * @param \WP_Post|\Timber\Post $post Summary.
	 * @return array
	 */
	public static function get_item( $post ) {
		$timestamp = get_post_timestamp( $post->ID );
		$periode   = self::get_periode( $post->ID );
		$day       = (int) wp_date( 'j', $timestamp );
		$month     = (int) wp_date( 'n', $timestamp );
		$year      = (int) wp_date( 'Y', $timestamp );

		return array(
			'ID'          => $post->ID,
			'title'       => get_the_title( $post->ID ),
			'link'        => get_permalink( $post->ID ),
			'datetime'    => wp_date( 'Y-m-d', $timestamp ),
			'publicat'    => self::format_date( $day, $month, $year ),
			'day'         => $day,
			'month_abbr'  => self::MONTH_ABBREVIATIONS[ $month - 1 ],
			'year'        => $periode ? $periode['year'] : $year,
			'periode'     => $periode,
			'stats'       => self::get_stats( $post->ID ),
			'actualitzat' => self::get_actualitzat( $post->ID ),
			'extracte'    => self::get_first_paragraph( $post->post_content ),
		);
	}

	/**
	 * The text of the first paragraph, used as the teaser when there are no
	 * figures to show instead.
	 *
	 * @param string $content Raw post content.
	 * @return string
	 */
	public static function get_first_paragraph( $content ) {
		if ( preg_match( '/<p\b[^>]*>(.*?)<\/p>/is', $content, $matches ) ) {
			$text = $matches[1];
		} else {
			$parts = preg_split( '/\R\s*\R/', trim( $content ) );
			$text  = $parts[0] ?? '';
		}

		return trim( wp_strip_all_tags( $text ) );
	}

	/**
	 * The summaries published right before and after this one.
	 *
	 * @param int $post_id Summary ID.
	 * @return array previous and next, each an item array or null.
	 */
	public static function get_adjacent( $post_id ) {
		$date = get_post_field( 'post_date', $post_id );

		return array(
			'previous' => self::get_neighbour( $post_id, $date, 'before', 'DESC' ),
			'next'     => self::get_neighbour( $post_id, $date, 'after', 'ASC' ),
		);
	}

	/**
	 * @param int    $post_id   Summary ID.
	 * @param string $date      Its post_date.
	 * @param string $direction 'before' or 'after'.
	 * @param string $order     Sort order that puts the closest one first.
	 * @return array|null
	 */
	private static function get_neighbour( $post_id, $date, $direction, $order ) {
		$posts = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'orderby'          => 'date',
				'order'            => $order,
				'post__not_in'     => array( $post_id ),
				'date_query'       => array(
					array(
						'column'    => 'post_date',
						$direction  => $date,
						'inclusive' => false,
					),
				),
				'suppress_filters' => false,
			)
		);

		return $posts ? self::get_item( $posts[0] ) : null;
	}

	/**
	 * Latest summaries, newest first.
	 *
	 * @param int $limit How many.
	 * @return array Item arrays.
	 */
	public static function get_recent( $limit = 6 ) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return array_map( array( __CLASS__, 'get_item' ), $posts );
	}

	/**
	 * Table of contents from the summary's second-level headings that carry an
	 * id.
	 *
	 * @param string $content Raw post content.
	 * @return array Each with id and title.
	 */
	public static function get_toc( $content ) {
		if ( ! preg_match_all( '/<h2\b[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h2>/is', $content, $matches, PREG_SET_ORDER ) ) {
			return array();
		}

		$toc = array();
		foreach ( $matches as $match ) {
			// Drop the count in "Sense resposta (20)": the sidebar is narrow.
			$title = trim( preg_replace( '/\s*\(\d+\)\s*$/', '', wp_strip_all_tags( $match[2] ) ) );
			$toc[] = array(
				'id'    => $match[1],
				'title' => $title,
			);
		}

		return $toc;
	}
}
