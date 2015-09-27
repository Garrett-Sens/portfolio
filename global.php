<?php

/*
 * GLOBAL.PHP
 *
 * A collection of useful PHP functions I've collected and written
 */


/*
 * source: http://stackoverflow.com/questions/1200214/how-can-i-measure-the-speed-of-code-written-in-php
 */
function time_function( $fn, $args, $iterations = 200000 )
{
	$before = microtime( true );
	for( $i = 0; $i < $iterations; $i++ ) {
		call_user_func_array( $fn, $args );
	}
	$after = microtime( true );

	return( ( $after - $before )/$iterations . " sec/serialize\n" );
}


/* returns true if $string begins with $substring
 * source: andy@onesandzeros.biz
 */
function begins_with( $string, $substring )
{
	return ( substr( $string, 0, strlen( $substring ) ) == $substring );
}

/*
 * return true if $string ends with $substring
 * source: andy@onesandzeros.biz
 */
function ends_with( $string, $substring )
{
	return ( substr( $string, strlen( $string ) - strlen( $substring ) ) == $substring );
}


function is_null_or_empty_string( $value )
{
	return ( !isset( $value ) || $value === null || strlen( $value ) === 0 );
}


// needed for empty_array function below
function not_null_or_empty_string( $value )
{
	return !is_null_or_empty( $value );
}


function empty_array( $array )
{
	return ( count( array_filter( $array, 'not_null_or_empty_string' ) ) === 0 );
}

/*
 * returns true if $array is an associative array (contains string keys)
 * source: http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential/4254008#4254008
 */
function is_assoc( $array )
{
	return !empty( $array ) && is_array( $array ) && ( bool )count( array_filter( array_keys( $array ), 'is_string' ) );
}


// prints php variable as html
function print_h( $var )
{
	echo '<pre>';
	print_r( $var );
	echo  '</pre>';
}


// starts a new session if necessary; preserves session i.d. if cookies disabled
function start_session()
{
	if( session_id() === '' )
	{
		if( isset( $_GET[ 'sid' ] ) ) {	// the session i.d. is normally sent as a cookie, but if cookies are disabled then we have to send it through URL param
			session_id( $_GET[ 'sid' ] );
		}
		session_start();
	}
}


