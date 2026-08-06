<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Posts;

use Timber\Post;

/**
 * Timber post model for the 'esdeveniment' post type.
 *
 * Registered through the 'timber/post/classmap' filter (see
 * Softcatala\TypeRegisters\Esdeveniment), so every esdeveniment returned by
 * Timber is an instance of this class, both in PHP and in Twig.
 *
 * Note: in PHP always call these as methods ($esdeveniment->start_date()).
 * Property syntax goes through Timber\Core::__get(), which resolves postmeta
 * before methods, so a meta row with the same name would shadow the method.
 */
class Esdeveniment extends Post {

	/**
	 * Start of the event, as a Unix timestamp.
	 *
	 * @return int|null Null when the event has no start date.
	 */
	public function start_date() {
		return self::to_timestamp( $this->meta( 'data_inici' ) );
	}

	/**
	 * End of the event, as a Unix timestamp.
	 *
	 * @return int|null Null when the event has no end date.
	 */
	public function end_date() {
		return self::to_timestamp( $this->meta( 'data_fi' ) );
	}

	/**
	 * Year the event starts on, used to group the events archive.
	 *
	 * @return int|null Null when the event has no start date.
	 */
	public function year() {
		$start = $this->start_date();

		return null === $start ? null : (int) wp_date( 'Y', $start );
	}

	/**
	 * Whether the event has not finished yet. An event is still upcoming on
	 * the day it takes place, so the comparison is against today's midnight.
	 *
	 * @return bool
	 */
	public function is_upcoming() {
		$end = $this->end_date() ?? $this->start_date();

		return null !== $end && $end >= strtotime( 'today midnight' );
	}

	/**
	 * Whether the event is over.
	 *
	 * @return bool False for events with no dates at all.
	 */
	public function is_past() {
		$has_date = null !== $this->end_date() || null !== $this->start_date();

		return $has_date && ! $this->is_upcoming();
	}

	/**
	 * Reads a date meta as a timestamp.
	 *
	 * The events imported from Toolset store Unix timestamps (which is what
	 * the meta queries ordering and filtering the events compare against),
	 * while the ACF date picker stores dates as Ymd.
	 *
	 * @param mixed $value raw meta value.
	 * @return int|null
	 */
	private static function to_timestamp( $value ) {

		if ( empty( $value ) ) {
			return null;
		}

		if ( is_numeric( $value ) && ! preg_match( '/^(19|20)\d{6}$/', (string) $value ) ) {
			return (int) $value;
		}

		$timestamp = strtotime( (string) $value );

		return false === $timestamp ? null : $timestamp;
	}
}
