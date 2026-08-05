<?php
/**
 * [ia-data url="..." format="table|graph"] shortcode: fetches a JSON
 * document server-side and renders it as either a table or a bar chart.
 *
 * Expected JSON shape (see Softcatala/ai-eval-catalan's embeddings.json for
 * a real example):
 *
 *   {
 *     "text": { "field_key": "Column label", ... },  // column order + headers
 *     "metrics": {                                    // optional, per metric
 *       "field_key": {
 *         "direction": "lower_is_better",             // or "higher_is_better" (default)
 *         "scale": 100,                               // render-only multiplier
 *         "suffix": " %",
 *         "decimals": 2,
 *         "success": { "max": 0.05, "color": "#388e3c" },
 *         "warning": { "max": 0.10, "color": "#f9a825" },
 *         "error":   { "color": "#c62828" }
 *       }
 *     },
 *     "data": [ { "field_key": value, ... }, ... ]     // one row per entry
 *   }
 *
 * "metrics" entries are keyed by field key, so a document exposing several
 * metrics can describe each one. Every key is optional; a metric with no
 * entry keeps the built-in defaults (stylesheet bar color, no threshold
 * line, raw value at the shortcode's "decimals").
 *
 * - "direction" selects how the cutoffs are read: "higher_is_better" (the
 *   default, also used for any unrecognized value) takes success/warning
 *   ".min", "lower_is_better" takes ".max". "error" is the fallback bucket
 *   and carries a color only.
 * - Cutoffs are always expressed in raw data units, never scaled: WER's
 *   success.max is 0.05, not 5. "scale"/"suffix"/"decimals" are
 *   presentation only and apply to row values and the threshold label
 *   alike, in both formats.
 * - Labels stay in "text" -- "metrics" never carries a display name.
 *
 * A legacy single-metric "thresholds" object is still honored when its
 * "metric" key names the column being rendered:
 *
 *   "thresholds": { "metric": "composite", "direction": "higher_is_better",
 *                   "success": { "min": 0.85, "color": "#388e3c" }, ... }
 *
 * Resolution order per metric: shortcode attribute, then "metrics", then
 * "thresholds", then the built-in default.
 *
 * Usage:
 *   [ia-data url="https://example.com/data.json" format="table"]
 *   [ia-data url="https://example.com/data.json" format="graph" title="..." caption="..."]
 *
 * format="table":
 * - Columns and headers come from the "text" object, in declared order.
 * - If a row has a field matching "link_field" (default "repo_url"), the
 *   "link_column" cell (default: the first column) is wrapped in a link to
 *   it. That cell is wrapped in <strong> when the row's "highlight_field"
 *   (default "cloud") is truthy. Pass highlight_field="" to disable that.
 * - The "highlight_field" itself is treated as row metadata and is not
 *   rendered as its own column, even if present in "text".
 *
 * format="graph":
 * - Renders one horizontal bar per row for the "metric" attribute, falling
 *   back to thresholds.metric and then to the last numeric column in "text"
 *   (see guess_metric()). Bar length is normalized to the highest value
 *   present (100% = current top score, not an absolute 0-1 scale) whatever
 *   the direction, so for a lower-is-better metric the longest bar is the
 *   worst row.
 * - Bar color comes from the metric's success/warning/error buckets based
 *   on where the row's value falls. Without a metric configuration every
 *   bar keeps the stylesheet's default color and no threshold line is
 *   drawn.
 * - A dashed threshold line is drawn at the metric's success cutoff
 *   (success.min or success.max, per direction), positioned with a CSS
 *   calc() (no JS) -- see assets_once() for the fixed column width
 *   constants it depends on. Its default label is "≥ x" or "≤ x", per
 *   direction.
 * - Row labels use "label_field" (default: same as the table's first
 *   column) and are bolded when "highlight_field" is truthy, same as the
 *   table's link column.
 * - "title" defaults to the metric's label from "text"; "subtitle",
 *   "caption" and "threshold_label" are free text and empty by default
 *   since they aren't derivable from the JSON schema.
 *
 * Common attributes: url (required), cache (seconds, default 3600, 0
 * disables), decimals (default 4).
 *
 * The graph's "direction", "scale", "suffix" and "decimals" can also be set
 * as shortcode attributes for one-off embeds, overriding whatever the JSON
 * declares for that metric.
 *
 * Security: requests go through wp_safe_remote_get() (SSRF guard: rejects
 * loopback/private/link-local hosts, http/https only) and responses are
 * cached in a transient. All values are escaped on output.
 */
