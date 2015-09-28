<?php

/*
 * This is a collection of global php functions I've written to assist me in my web development work.
 * I typically rely on many more global php functions, but many of them were authored by others.
 */


/**
 * Starts a new session if one does not exist.
 * Optionally preserves session i.d., passed as a url parameter 'sid', in case cookies have been disabled.
 */
function start_session()
{
	// if session does not exist
	if( session_id() === '' )
	{
		// if url contains 'sid' parameter
		if( isset( $_GET[ 'sid' ] ) && strlen( $_GET[ 'sid' ] ) > 0 ) {
			// preserve session i.d.
			session_id( $_GET[ 'sid' ] );
		}
		session_start();
	}
}


/**
 * Sanitizes data and optionally trims strings.
 *
 * All form data or any data coming from the client should be sanitized and escaped before storage or outputting to the client.
 * PHP's htmlspecialchars function prevents cross-side-scripting (XSS) by converting special characters, such as the opening and
 * closing carats in the <script> tag, to HTML entities.
 *
 * @param mixed [$data] What you want to sanitize
 * @param boolean [$trim_strings] Whether or not the function should trim strings found in $data
 * @return mixed sanitized $data
 */
function scrub( $data, $trim_strings = false )
{
	// base case
	if( !isset( $data ) )
	{
		return $data;
	}
	// base case
	else if( is_string( $data ) )
	{
		if( $trim_strings )
		{
			$data = trim( $data );
		}
		return htmlspecialchars( $data );
	}
	// recursion
	else if( is_array( $data ) )	// is_array: slow, but readable
	{
		$keys = array_keys( $data );
		for( $i = 0, $l = count( $keys ); $i < $l; $i++ )	// for loop: faster than foreach
		{
			$data[ $keys[ $i ] ] = scrub( $data[ $keys[ $i ] ], $trim_strings );
		}
		return $data;
	}
	// recursion
	else if( is_object( $data ) )
	{
		foreach( $data as $property => $value )	// foreach: easiest way to iterate over objects in PHP
		{
			$data->$property = scrub( $value, $trim_strings );
		}
	}
	// other, e.g., boolean, number
	return $data;
}

?>