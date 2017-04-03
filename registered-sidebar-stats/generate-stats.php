#!/usr/bin/env php
<?php

chdir( __DIR__ . '/data' );
$themes_sidebars = array();
foreach ( glob( '*.json' ) as $theme_file ) {
	$theme_slug = basename( $theme_file, '.json' );
	$themes_sidebars[ $theme_slug ] = json_decode( file_get_contents( $theme_file ), true );
}

$used_sidebar_ids = array();
$sidebars_per_theme = array();
$normalized_sidebars_per_theme = array();
foreach ( $themes_sidebars as $theme_sidebars ) {
	$count = count( $theme_sidebars );
	if ( ! isset( $sidebars_per_theme[ $count ] ) ) {
		$sidebars_per_theme[ $count ] = 0;
	}
	$sidebars_per_theme[ $count ] += 1;

	foreach ( array_keys( $theme_sidebars ) as $sidebar_id ) {
		if ( ! isset( $used_sidebar_ids[ $sidebar_id ] ) ) {
			$used_sidebar_ids[ $sidebar_id ] = 0;
		}
		$used_sidebar_ids[ $sidebar_id ] += 1;

		$normalized_sidebar_id = preg_replace( '/\W/', '', strtolower( $sidebar_id ) );
		$normalized_sidebar_id = str_replace( 'widgetarea', '', $normalized_sidebar_id );
		$normalized_sidebar_id = str_replace( 'sidebar', '', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^first(.+)/', '$11', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^(.+)first/', '$11', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^second(ary)?$/', '2', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^second(.+)$/', '$12', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^(.+)second$/', '$12', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^third$/', '3', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^third(.+)$/', '$13', $normalized_sidebar_id );
		$normalized_sidebar_id = preg_replace( '/^(.+)third/', '$13', $normalized_sidebar_id );
		if ( ! isset( $used_normalized_sidebar_ids[ $normalized_sidebar_id ] ) ) {
			$used_normalized_sidebar_ids[ $normalized_sidebar_id ] = 0;
		}
		$used_normalized_sidebar_ids[ $normalized_sidebar_id ] += 1;
	}
}

arsort( $used_sidebar_ids );
arsort( $used_normalized_sidebar_ids );
ksort( $sidebars_per_theme );

echo "## Number of themes using sidebar counts:\n";
foreach ( $sidebars_per_theme as $sidebar_count => $theme_count ) {
	print "$sidebar_count, $theme_count\n";
}
echo "\n\n";

echo "## Sidebar IDs used more than once:\n";
foreach ( $used_sidebar_ids as $sidebar_id => $count ) {
	if ( $count > 1 ) {
		print "$sidebar_id, $count\n";
	}
}

echo "## Normalized sidebar IDs used more than once:\n";
foreach ( $used_normalized_sidebar_ids as $sidebar_id => $count ) {
	if ( $count > 1 ) {
		print "$sidebar_id, $count\n";
	}
}
