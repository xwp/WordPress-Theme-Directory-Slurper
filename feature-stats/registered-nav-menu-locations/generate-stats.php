#!/usr/bin/env php
<?php

chdir( __DIR__ . '/data' );
$themes_locations = array();
foreach ( glob( '*.json' ) as $theme_file ) {
	$theme_slug = basename( $theme_file, '.json' );
	$themes_locations[ $theme_slug ] = json_decode( file_get_contents( $theme_file ), true );
}

$used_location_ids = array();
$locations_per_theme = array();
foreach ( $themes_locations as $theme_locations ) {
	$count = count( $theme_locations );
	if ( ! isset( $locations_per_theme[ $count ] ) ) {
		$locations_per_theme[ $count ] = 0;
	}
	$locations_per_theme[ $count ] += 1;

	foreach ( array_keys( $theme_locations ) as $location_id ) {
		if ( ! isset( $used_location_ids[ $location_id ] ) ) {
			$used_location_ids[ $location_id ] = 0;
		}
		$used_location_ids[ $location_id ] += 1;
	}
}

arsort( $used_location_ids );
ksort( $locations_per_theme );

echo "## Number of themes using sidebar counts:\n";
foreach ( $locations_per_theme as $location_count => $theme_count ) {
	print "$location_count, $theme_count\n";
}
echo "\n\n";

echo "## Sidebar IDs used more than once:\n";
foreach ( $used_location_ids as $location_id => $count ) {
	if ( $count > 1 ) {
		print "$location_id, $count\n";
	}
}
