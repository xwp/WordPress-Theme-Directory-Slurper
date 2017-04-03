<?php

namespace WordPress_Theme_Directory_Slurper\Feature_Stats;

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
