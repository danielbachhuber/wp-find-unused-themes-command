<?php

use cli\progress\Bar;
use function WP_CLI\Utils\{ format_items };

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Find unused themes on a multisite network.
 *
 * Iterates through all sites on a network to find themes which aren't enabled
 * on any site.
 *
 * ## OPTIONS
 *
 * [--verbose]
 * : Shows the number of sites a theme is active on, and details about its parent/child themes.
 *
 * ## EXAMPLES
 *
 * wp find-unused-themes
 * wp find-unused-themes --verbose
 *
 * @param array $args
 * @param array $assoc_args
 */
$find_unused_themes_command = function( $args, $assoc_args) {
	$sites = get_sites( array( 'number' => false ) );
	$all_themes = wp_get_themes( array(
		'errors'  => null,
		'allowed' => null,
	) );

	foreach ( array_keys( $all_themes ) as $slug ) {
		// Need slug as key for efficient access, and as value so `format_items()` can print it.
		$themes_site_count[ $slug ] = array(
			'slug'         => $slug,
			'active sites' => 0,
		);
		$parent = $all_themes[ $slug ]->parent();
		$themes_site_count[ $slug ]['parent'] = $parent ? $parent->get_stylesheet() : '';
		$themes_site_count[ $slug ]['active children'] = empty( $themes_site_count[ $slug ]['parent'] ) ? 0 : '';
	}

	if ( count( $sites ) > 10000 ) {
		WP_CLI::warning( "Large network detected, this could take awhile.\n" );
	}

	$notify = new Bar( 'Checking sites', count( $sites ) );

	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );

		$current_theme = get_option( 'stylesheet' );
		$parent        = $themes_site_count[ $current_theme ]['parent'];

		if ( isset ( $all_themes[ $current_theme ] ) ) {
			$themes_site_count[ $current_theme ]['active sites']++;
		}

		if ( ! empty( $parent ) && isset ( $all_themes[ $parent ] ) ) {
			$themes_site_count[ $parent ]['active children']++;
		}

		restore_current_blog();
		$notify->tick();
	}

	$notify->finish();

	if ( isset( $assoc_args['verbose'] ) ) {
		WP_CLI::log( "\nAll themes installed on the network:\n" );
		format_items( 'table', $themes_site_count, array( 'slug', 'parent', 'active sites', 'active children' ) );
	} else {
		$unused_themes = array_filter( $themes_site_count, function( $theme ) {
			return $theme['active sites'] === 0 && empty( $theme['active children'] );
		} );

		WP_CLI::log( "\nInstalled themes that aren't active on any site, and don't have any active child themes:\n" );
		format_items( 'table', $unused_themes, array( 'slug' ) );
	}
};
WP_CLI::add_command( 'find-unused-themes', $find_unused_themes_command, array(
	'before_invoke' => function(){
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite install.' );
		}
	},
) );
