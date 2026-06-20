<?php

/**
 * Seed all nav menus (Recursos i Serveis, Coneixeu, Col·laboreu) on first deploy.
 *
 * Safe to run multiple times — each menu is skipped individually if its
 * location is already assigned, preserving any admin edits.
 *
 * Usage: wp sc seed-nav-menus
 */
class Seed_Nav_Menus_Command extends WP_CLI_Command {

	/**
	 * Seed all header nav menus if they have not been assigned yet.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sc seed-nav-menus
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function __invoke( $args, $assoc_args ) {
		$this->seed_menu(
			'nav-recursos',
			'Recursos i Serveis',
			array( $this, 'add_recursos_items' )
		);

		$this->seed_menu(
			'nav-coneixeu',
			'Coneixeu',
			array( $this, 'add_coneixeu_items' )
		);

		$this->seed_menu(
			'nav-collaboreu',
			'Col·laboreu',
			array( $this, 'add_collaboreu_items' )
		);
	}

	/**
	 * Seed a single menu location, skipping if already assigned.
	 *
	 * @param string   $location  Theme menu location slug.
	 * @param string   $name      Menu display name.
	 * @param callable $add_items Callback that receives the menu ID and adds items.
	 */
	private function seed_menu( $location, $name, $add_items ) {
		$locations = get_nav_menu_locations();

		if ( ! empty( $locations[ $location ] ) ) {
			WP_CLI::log( "$location menu already assigned, skipping." );
			return;
		}

		$existing = wp_get_nav_menu_object( $name );
		if ( $existing ) {
			$menu_id = $existing->term_id;
			WP_CLI::log( "'$name' menu exists without location assignment, adding items." );
		} else {
			$menu_id = wp_create_nav_menu( $name );
			if ( is_wp_error( $menu_id ) ) {
				WP_CLI::warning( "Could not create '$name' menu: " . $menu_id->get_error_message() );
				return;
			}
		}

		call_user_func( $add_items, $menu_id );

		$locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		WP_CLI::success( "'$name' menu seeded and assigned to $location." );
	}

	/**
	 * Add a top-level item and optionally its children to a menu.
	 *
	 * @param int    $menu_id  Menu ID.
	 * @param string $title    Item title.
	 * @param string $url      Item URL.
	 * @param array  $children Array of [title, url] pairs.
	 * @param string $classes  Optional CSS classes string.
	 * @return int The new item's ID.
	 */
	private function add_item( $menu_id, $title, $url, $children = array(), $classes = '' ) {
		$args = array(
			'menu-item-title'  => $title,
			'menu-item-url'    => $url,
			'menu-item-status' => 'publish',
		);

		if ( $classes ) {
			$args['menu-item-classes'] = $classes;
		}

		$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );

