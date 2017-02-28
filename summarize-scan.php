#!/usr/bin/env php
<?php

$handle = fopen( $argv[1], 'r' ) or die;

$scan_info       = array();
$current_theme  = '';
$current_count   = 0;
$max_name_length = 0;

function save_current_theme_info() {
	global $scan_info, $current_theme, $current_count, $max_name_length;
	if ( $current_count > 0 ) {
		array_push( $scan_info, array(
			'theme_name'  => $current_theme,
			'matches'     => $current_count,
		) );
		$current_count = 0;
		$max_name_length = max( strlen( $current_theme ), $max_name_length );
	}
}

while ( ( $line = fgets( $handle ) ) !== false ) {
	if ( preg_match( '#^(themes/)?([^/]+)/#', $line, $match ) ) {
		$theme = $match[2];
		if ( $theme !== $current_theme ) {
			save_current_theme_info();
			$current_theme = $theme;
		}
		$current_count++;
	}
}

fclose( $handle );

save_current_theme_info();

$num_results = count( $scan_info );
fwrite( STDERR, sprintf(
	"%d matching theme%s\n",
	$num_results,
	( $num_results === 1 ? '' : 's' )
) );

echo 'Matches  ' . str_pad( 'Theme', $max_name_length - 3 ) . "Active installs\n";
echo '=======  ' . str_pad( '======', $max_name_length - 3 ) . "===============\n";

foreach ( $scan_info as $theme ) {
	$api_url = "https://api.wordpress.org/themes/info/1.1/?action=theme_information&request[slug]=$theme[theme_name]&request[fields][active_installs]=1";
	$result = json_decode( file_get_contents( $api_url ) );
	if ( $result ) {
		$active_installs = str_pad(
			number_format( $result->active_installs ),
			9, ' ', STR_PAD_LEFT
		) . '+';
	} else {
		// The themes API returns `null` for nonexistent/removed themes
		$active_installs = '  Not Found';
	}
	echo str_pad( $theme['matches'], 7, ' ', STR_PAD_LEFT )
		. '  '
		. str_pad( $theme['theme_name'], $max_name_length )
		. '  '
		. "$active_installs\n";
}
