<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Find unused themes on a multisite network.
 *
 * Iterates through all sites on a network to find themes which aren't enabled
 * on any site.
 */
$find_unused_themes_command = function() {

	$response = WP_CLI::launch_self( 'site list', array(), array( 'format' => 'json' ), false, true );
	$sites = json_decode( $response->stdout );
	$unused = array();
	$used = array();
	foreach( $sites as $site ) {
		WP_CLI::log( "Checking {$site->url} for unused themes..." );
		$themes = WP_CLI::runcommand( "--url={$site->url} theme list --format=json", array(
			'return'  => true,
			'parse'   => 'json',
			'launch'  => true,
		) );
		foreach( $themes as $theme ) {
			if ( 'no' == $theme['enabled'] && 'inactive' == $theme['status'] && ! in_array( $theme['name'], $used ) ) {
				$unused[ $theme['name'] ] = $theme;
			} else {
				if ( isset( $unused[ $theme['name'] ] ) ) {
					unset( $unused[ $theme['name'] ] );
				}
				$used[] = $theme['name'];
			}
		}
	}
	WP_CLI\Utils\format_items( 'table', $unused, array( 'name', 'version' ) );
};
WP_CLI::add_command( 'find-unused-themes', $find_unused_themes_command, array(
	'before_invoke' => function(){
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite install.' );
		}
	},
) );