if ( ! class_exists( 'SC_Shortcodes_IaData' ) ) :
class SC_Shortcodes_IaData {

	private static $assets_printed = false;

	public function __construct( $shortcodes_handler = null ) {
		if ( $shortcodes_handler !== null ) {
			$shortcodes_handler->add( 'ia-data', array( $this, 'shortcode' ), false );
		}
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'             => '',
				'format'          => 'table',
				'cache'           => 3600,
				'decimals'        => 4,
				'highlight_field' => 'cloud',
				// format="table" only
				'link_column'     => '',
				'link_field'      => 'repo_url',
				// format="graph" only
				'metric'          => '',
				'direction'       => '',
				'scale'           => '',
				'suffix'          => '',
				'label_field'     => '',
				'title'           => '',
				'subtitle'        => '',
				'caption'         => '',
				'threshold_label' => '',
			),
			$atts,
			'ia-data'
		);

		$url = esc_url_raw( trim( $atts['url'] ) );

		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return '';
		}

		$format = 'graph' === $atts['format'] ? 'graph' : 'table';

		$cache_ttl = max( 0, (int) $atts['cache'] );
		$cache_key = 'sc_ia_data_' . md5( wp_json_encode( $atts ) );

		if ( $cache_ttl > 0 ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$json = $this->fetch_json( $url );

		if ( null === $json || empty( $json['text'] ) || empty( $json['data'] ) || ! is_array( $json['data'] ) ) {
			return '';
		}

		$output = 'graph' === $format ? $this->render_graph( $json, $atts ) : $this->render_table( $json, $atts );

		if ( $cache_ttl > 0 ) {
			set_transient( $cache_key, $output, $cache_ttl );
		}

		return $output;
	}

	private function fetch_json( $url ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $content_type && false === strpos( $content_type, 'json' ) && false === strpos( $content_type, 'text' ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Per-metric configuration for $metric: "metrics" wins over the legacy
	 * single-metric "thresholds" object, which only applies when its own
	 * "metric" key names this column. Returns an empty array when the
	 * document describes nothing for it.
	 *
	 * @param array  $json   Decoded JSON document.
	 * @param string $metric Field key of the metric.
	 * @return array Metric configuration, empty when undescribed.
	 */
	private function metric_config( $json, $metric ) {
		if ( isset( $json['metrics'][ $metric ] ) && is_array( $json['metrics'][ $metric ] ) ) {
			return $json['metrics'][ $metric ];
		}

		$legacy = isset( $json['thresholds'] ) && is_array( $json['thresholds'] ) ? $json['thresholds'] : array();

		if ( isset( $legacy['metric'] ) && is_string( $legacy['metric'] ) && $legacy['metric'] === $metric ) {
			return $legacy;
		}

		return array();
	}

	/**
	 * True unless the configuration explicitly asks for lower_is_better, so
	 * an unrecognized direction falls back to the documented default rather
	 * than to a third, undefined behavior.
	 *
	 * @param array $config Metric configuration.
	 * @return bool True when larger values are better.
	 */
	private function is_higher_better( $config ) {
		return ! isset( $config['direction'] ) || 'lower_is_better' !== $config['direction'];
	}

	/**
	 * The cutoff a success/warning bucket carries, read from the key that
	 * matches the direction ("min" going up, "max" going down). Null when
	 * the bucket doesn't declare one -- a bucket keyed the wrong way round
	 * is ignored rather than silently reinterpreted.
	 *
	 * @param array $bucket           Success or warning bucket.
	 * @param bool  $higher_is_better Direction of the metric.
	 * @return float|null The cutoff, or null when the bucket declares none.
	 */
	private function cutoff( $bucket, $higher_is_better ) {
		$key = $higher_is_better ? 'min' : 'max';

		return isset( $bucket[ $key ] ) && is_numeric( $bucket[ $key ] ) ? (float) $bucket[ $key ] : null;
	}

	/**
	 * Applies a metric's "scale"/"suffix"/"decimals" to a numeric value.
	 * Callers must escape the result.
	 *
	 * @param int|float $value            Raw value from the data.
	 * @param array     $config           Metric configuration.
	 * @param int       $default_decimals Fallback decimals from the shortcode.
	 * @return string Formatted, unescaped value.
	 */
	private function format_metric_number( $value, $config, $default_decimals ) {
		$decimals = isset( $config['decimals'] ) && is_numeric( $config['decimals'] ) ? (int) $config['decimals'] : $default_decimals;
		$scale    = isset( $config['scale'] ) && is_numeric( $config['scale'] ) ? (float) $config['scale'] : 1;
		$suffix   = isset( $config['suffix'] ) ? (string) $config['suffix'] : '';

		return number_format( (float) $value * $scale, $decimals ) . $suffix;
	}

	/**
	 * True when $config asks for anything the default formatting wouldn't
	 * already do. Guards format_value()'s existing behavior: routing every
	 * numeric cell through number_format() would turn an integer column
	 * such as "dim": 768 into "768.0000".
	 *
	 * @param array $config Metric configuration.
	 * @return bool True when the metric declares its own number formatting.
	 */
	private function has_number_format( $config ) {
		return isset( $config['scale'] ) || isset( $config['suffix'] ) || isset( $config['decimals'] );
	}

	/**
	 * format="table"
	 */
	private function render_table( $json, $atts ) {
		$labels          = $json['text'];
		$rows            = $json['data'];
		$highlight_field = $atts['highlight_field'];
		$link_field      = $atts['link_field'];
		$decimals        = (int) $atts['decimals'];

		$columns = array_keys( $labels );
		if ( '' !== $highlight_field ) {
			$columns = array_diff( $columns, array( $highlight_field ) );
		}

		$link_column = '' !== $atts['link_column'] ? $atts['link_column'] : reset( $columns );

		$html  = '<table class="sc-json-table">';
		$html .= '<thead><tr>';
		foreach ( $columns as $key ) {
			$html .= '<th>' . esc_html( $labels[ $key ] ) . '</th>';
		}
		$html .= '</tr></thead>';

		$configs = array();
		foreach ( $columns as $key ) {
			$configs[ $key ] = $this->metric_config( $json, $key );
		}

		$html .= '<tbody>';
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$html .= '<tr>';
			foreach ( $columns as $key ) {
				$raw = isset( $row[ $key ] ) ? $row[ $key ] : '';

				if ( is_numeric( $raw ) && $this->has_number_format( $configs[ $key ] ) ) {
					$value = esc_html( $this->format_metric_number( $raw, $configs[ $key ], $decimals ) );
				} else {
					$value = $this->format_value( $raw, $decimals );
				}

				if ( $key === $link_column && ! empty( $row[ $link_field ] ) ) {
					$value = '<a href="' . esc_url( $row[ $link_field ] ) . '" target="_blank" rel="noopener noreferrer">' . $value . '</a>';

					if ( '' !== $highlight_field && ! empty( $row[ $highlight_field ] ) ) {
						$value = '<strong>' . $value . '</strong>';
					}
				}

				$html .= '<td>' . $value . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';

		return $html;
	}

	private function format_value( $value, $decimals ) {
		if ( is_float( $value ) ) {
			return esc_html( number_format( $value, $decimals ) );
		}

		if ( is_bool( $value ) ) {
			return $value ? esc_html__( 'Sí', 'wp-softcatala' ) : '';
		}

		if ( is_scalar( $value ) ) {
			return esc_html( (string) $value );
		}

		return '';
	}

	/**
	 * format="graph"
	 */
	private function render_graph( $json, $atts ) {
		$labels     = $json['text'];
		$rows       = $json['data'];
		$thresholds = isset( $json['thresholds'] ) && is_array( $json['thresholds'] ) ? $json['thresholds'] : array();
		$decimals   = (int) $atts['decimals'];

		$columns         = array_keys( $labels );
		$label_field     = '' !== $atts['label_field'] ? $atts['label_field'] : reset( $columns );
		$highlight_field = $atts['highlight_field'];

		$metric = '' !== $atts['metric'] ? $atts['metric'] : ( isset( $thresholds['metric'] ) ? $thresholds['metric'] : '' );

		if ( '' === $metric ) {
			$metric = $this->guess_metric( $columns, $rows, array( $label_field, $highlight_field ) );
		}

		if ( '' === $metric || ! isset( $labels[ $metric ] ) ) {
			return '';
		}

		$values = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) && isset( $row[ $metric ] ) && is_numeric( $row[ $metric ] ) ) {
				$values[] = (float) $row[ $metric ];
			}
		}

		if ( empty( $values ) ) {
			return '';
		}

		$max_value = max( $values );

		$config = $this->metric_config( $json, $metric );

		foreach ( array( 'direction', 'scale', 'suffix' ) as $override ) {
			if ( '' !== $atts[ $override ] ) {
				$config[ $override ] = $atts[ $override ];
			}
		}

		$higher_is_better = $this->is_higher_better( $config );
		$success          = isset( $config['success'] ) && is_array( $config['success'] ) ? $config['success'] : array();
		$warning          = isset( $config['warning'] ) && is_array( $config['warning'] ) ? $config['warning'] : array();
		$error            = isset( $config['error'] ) && is_array( $config['error'] ) ? $config['error'] : array();

		$title    = '' !== $atts['title'] ? $atts['title'] : $labels[ $metric ];
		$subtitle = $atts['subtitle'];
		$caption  = $atts['caption'];

		$success_cutoff      = $this->cutoff( $success, $higher_is_better );
		$show_threshold_line = null !== $success_cutoff && $max_value > 0;
		// Clamped so a cutoff beyond the data's range stays inside the panel.
		$threshold_fraction = $show_threshold_line ? min( 1, max( 0, $success_cutoff / $max_value ) ) : 0;
		$threshold_label    = '' !== $atts['threshold_label']
			? $atts['threshold_label']
			: ( $show_threshold_line
				? ( $higher_is_better ? '≥ ' : '≤ ' ) . $this->format_metric_number( $success_cutoff, $config, $decimals )
				: '' );

		$html  = $this->assets_once();
		$html .= '<div class="charts-wrapper">';
		$html .= '<div class="chart-panel"' . ( $show_threshold_line ? ' style="--sc-threshold-fraction: ' . esc_attr( $threshold_fraction ) . ';"' : '' ) . '>';
		$html .= '<div class="chart-title">' . esc_html( $title ) . '</div>';

		if ( '' !== $subtitle ) {
			$html .= '<div class="chart-subtitle">' . esc_html( $subtitle ) . '</div>';
		}

		$html .= '<div class="chart-body">';

		if ( $show_threshold_line ) {
			$html .= '<div class="threshold-line"><span class="threshold-line-label">' . esc_html( $threshold_label ) . '</span></div>';
		}

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! isset( $row[ $metric ] ) || ! is_numeric( $row[ $metric ] ) ) {
				continue;
			}

			$value   = (float) $row[ $metric ];
			$pct     = $max_value > 0 ? ( $value / $max_value ) * 100 : 0;
			$label   = isset( $row[ $label_field ] ) ? (string) $row[ $label_field ] : '';
			$is_bold = '' !== $highlight_field && ! empty( $row[ $highlight_field ] );
			$color   = $this->bar_color( $value, $success, $warning, $error, $higher_is_better );

			$label_html = esc_html( $label );
			if ( $is_bold ) {
				$label_html = '<strong>' . $label_html . '</strong>';
			}

			$html .= '<div class="chart-row">';
			$html .= '<div class="row-label" title="' . esc_attr( $label ) . '">' . $label_html . '</div>';
			$html .= '<div class="bar-area"><div class="bar" style="width: ' . esc_attr( number_format( $pct, 2 ) ) . '%;' . ( $color ? ' background-color: ' . esc_attr( $color ) . ';' : '' ) . '"></div></div>';
			$html .= '<div class="row-value">' . esc_html( $this->format_metric_number( $value, $config, $decimals ) ) . '</div>';
			$html .= '</div>';
		}

		$html .= '</div>'; // .chart-body

		if ( '' !== $caption ) {
			$html .= '<p class="chart-caption"><em>' . esc_html( $caption ) . '</em></p>';
		}

		$html .= '</div>'; // .chart-panel
		$html .= '</div>'; // .charts-wrapper

		return $html;
	}

	/**
	 * Last numeric column in "text", used when neither the shortcode nor the
	 * JSON names a metric. Last rather than first because these documents put
	 * the summary score (composite, average, ...) after the individual ones,
	 * while the leading numeric columns tend to be metadata (dimensions,
	 * parameter counts).
	 */
	private function guess_metric( $columns, $rows, $excluded ) {
		foreach ( array_reverse( $columns ) as $key ) {
			if ( in_array( $key, $excluded, true ) ) {
				continue;
			}

			foreach ( $rows as $row ) {
				if ( is_array( $row ) && isset( $row[ $key ] ) && is_numeric( $row[ $key ] ) ) {
					return $key;
				}
			}
		}

		return '';
	}

	/**
	 * The first bucket $value falls into, best to worst. Going up a value
	 * has to reach the cutoff, going down it has to stay under it; a bucket
	 * with no cutoff for the current direction never matches, so a document
	 * that only colors part of the range still lands on "error" for the
	 * rest.
	 *
	 * @param float $value            Row value.
	 * @param array $success          Success bucket.
	 * @param array $warning          Warning bucket.
	 * @param array $error            Error bucket.
	 * @param bool  $higher_is_better Direction of the metric.
	 * @return string Color, or an empty string to keep the stylesheet default.
	 */
	private function bar_color( $value, $success, $warning, $error, $higher_is_better ) {
		foreach ( array( $success, $warning ) as $bucket ) {
			$cutoff = $this->cutoff( $bucket, $higher_is_better );

			if ( null === $cutoff ) {
				continue;
			}

			if ( $higher_is_better ? $value >= $cutoff : $value <= $cutoff ) {
				return isset( $bucket['color'] ) ? $bucket['color'] : '';
			}
		}

		return isset( $error['color'] ) ? $error['color'] : '';
	}

	/**
	 * Emits the graph's <style> block once per page, no matter how many
	 * [ia-data format="graph"] shortcodes are used.
	 *
	 * The threshold line's position is computed with calc() instead of JS:
	 * .row-label is a fixed 182px + 8px right padding (190px total) and
	 * .row-value is a fixed 48px + 6px left padding (54px total), so
	 * .bar-area always starts at 190px from .chart-body's left edge and is
	 * (100% - 244px) wide. Keep these constants in sync with the rule
	 * widths below if either changes.
	 */
	private function assets_once() {
		if ( self::$assets_printed ) {
			return '';
		}

		self::$assets_printed = true;

		return <<<HTML
<style>
  .charts-wrapper { display: flex; gap: 28px; flex-wrap: wrap; align-items: flex-start; }
  .chart-panel { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 20px 22px 16px; width: 1200px; max-width: 100%; box-sizing: border-box; }
  .chart-title { font-weight: 700; font-size: 14.5px; margin: 0 0 2px; color: #1a1a1a; }
  .chart-subtitle { color: #c62828; font-size: 12px; text-align: center; font-weight: 600; margin: 0 0 8px; }
  .chart-body { position: relative; margin-top: 20px; }
  .chart-row { display: flex; align-items: center; height: 23px; margin-bottom: 2px; }
  .row-label { width: 182px; min-width: 182px; text-align: right; font-size: 12px; padding-right: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #333; }
  .bar-area { flex: 1; height: 100%; background: #ececec; border-radius: 2px; position: relative; overflow: visible; }
  .bar { height: 100%; border-radius: 2px; min-width: 3px; background-color: #337ab7; }
  .row-value { width: 48px; min-width: 48px; font-size: 12px; color: #555; padding-left: 6px; text-align: left; }
  .threshold-line { position: absolute; top: -18px; bottom: 0; width: 0; border-left: 2px dashed #c62828; z-index: 5; pointer-events: none; left: calc(190px + (100% - 244px) * var(--sc-threshold-fraction, 0)); }
  .threshold-line-label { position: absolute; top: 0; left: 5px; font-size: 11px; color: #c62828; white-space: nowrap; font-weight: 600; line-height: 1; }
  .chart-caption { font-size: 11px; color: #777; font-style: italic; margin: 10px 0 0; }
</style>
HTML;
	}
}
endif;
