#!/usr/bin/env php
<?php
/**
 * Parse all slurped themes for registered nav menu locations and output a JSON file for each in the data subdirectory.
 * Author: Weston Ruter, XWP
 *
 * Copyright (c) 2017 XWP (https://xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

namespace WordPress_Theme_Directory_Slurper\Feature_Stats\Registered_Nav_Menu_Locations;

require_once __DIR__ . '/../functions.php';

use function WordPress_Theme_Directory_Slurper\Feature_Stats\parse_array_tokens;
use function WordPress_Theme_Directory_Slurper\Feature_Stats\locate_token_sets;

/**
 * Parse themes for registered nav menu locations.
 *
 * Registered sidebars for each theme are written out into separate JSON files
 * in the data subdirectory.
 *
 * @param array $theme_slugs Theme slugs, defaults to all of the themes that have been slurped down.
 * @throws \Exception
 */
function parse_themes( $theme_slugs = array() ) {
	if ( empty( $theme_slugs ) ) {
		$theme_slugs = array_map( 'basename', glob( __DIR__ . '/../../themes/*', GLOB_ONLYDIR ) );
	}
	if ( empty( $theme_slugs ) ) {
		throw new \Exception( 'Error: First run ../../update to slurp down the themes.' );
	}

	$output_dir = __DIR__ . '/data';
	if ( ! file_exists( $output_dir ) ) {
		mkdir( $output_dir );
	}

	foreach ( $theme_slugs as $theme_slug ) {
		$parsed_sidebars_file = sprintf( '%s/%s.json', $output_dir, $theme_slug );
		if ( file_exists( $parsed_sidebars_file ) ) {
			continue;
		}

		echo "## $theme_slug\n";

		$directory = new \RecursiveDirectoryIterator( __DIR__ . '/../../themes/' . $theme_slug );
		$iterator = new \RegexIterator( new \RecursiveIteratorIterator( $directory ), '/^.+\.php$/i', \RecursiveRegexIterator::MATCH );

		$registered_locations = array();
		foreach ( $iterator as $php_file ) {

			$tokens = token_get_all( file_get_contents( $php_file->getPathname() ) );

			// Gather calls to register_nav_menu():
			foreach ( locate_token_sets( 'register_nav_menu', $tokens ) as $register_location_call_tokens ) {

				$token = array_shift( $register_location_call_tokens );
				if ( ! is_array( $token ) ) {
					fwrite( STDERR, "Warning: Missing string for register_nav_menu().\n" );
					continue;
				}
				$location = trim( $token[1], '"\'' );
				$comma_token = array_shift( $register_location_call_tokens );
				if ( ',' !== $comma_token ) {
					fwrite( STDERR, "Warning: Missing comma for {$location}\n" );
					continue;
				}

				$description = '';
				foreach ( $register_location_call_tokens as $token ) {
					if ( is_array( $token ) ) {
						$description .= $token[1];
					} else {
						$description .= $token;
					}
				}

				$registered_locations[ $location ] = $description;
			}

			// Gather calls to register_nav_menus():
			foreach ( locate_token_sets( 'register_nav_menus', $tokens ) as $register_locations_call_token_sets ) {
				$token_sets = locate_token_sets( 'array', $register_locations_call_token_sets );
				$array_tokens = array_shift( $token_sets );
				if ( empty( $array_tokens ) ) {
					$array_tokens = array();
				}

				$registered_locations = array_merge(
					$registered_locations,
					parse_array_tokens( $array_tokens )
				);

			} // End foreach().
		} // End foreach().

		file_put_contents( $parsed_sidebars_file, json_encode( $registered_locations, JSON_PRETTY_PRINT ) );
	} // End foreach().
}

// Self boot.
if ( basename( $_SERVER['SCRIPT_NAME'] ) === basename( __FILE__ ) ) {
	try {
		parse_themes( array_slice( $argv, 1 ) );
	} catch ( \Exception $e ) {
		fwrite( STDERR, $e->getMessage() . PHP_EOL );
		exit( $e->getCode() );
	}
}
