<?php
/**
 * Block editor configuration: theme.json data, allowed blocks, post types and
 * block style variations. Applied by Softcatala\Blocks\Editor, only while the
 * `block_editor` feature flag is on.
 *
 * The theme.json part is PHP rather than a theme.json file at the theme root
 * so the flag can switch it off: a theme.json file is always read.
 *
 * Colours mirror frontend/src/scss/_tokens.scss. The styles' CSS lives in
 * frontend/src/scss/_blocks.scss.
 *
 * @package Softcatala
 */

return array_merge(

	/*
	 * Shared with the frontend block-markup tests (frontend/tests/blocks.test.ts):
	 * post_types, blocks (the inserter), html_blocks (unfiltered_html users only),
	 * styles (block => style name => label; CSS in frontend/src/scss/_blocks.scss),
	 * pattern_category.
	 */
	json_decode( file_get_contents( __DIR__ . '/block-editor.json' ), true ),

	array(

		/*
		 * Merged into the theme origin of theme.json (wp_theme_json_data_theme).
		 * Mostly restrictions: no free colour, size or spacing pickers.
		 */
		'theme_json'  => array(
			'version'  => 3,
			'settings' => array(
				'appearanceTools' => false,
				'color'           => array(
					'custom'           => false,
					'customGradient'   => false,
					'customDuotone'    => false,
					'defaultPalette'   => false,
					'defaultGradients' => false,
					'defaultDuotone'   => false,
					'duotone'          => array(),
					'link'             => false,
					'palette'          => array(
						// Pantone 186, as the site renders it (--color-brand).
						array(
							'slug'  => 'vermell-corporatiu',
							'name'  => 'Vermell corporatiu',
							'color' => '#c43434',
						),
						// Pantone 484.
						array(
							'slug'  => 'vermell-fosc',
							'name'  => 'Vermell fosc',
							'color' => '#9a3324',
						),
						// Pantone 1795.
						array(
							'slug'  => 'vermell-llampant',
							'name'  => 'Vermell llampant',
							'color' => '#d22630',
						),
						array(
							'slug'  => 'text',
							'name'  => 'Text',
							'color' => '#333333',
						),
						array(
							'slug'  => 'text-secundari',
							'name'  => 'Text secundari',
							'color' => '#797979',
						),
						array(
							'slug'  => 'fons-gris',
							'name'  => 'Fons gris',
							'color' => '#f5f5f5',
						),
						array(
							'slug'  => 'fons-vermell',
							'name'  => 'Fons vermell clar',
							'color' => '#fff7f7',
						),
						array(
							'slug'  => 'blanc',
							'name'  => 'Blanc',
							'color' => '#ffffff',
						),
						// Pantone 151 (orange) is deliberately absent: 2.5:1 on
						// white, so it is an accent for styles, never a text colour.
					),
					'gradients'        => array(
						array(
							'slug'     => 'degradat-corporatiu',
							'name'     => 'Degradat corporatiu',
							'gradient' => 'linear-gradient(135deg, #9a3324 0%, #d22630 100%)',
						),
					),
				),
				'typography'      => array(
					'customFontSize'   => false,
					'defaultFontSizes' => false,
					'fluid'            => false,
					'lineHeight'       => false,
					'letterSpacing'    => false,
					'fontStyle'        => false,
					'fontWeight'       => false,
					'textTransform'    => false,
					'textDecoration'   => false,
					'textAlign'        => true,
					'dropCap'          => false,
					'writingMode'      => false,
					'fontFamilies'     => array(),
					'fontSizes'        => array(
						array(
							'slug' => 'petit',
							'name' => 'Petit',
							'size' => '14px',
						),
						array(
							'slug' => 'normal',
							'name' => 'Normal',
							'size' => '16px',
						),
						array(
							'slug' => 'mitja',
							'name' => 'Mitjà',
							'size' => '20px',
						),
						array(
							'slug' => 'gran',
							'name' => 'Gran',
							'size' => '28px',
						),
					),
				),
				'spacing'         => array(
					'customSpacingSize'   => false,
					'defaultSpacingSizes' => false,
					'margin'              => false,
					'padding'             => false,
					'spacingSizes'        => array(),
				),
				'border'          => array(
					'color'  => false,
					'radius' => false,
					'style'  => false,
					'width'  => false,
				),
				'shadow'          => array(
					'defaultPresets' => false,
					'presets'        => array(),
				),
				'dimensions'      => array(
					'aspectRatio' => false,
					'minHeight'   => false,
				),
				'background'      => array(
					'backgroundImage' => false,
					'backgroundSize'  => false,
				),
				'position'        => array(
					'sticky' => false,
				),
				// The content column of plantilla-text-menuleft (col-sm-9 of the
				// 1200px container, minus gutters): what the editor canvas shows.
				'layout'          => array(
					'contentSize' => '855px',
				),
			),
		),
	)
);
