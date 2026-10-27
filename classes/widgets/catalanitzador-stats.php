<?php

namespace Softcatala\Widgets;

use Softcatala\Providers\CatalanitzadorStats as Stats;

/**
 * Displays stats for Catalanitzador
 */
class CatalanitzadorStats extends \WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$widget_ops = array(
			'classname' => 'catalanitzador_stats',
			'description' => 'Estadístiques del Catalanitzador',
		);
		parent::__construct( 'catalanitzador_stats', 'Estadístiques del Catalanitzador', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args arguments for the widget.
	 * @param array $instance widget's instance.
	 */
	public function widget( $args, $instance ) {

		$sessions = Stats::sessions();

		if ( false !== $sessions ) {
			\Timber\Timber::render( 'widgets/catalanitzador_stats.twig', array( 'sessions' => number_format( $sessions,0,',','.' ) ) );
		}
	}
}
