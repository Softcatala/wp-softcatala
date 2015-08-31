<?php
/**
 * Template Name: Home Softcatala
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */
?>
<?php

$context = Timber::get_context();
Timber::render( array( 'home.twig' ), $context );