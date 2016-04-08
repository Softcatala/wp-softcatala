<?php

function sc_get_all_profiles( $field ) {
	$profiles = get_terms( 'ajuda-projecte', array('hide_empty' =>false) );
	
	$field['choices'] = array();
	
	foreach ($profiles as $profile) {
		$field['choices'][ $profile->slug ] = $profile->name;
	}
	
	return $field;
}
add_filter('acf/load_field/name=perfil', 'sc_get_all_profiles');


