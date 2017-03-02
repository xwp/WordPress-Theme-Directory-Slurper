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

namespace WordPress_Theme_Directory_Slurper\Registered_Sidebar_Stats;

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
		$theme_slugs = array_map( 'basename', glob( __DIR__ . '/../themes/*', GLOB_ONLYDIR ) );
	}
	if ( empty( $theme_slugs ) ) {
		throw new \Exception( 'Error: First run ../update to slurp down the themes.' );
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

		$directory = new \RecursiveDirectoryIterator( __DIR__ . '/../themes/' . $theme_slug );
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

/**
 * Parse an array of array tokens into an associative array.
 *
 * @param array $array_tokens Array tokens.
 * @return array Associative array.
 */
function parse_array_tokens( $array_tokens ) {
	$parsed_array = array();
	$paren_depth = 0;
	$args_tokens = array();
	$current_item = 0;
	for ( $i = 0; $i < count( $array_tokens ); $i += 1 ) {
		$token = $array_tokens[ $i ];
		if ( '(' === $token ) {
			$paren_depth += 1;
		} elseif ( ')' === $token ) {
			$paren_depth -= 1;
		} elseif ( ',' === $token && 0 === $paren_depth ) {
			$current_item += 1;
			continue;
		}
		$args_tokens[ $current_item ][] = $token;
	}

	foreach ( $args_tokens as $arg_tokens ) {
		if ( empty( $arg_tokens[0] ) || T_CONSTANT_ENCAPSED_STRING !== $arg_tokens[0][0] ) {
			print 'Unexpected key in tokens set: ';
			print_r( $arg_tokens );
			continue;
		}
		$key = trim( $arg_tokens[0][1], '"\'' );
		if ( empty( $arg_tokens[1] ) || T_DOUBLE_ARROW !== $arg_tokens[1][0] ) {
			print 'Expected double arrow in tokens set: ';
			print_r( $arg_tokens );
			continue;
		}
		$value = join( '', array_map( function( $token ) {
			if ( is_array( $token ) ) {
				if ( T_CONSTANT_ENCAPSED_STRING === $token[0] ) {
					if ( '"' === substr( $token[1], 0, 1 ) ) {
						return str_replace( '\\"', '"', trim( $token[1], '"' ) );
					} else {
						return str_replace( "\\'", "'", trim( $token[1], "'" ) );
					}
				}
				return $token[1];
			} else {
				return $token;
			}
		}, array_slice( $arg_tokens, 2 ) ) );
		$parsed_array[ $key ] = $value;
	}
	return $parsed_array;
}

/**
 * Locate token sets.
 *
 * Look throughout the tokens provided and finds sets of tokens that start with
 * $head followed by an open parenthesis up until that open parenthesis is closed.
 *
 * @param string $head   Head token string.
 * @param array  $tokens Tokens.
 *
 * @return array Arrays of token arrays.
 */
function locate_token_sets( $head, $tokens ) {
	$token_sets = array();
	$len = count( $tokens );
	for ( $i = 0; $i < $len; $i += 1 ) {
		if ( ! is_array( $tokens[ $i ] ) || $head !== $tokens[ $i ][1] ) {
			continue;
		}

		$token_set = array();
		$paren_depth = 0;

		// Advance to the first open paren.
		do {
			$i += 1;
		} while ( $i < $len && '(' !== $tokens[ $i ] );
		$paren_depth += 1;
		$i += 1;
		if ( $i >= $len ) {
			break;
		}

		for ( $j = $i; $j < $len; $j += 1 ) {
			$token = $tokens[ $j ];
			if ( is_array( $token ) && $head === $token[1] ) {
				break;
			}

			if ( '(' === $token ) {
				$paren_depth += 1;
			} elseif ( ')' === $token ) {
				$paren_depth -= 1;
			}

			if ( 0 === $paren_depth ) {
				break;
			}

			// Skip tokens we don't want.
			if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_WHITESPACE ), true ) ) {
				continue;
			}

			$token_set[] = $token;
		}
		$token_sets[] = $token_set;
		$i = $j;
	} // End for().
	return $token_sets;
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