// source: http://stackoverflow.com/questions/1175096/how-to-find-out-if-you-are-using-https-without-serverhttps
function is_https()
{
	return ( !empty($_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
}


/**
 *	sanitizes data in order to prevent Cross-Side-Scripting (XSS)
 *
 *	All form data or any data coming from the client should be sanitized
 *	and escaped before storage or outputting to the client
 *
 *	@param string/array of strings [$data]
 *	@return the same string/array trimmed and with special characters
 */
function scrub( $data )
{
	// base case
	if( is_string( $data ) )
	{
		return htmlspecialchars( trim( $data ) );
	}
	// recursion
	else if( is_array( $data ) )
	{
		foreach( $data as $key => $value )
		{
			$data[ $key ] = scrub( $value );
		}

		return $data;
	}
	else
	{
		return $data;
	}
}


/**
 *	converts multiline string to a singleline string with newline chars instead
 */
function to_single_line( $multiline_string, $html = false, $trim_extra_newlines = false )
{
	//$multiline_string	= json_encode( $multiline_string );		// converts "\r\n" to '\r\n'
	//$multiline_string	= trim( $multiline_string, "'\"" );		// remove extra set of quotes added by json_encode
	$multiline_string	= str_replace( "\r\n", '\r\n', $multiline_string );
	$multiline_string	= str_replace( "\n", '\n', $multiline_string );
	
	if( $trim_extra_newlines )
	{
		$multiline_string	= trim( $multiline_string, '\r\n' );	// remove leading or trailing newline chars
		$multiline_string	= trim( $multiline_string, '\n' );	// remove leading or trailing newline chars
	}

	if( $html )
	{
		$multiline_string	= preg_replace( "/(\\\\r)?\\\\n/", '<br/>', $multiline_string );	/* convert newline chars to html breaks */
	}

	return $multiline_string;
}


/**
 * converts a string of xml to an associative array
 *
 * source: ratfactor@gmail.com's comment in http://php.net/manual/en/class.simplexmliterator.php
 * original:
 * function xml2array($fname){
 *  $sxi = new SimpleXmlIterator($fname, null, true);
 *  return sxiToArray($sxi);
 * }
 *
 * function sxiToArray($sxi){
 *  $a = array();
 *  for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {
 *    if(!array_key_exists($sxi->key(), $a)){
 *      $a[$sxi->key()] = array();
 *    }
 *    if($sxi->hasChildren()){
 *      $a[$sxi->key()][] = sxiToArray($sxi->current());
 *    }
 *    else{
 *      $a[$sxi->key()][] = strval($sxi->current());
 *    }
 *  }
 *  return $a;
 * }
 *
 * @param string [$data] "A well-formed XML string or the path or URL to an XML document if data_is_url is TRUE." (http://php.net/manual/en/simplexmlelement.construct.php)
 * @param boolean [$data_is_url] "By default, data_is_url is FALSE. Use TRUE to specify that data is a path or URL to an XML document instead of string data."
 */
function xml_to_array( $data, $data_is_url = false )
{
	$sxi	= new SimpleXmlIterator( $data, null, $data_is_url );

	// include name of top level
	$xml_array	= array();
	$xml_array	= array(
		$sxi->getName()	=> sxi_to_array( $sxi )
	);
	return $xml_array;
}

function sxi_to_array( $sxi )
{
	/*
	 * create parent array
	 */
	$xml_array	= array();

	// add parent's attributes
	$attributes	= $sxi->attributes();
	if( $attributes )
	{
		$attributes_array	= array();
		foreach( $attributes as $key => $value )
		{
			$attributes_array[ strval( $key ) ]	= strval( $value );
		}

		$xml_array[ '@attributes' ]	= $attributes_array;
	}

	/*
	 * iterate over children
	 */
	for( $sxi->rewind(); $sxi->valid(); $sxi->next() )
	{
		$xml_key	= $sxi->key();

		// create child level
		if( !array_key_exists( $xml_key, $xml_array ) )
		{
			$xml_array[ $xml_key ] = array();
		}

		/*
		 * add children to $xml_array
		 */
		$current	= $sxi->current();
		// using indexed sub-arrays here allows the array to store xml records with duplicate keys
		if( $sxi->hasChildren() )
		{
			$xml_array[ $xml_key ][]	= sxi_to_array( $current );
		} else
		{
			// get attributes
			$attributes			= $current->attributes();
			$attributes_array	= array();
			if( $attributes )
			{
				foreach( $attributes as $key => $value )
				{
					$attributes_array[ strval( $key ) ]	= strval( $value );
				}

				$xml_array[ $xml_key ][]	= array(
					'@value'		=> strval( $current ),
					'@attributes'	=> $attributes_array
				);
			}
			else
			{
				$xml_array[ $xml_key ][]	= array(
					'@value'	=> strval( $current )
				);
			}
		}
	}

	return $xml_array;
}

/**
 * rounds then converts ints or floats to U.S. dollar format
 * @param int/float [$number]
 * @param bool [$format_options] Optional.
 * If '$', adds dollar sign to front.
 * If ',', adds commas between thousands
 * If '$,', adds both
 * @return float or string (with $options )
 */
function USD_format( $num, $format_options = '' )
{
	$number	= (float) 0;
	$number	= (float) round( $num, 2 );

	if( strlen( $format_options ) > 0 )
	{
		switch( $format_options )
		{
			case '$,':
				return '$' . print_r( number_format( $number, 2, '.', ',' ), true );
				break;
			case ',$':
				return '$' . print_r( number_format( $number, 2, '.', ',' ), true );
				break;
			case '$':
				if( function_exists( 'money_format' ) )
				{
					setlocale( LC_MONETARY, 'en_US.UTF-8' );
					return money_format( '%.2n', $number ); // string
				} else
				{
					return '$' . print_r( number_format( $number, 2, '.', '' ), true );
				}
				break;
			case ',':
				return print_r( number_format( $number, 2, '.', ',' ), true );
		}
	} else
	{
		return (float) number_format( $number, 2, '.', '' );
	}
}


function is_valid_cc_num( $cc_num_string )
{
	// how come I'm not doing anything with this information?
	$cc_num_info = array(
		'Visa'				=> '/^(4)/',
		'MasterCard'		=> '/^(51|52|53|54|55)/',
		'Discover'			=> '^(6011)/',
		'American Express'	=> '/^3[47][0-9]{13}$/'
	);

	// remove non-numbers
	$cc_num_string = preg_replace( '[^0-9]', '', $cc_num_string );

	return is_valid_luhn( $cc_num_string );
}


/*
 *	tests whether credit card number is valid (not necessarily an actual, working number)
 *	See http://en.wikipedia.org/wiki/Luhn_algorithm
 *
 *	source: "troelskn", https://gist.github.com/troelskn/1287893
 */
function is_valid_luhn( $cc_num_string )
{
	$sumTable = array(
		array( 0,1,2,3,4,5,6,7,8,9 ),
		array( 0,2,4,6,8,1,3,5,7,9 )
	);

	$sum = 0;
	$flip = 0;

	for( $i = strlen( $cc_num_string ) - 1; $i >= 0; $i-- )
	{
		$sum += $sumTable[ $flip++ & 0x1 ][ $cc_num_string[ $i ] ];
	}

	return ( $sum % 10 === 0 );
}


/*
 * slower
 * source: https://gist.github.com/troelskn/1287893
 */
function is_valid_luhn2( $num )
{
	$sum	= '';

	for( $i = strlen( $num ) - 1; $i >= 0; --$i )
	{
		$sum .= $i & 1 ? $num[$i] : $num[$i] * 2;
	}

	return array_sum( str_split( $sum ) ) % 10 === 0;
}


/*
 * converts assoc. array of html form field data to array of form field html
 */
function fields_data_to_html( $fields_data )
{
	$form_fields_html_array	= array();

	foreach( $fields_data as $name => $data )
	{
		$form_fields_html_array[ $name ] = field_data_to_html( $data );
	}

	return $form_fields_html_array;
}


/*
 * converts html form field data array to form field html string
 */
function field_data_to_html( $field_data )
{
	$string_builder	= '';
	$string_end		= '';

	switch( $field_data[ 'type' ] )
	{
		case 'text':
			unset( $field_data[ 'type' ] );	// unset it so it doesn't get added as an attribute again below
			$string_builder	.= "<input type='text'";
			$string_end		= ">\n";
			break;

		case 'submit':
			unset( $field_data[ 'type' ] );
			$string_builder	.= "<input type='submit'";
			$string_end		= ">\n";
			break;

		case 'checkbox':
			unset( $field_data[ 'type' ] );
			$string_builder	.= "<input type='checkbox'";
			$string_end		= ">\n";
			break;

		case 'textarea':
			unset( $field_data[ 'type' ] );
			$string_builder	.= "<textarea";
			if( isset( $field_data[ 'value' ] ) )
			{
				$inner_html		= preg_replace( "/(\\\\r)?\\\\n/", "\n", $field_data[ 'value' ] ); // four backslashes because both regex and PHP use \ as an escape character http://stackoverflow.com/questions/20818283/how-to-properly-escape-a-backslash-to-match-a-literal-backslash-in-single-quoted
				$string_end		= ">" . $inner_html . "</textarea>\n";
				unset( $field_data[ 'value' ] );
			} else
			{
				$string_end		= "></textarea>\n";
			}
			break;

		case 'select':
			unset( $field_data[ 'type' ] );
			$string_builder	.= "<select";
			$string_end		= ">\n";
			foreach( $field_data[ 'options' ] as $option_data )
			{
				$string_end	.= field_data_to_html( $option_data );
			}
			unset( $field_data[ 'options' ] );
			$string_end		.= "</select>\n";
			break;

		case 'option':
			unset( $field_data[ 'type' ] );
			$string_builder	.= "<option";
			$string_end		= '>' . $field_data[ 'inner_html' ] . "</option>\n";
			unset( $field_data[ 'inner_html' ] );
			break;

		default:
			throw new Exception( 'Invalid field type "' . $field_data[ 'type' ] . '".' );
	}

	foreach( $field_data as $attribute => $value )
	{
		if( $value === null )
		{
			$string_builder	.= " $attribute";
		} else
		{
			if( is_bool( $value ) )
			{
				$value = $value ? 'true' : 'false';
			}
			$string_builder	.= " $attribute='$value'";
		}
	}
	$string_builder	.= $string_end;

	return $string_builder;
}


function write_to_file( $location, $data_string )
{
	$file = fopen( $location, 'w' );
	if( $file === false )
	{
		throw new RuntimeException( 'Unable to open file for writing.' );
	}

	// add server-specific newline character at end of data stream if there isn't one
	if( preg_match( "/(\r)?\n/", substr( $data_string, -1 ) ) === 0 )
	{
		$data_string .= PHP_EOL;
	}

	$bytes = fwrite( $file, $data_string );
	if( $bytes === false )
	{
		throw new RuntimeException( 'Unable to write to file.' );
	}

	fclose( $file );

	return $bytes;
}


?>