		foreach ( $children as $child ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $child[0],
					'menu-item-url'       => $child[1],
					'menu-item-parent-id' => $item_id,
					'menu-item-status'    => 'publish',
				)
			);
		}

		return $item_id;
	}

	/**
	 * @param int $menu_id Menu ID.
	 */
	private function add_recursos_items( $menu_id ) {
		$this->add_item( $menu_id, 'Traductor', '/traductor/' );
		$this->add_item( $menu_id, 'Corrector', '/corrector/' );

		$this->add_item(
			$menu_id,
			'Diccionaris i eines',
			'#',
			array(
				array( 'Diccionari de sinònims', '/diccionari-de-sinonims/' ),
				array( 'Diccionari anglès-català', '/diccionari-angles-catala/' ),
				array( 'Hora en català', '/hora-en-catala/' ),
				array( 'Separador i comptador de síl·labes', '/sillabes/' ),
				array( 'Nombres en lletres', '/nombres-en-lletres/' ),
				array( 'Conjugador de verbs', '/conjugador-de-verbs/' ),
				array( 'Resum de textos', '/resum-de-textos-en-catala/' ),
				array( "Transcripció d'àudio i vídeos a text", '/transcripcio/' ),
				array( 'Doblatge de vídeos automàtic', '/doblatge/' ),
			)
		);

		$this->add_item( $menu_id, 'Aplicacions', '/programes/', array(), 'is-megamenu-programes' );

		$this->add_item(
			$menu_id,
			'Recursos per a traductors',
			'/recursos/',
			array(
				array( "Guia d'estil", '/guia-estil-de-softcatala/' ),
				array( 'Memòries de traducció', '/recursos/memories/' ),
				array( 'Estàndards ISO', '/estandard-iso-catala/' ),
				array( 'Terminologia', '/recursos/terminologia/' ),
				array( 'Adaptador a variant valenciana', 'https://www.softvalencia.org/adaptador/' ),
				array( 'Dades obertes', '/dades-obertes/' ),
			)
		);

		$this->add_item(
			$menu_id,
			'Ordinadors i mòbils',
			'/ordinadors-i-mobils-en-catala/',
			array(
				array( 'Catalanitzador de Softcatalà', '/catalanitzador/' ),
				array( "Guia d'aparells en català", '/tutorials/guia-de-mobils-i-tauletes-en-catala/' ),
				array( 'Tutorials', '/ordinadors-i-mobils-en-catala/tutorials/' ),
				array( 'La IA al vostre ordinador personal', '/ia-local/' ),
			)
		);
	}

	/**
	 * @param int $menu_id Menu ID.
	 */
	private function add_coneixeu_items( $menu_id ) {
		$this->add_item(
			$menu_id,
			'Què és Softcatalà',
			'/que-es-softcatala/',
			array(
				array( "L'associació", '/que-es-softcatala/associacio/' ),
				array( 'Història', '/que-es-softcatala/historia/' ),
				array( 'PMF sobre Softcatalà', '/que-es-softcatala/pmf-sobre-softcatala/' ),
				array( 'Premis i guardons', '/que-es-softcatala/premis-i-guardons/' ),
			)
		);

		$this->add_item(
			$menu_id,
			'En què treballem',
			'/en-que-treballem/',
			array(
				array( 'Traducció de programari', '/en-que-treballem/traduccio-de-programari/' ),
				array( 'Eines lingüístiques', '/en-que-treballem/eines-linguistiques/' ),
				array( 'Recursos per a traductors', '/en-que-treballem/recursos-traductors/' ),
				array( 'Foment del català a les noves tecnologies', '/en-que-treballem/foment-del-catala-tic/' ),
				array( 'Activisme digital', '/en-que-treballem/activisme-digital/' ),
			)
		);

		$this->add_item( $menu_id, 'Membres', '/membres/' );

		$this->add_item(
			$menu_id,
			'Com ens organitzem',
			'/com-ens-organitzem/',
			array(
				array( 'Presa de decisions', '/com-ens-organitzem/presa-de-decisions/' ),
				array( "Funcionament econòmic de l'associació", '/com-ens-organitzem/funcionament-economic-de-lassociacio/' ),
				array( 'Nous membres', '/com-ens-organitzem/nous-membres/' ),
				array( 'Codi de conducta', '/com-ens-organitzem/codi-de-conducta/' ),
				array( 'Comunicació amb els mitjans', '/com-ens-organitzem/comunicacio-amb-els-mitjans/' ),
			)
		);

		$this->add_item( $menu_id, 'Blogs', '/planeta/' );

		$this->add_item(
			$menu_id,
			'Podcasts',
			'/podcasts/',
			array(
				array( "Quinze glaçons d'hidrogen", '/podcasts/quinze-glacons-hidrogen/' ),
			)
		);
	}

	/**
	 * @param int $menu_id Menu ID.
	 */
	private function add_collaboreu_items( $menu_id ) {
		$this->add_item( $menu_id, 'Raons per a col·laborar', '/col·laboreu/raons-per-col·laborar/' );
		$this->add_item( $menu_id, 'Traductors i correctors', '/col·laboreu/traduccio-i-correccio/' );
		$this->add_item( $menu_id, 'Dissenyadors', '/col·laboreu/disseny/' );
		$this->add_item( $menu_id, 'Desenvolupadors', '/col·laboreu/desenvolupament/' );
		$this->add_item( $menu_id, 'Gestors de contingut', '/col·laboreu/gestio-de-contingut/' );
		$this->add_item( $menu_id, 'Coordinadors d\'esdeveniments', '/col·laboreu/coordinacio-desdeveniments/' );
	}
}
