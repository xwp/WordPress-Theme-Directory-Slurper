#!/usr/bin/env php
<?php
/**
 * Parse all slurped themes for registered sidebars and output a JSON file for each in the data subdirectory.
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

namespace WordPress_Theme_Directory_Slurper\Feature_Stats\Registered_Widget_Sidebars;

require_once __DIR__ . '/../functions.php';

use function WordPress_Theme_Directory_Slurper\Feature_Stats\parse_array_tokens;
use function WordPress_Theme_Directory_Slurper\Feature_Stats\locate_token_sets;

/**
 * Parse themes for registered sidebars.
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

		$registered_sidebars = array();
		foreach ( $iterator as $php_file ) {
			$tokens = token_get_all( file_get_contents( $php_file->getPathname() ) );

			// Gather calls to register_sidebar():
			foreach ( locate_token_sets( 'register_sidebar', $tokens ) as $register_sidebar_call_tokens ) {
				foreach ( locate_token_sets( 'array', $register_sidebar_call_tokens ) as $sidebar_args_array_tokens ) {
					$sidebar_args = array_merge(
						array(
							'name' => sprintf( 'Sidebar %d', count( $registered_sidebars ) + 1 ),
							'id' => sprintf( 'sidebar-%d', count( $registered_sidebars ) + 1 ),
						),
						parse_array_tokens( $sidebar_args_array_tokens )
					);

					if ( isset( $registered_sidebars[ $sidebar_args['id'] ] ) ) {
						fwrite( STDERR, "Warning: Duplicate registered sidebar: {$sidebar_args['id']}\n" );
					}

					$registered_sidebars[ $sidebar_args['id'] ] = $sidebar_args;
				}
			}

			// Gather calls to register_sidebars():
			foreach ( locate_token_sets( 'register_sidebars', $tokens ) as $register_sidebars_call_token_sets ) {
				$count_token = array_shift( $register_sidebars_call_token_sets );
				if ( empty( $count_token ) || T_LNUMBER !== $count_token[0] ) {
					fwrite( STDERR, "Warning: Unexpected lack of integer \$number arg for register_sidebars() call.\n" );
					continue;
				}
				$number = $count_token[1];

				$token_sets = locate_token_sets( 'array', $register_sidebars_call_token_sets );
				$array_tokens = array_shift( $token_sets );
				if ( empty( $array_tokens ) ) {
					$array_tokens = array();
				}
				$args = parse_array_tokens( $array_tokens );

				// See register_sidebars():

				for ( $i = 1; $i <= $number; $i++ ) {
					$_args = $args;

					if ( $number > 1 ) {
						$_args['name'] = isset( $args['name'] ) ? sprintf( $args['name'], $i ) : sprintf( 'Sidebar %d', $i );
					} else {
						$_args['name'] = isset( $args['name'] ) ? $args['name'] : 'Sidebar';
					}

					// Custom specified ID's are suffixed if they exist already.
					// Automatically generated sidebar names need to be suffixed regardless starting at -0
					if ( isset( $args['id'] ) ) {
						$_args['id'] = $args['id'];
						$n = 2; // Start at -2 for conflicting custom ID's
						while ( isset( $registered_sidebars[ $_args['id'] ] ) ) {
							$_args['id'] = $args['id'] . '-' . $n++;
						}
					} else {
						$n = count( $registered_sidebars );
						do {
							$_args['id'] = 'sidebar-' . ++$n;
						} while ( isset( $registered_sidebars[ $_args['id'] ] ) );
					}
					$registered_sidebars[ $_args['id'] ] = $_args;
				}
			} // End foreach().
		} // End foreach().

		file_put_contents( $parsed_sidebars_file, json_encode( $registered_sidebars, JSON_PRETTY_PRINT ) );
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
