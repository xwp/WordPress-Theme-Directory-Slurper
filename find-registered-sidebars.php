#!/usr/bin/env php
<?php

chdir( __DIR__ . '/themes' );

$theme_slugs = glob( '*', GLOB_ONLYDIR );

foreach ( $theme_slugs as $theme_slug ) {
	echo "## $theme_slug\n";
	$parsed_sidebars_file = sprintf( '%s-registered-sidebars.json', $theme_slug );
	if ( file_exists( $parsed_sidebars_file ) ) {
		continue;
	}

	$directory = new RecursiveDirectoryIterator( $theme_slug );
	$iterator = new RegexIterator( new RecursiveIteratorIterator( $directory ), '/^.+\.php$/i', RecursiveRegexIterator::MATCH );
	$theme_sidebars = array();

	foreach ( $iterator as $php_file ) {
		$tokens = token_get_all( file_get_contents( $php_file->getPathname() ) );

		$sidebar_arg_token_sets = array();
		foreach ( locate_token_sets( 'register_sidebar', $tokens ) as $register_sidebar_call_tokens ) {
			$sidebar_arg_token_sets = array_merge(
				$sidebar_arg_token_sets,
				locate_token_sets( 'array', $register_sidebar_call_tokens )
			);
		}
		foreach ( locate_token_sets( 'register_sidebars', $tokens ) as $register_sidebar_call_tokens ) {
			$sidebar_arg_token_sets = array_merge(
				$sidebar_arg_token_sets,
				locate_token_sets( 'array', $register_sidebar_call_tokens )
			);
		}

		$sidebar_args_tokens = array();
		$current_item = 0;
		foreach ( $sidebar_arg_token_sets as $sidebar_arg_token_set ) {
			$paren_depth = 0;
			$args_tokens = array();
			for ( $i = 0; $i < count( $sidebar_arg_token_set ); $i += 1 ) {
				$token = $sidebar_arg_token_set[ $i ];
				if ( '(' === $token ) {
					$paren_depth += 1;
				} elseif ( ')' === $token ) {
					$paren_depth -= 1;
				} elseif ( ',' === $token && 0 === $paren_depth ) {
					$current_item += 1;
					continue;
				}

				$sidebar_args_tokens[ $current_item ][] = $token;
			}
		}

		if ( empty( $sidebar_args_tokens ) ) {
			continue;
		}

		$sidebar_args = array(
			'name' => sprintf( 'Sidebar %d', count( $theme_sidebars ) + 1 ),
			'id' => sprintf( 'sidebar-%d', count( $theme_sidebars ) + 1 ),
		);
		foreach ( $sidebar_args_tokens as $sidebar_args_tokens_set ) {

			if ( empty( $sidebar_args_tokens_set[0] ) || T_CONSTANT_ENCAPSED_STRING !== $sidebar_args_tokens_set[0][0] ) {
				print 'Unexpected key in tokens set: ';
				print_r( $sidebar_args_tokens_set );
				continue;
			}
			$key = trim( $sidebar_args_tokens_set[0][1], '"\'' );
			if ( empty( $sidebar_args_tokens_set[1] ) || T_DOUBLE_ARROW !== $sidebar_args_tokens_set[1][0] ) {
				print 'Expected double arrow in tokens set: ';
				print_r( $sidebar_args_tokens_set );
				continue;
			}
			$value = join( '', array_map( function( $token ) {
				if ( is_array( $token ) ) {
					return $token[1];
				} else {
					return $token;
				}
			}, array_slice( $sidebar_args_tokens_set, 2 ) ) );

			$sidebar_args[ $key ] = $value;
		}

		$sidebar_args['id'] = trim( $sidebar_args['id'], '"\'' );
		$theme_sidebars[] = $sidebar_args;
	}
	file_put_contents( $parsed_sidebars_file, json_encode( $theme_sidebars, JSON_PRETTY_PRINT ) );
}

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
	}
	return $token_sets;
}


