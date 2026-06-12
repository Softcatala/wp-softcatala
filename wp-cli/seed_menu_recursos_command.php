<?php

/**
 * Seed the nav-recursos menu with its initial structure on first deploy.
 *
 * Safe to run multiple times — if a menu is already assigned to the
 * nav-recursos location, the command exits immediately without making
 * any changes, preserving admin edits.
 *
 * Usage: wp sc seed-menu-recursos
 */
class Seed_Menu_Recursos_Command extends WP_CLI_Command {

	/**
	 * Seed the nav-recursos menu if it has not been assigned yet.
	 *
	 * Creates the menu with all initial items and assigns it to the
	 * nav-recursos theme location. On subsequent calls, exits immediately
	 * if the location already has a menu assigned.
	 *
	 * ## EXAMPLES
	 *
	 *     # Seed Recursos i Serveis nav menu (run after deploy)
	 *     wp sc seed-menu-recursos
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function __invoke( $args, $assoc_args ) {
		$locations = get_nav_menu_locations();

		if ( ! empty( $locations['nav-recursos'] ) ) {
			WP_CLI::log( 'nav-recursos menu already exists, skipping.' );
			return;
		}

		$existing_menu = wp_get_nav_menu_object( 'Recursos i Serveis' );
		if ( $existing_menu ) {
			$menu_id = $existing_menu->term_id;
			WP_CLI::log( 'Menu already exists, adding items and assigning to location.' );
			$this->add_items( $menu_id );
		} else {
			$menu_id = wp_create_nav_menu( 'Recursos i Serveis' );
			if ( is_wp_error( $menu_id ) ) {
				WP_CLI::error( 'Could not create menu: ' . $menu_id->get_error_message() );
				return;
			}
			$this->add_items( $menu_id );
		}

		$locations['nav-recursos'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		WP_CLI::success( 'nav-recursos menu created and assigned.' );
	}

	/**
	 * Add all initial menu items to the given menu.
	 *
	 * @param int $menu_id The menu ID to add items to.
	 */
	private function add_items( $menu_id ) {
		// Top-level plain items.
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Traductor',
				'menu-item-url'    => '/traductor/',
				'menu-item-status' => 'publish',
			)
		);

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Corrector',
				'menu-item-url'    => '/corrector/',
				'menu-item-status' => 'publish',
			)
		);

		// Aplicacions — megamenu sentinel item.
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'   => 'Aplicacions',
				'menu-item-url'     => '/programes/',
				'menu-item-classes' => 'is-megamenu-programes',
				'menu-item-status'  => 'publish',
			)
		);

		// Diccionaris i eines — dropdown parent.
		$diccionaris_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Diccionaris i eines',
				'menu-item-url'    => '#',
				'menu-item-status' => 'publish',
			)
		);

		$diccionari_children = array(
			array( 'Diccionari de sinònims', '/diccionari-de-sinonims/' ),
			array( 'Diccionari anglès-català', '/diccionari-angles-catala/' ),
			array( 'Hora en català', '/hora-en-catala/' ),
			array( 'Separador i comptador de síl·labes', '/sillabes/' ),
			array( 'Nombres en lletres', '/nombres-en-lletres/' ),
			array( 'Conjugador de verbs', '/conjugador-de-verbs/' ),
			array( 'Resum de textos', '/resum-de-textos-en-catala/' ),
			array( 'Transcripció d\'àudio i vídeos a text', '/transcripcio/' ),
			array( 'Doblatge de vídeos automàtic', '/doblatge/' ),
		);

		foreach ( $diccionari_children as $child ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $child[0],
					'menu-item-url'       => $child[1],
					'menu-item-parent-id' => $diccionaris_id,
					'menu-item-status'    => 'publish',
				)
			);
		}

		// Recursos per a traductors — dropdown parent.
		$recursos_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Recursos per a traductors',
				'menu-item-url'    => '/recursos/',
				'menu-item-status' => 'publish',
			)
		);

		$recursos_children = array(
			array( 'Guia d\'estil', '/guia-estil-de-softcatala/' ),
			array( 'Memòries de traducció', '/recursos/memories/' ),
			array( 'Estàndards ISO', '/estandard-iso-catala/' ),
			array( 'Terminologia', '/recursos/terminologia/' ),
			array( 'Adaptador a variant valenciana', 'https://www.softvalencia.org/adaptador/' ),
			array( 'Dades obertes', '/dades-obertes/' ),
		);

		foreach ( $recursos_children as $child ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $child[0],
					'menu-item-url'       => $child[1],
					'menu-item-parent-id' => $recursos_id,
					'menu-item-status'    => 'publish',
				)
			);
		}

		// Ordinadors i mòbils — dropdown parent.
		$ordinadors_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Ordinadors i mòbils',
				'menu-item-url'    => '/ordinadors-i-mobils-en-catala/',
				'menu-item-status' => 'publish',
			)
		);

		$ordinadors_children = array(
			array( 'Catalanitzador de Softcatalà', '/catalanitzador/' ),
			array( 'Guia d\'aparells en català', '/tutorials/guia-de-mobils-i-tauletes-en-catala/' ),
			array( 'Tutorials', '/ordinadors-i-mobils-en-catala/tutorials/' ),
			array( 'La IA al vostre ordinador personal', '/la-intelligencia-artificial-al-vostre-ordinador-personal/' ),
		);

		foreach ( $ordinadors_children as $child ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $child[0],
					'menu-item-url'       => $child[1],
					'menu-item-parent-id' => $ordinadors_id,
					'menu-item-status'    => 'publish',
				)
			);
		}
	}
}
