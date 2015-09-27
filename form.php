<?php

/**
 * Validates POST data from HTML forms
 * 
 * A subclass to which is passed the form's fields' attributes 
 * can offer more specific validation.
 */
require_once( CORE_DOC_ROOT . 'php/global.php' );
start_session();

class Form_Validator
{
	public $valid	= false;
	public $result	= null;
	
	public function __construct()
	{
		$form_data	= $this->get_form_data();

		if( $form_data === null )
		{
			return;
		}

		$validation_return	= $this->validate_form( $form_data );
		$this->valid		= $validation_return[0];
		$this->result		= $validation_return[1];
	}

	protected function get_form_data()
	{
		if( $_POST === null || count( $_POST ) === 0 )
		{
			return null;
		}
		
		/*
		 * undo PHP's automatic field-name-to-array grouping
		 * see: http://www.php.net/manual/en/faq.html.php#faq.html.arrays
		 */
		$one_d_form_data = array();
		foreach( $_POST as $name => $value )
		{
			if( is_array( $value ) )
			{
				foreach( $value as $n => $v )
				{
					$one_d_form_data[ $name . '[' . $n . ']' ]	= $v;
				}
			} else
			{
				$one_d_form_data[ $name ]	= $value;
			}
		}

		return $one_d_form_data;
	}


	/*
	 * note: Why do we validate form data in both javascript and PHP?
	 * 1. Javascript validation is for the convenience of the user. It informs users and limits page 
	 *    requests by disabling form submission when the form data is invalid. It is not secure because
	 *    "the user can see the JS, and, by saving the page and amending the JS do whatever they want". 
	 * 2. "All user-submitted form data should be validated and sanity-checked on the server-side before storing."
	 * source: http://stackoverflow.com/questions/1726617/form-validation-with-javascript-vs-php
	 */
	protected function validate_form( $form_data )
	{
		$is_valid_field	= false;
		$error_count	= 0;
		$result			= array();

		// build result array from form data and validity values
		foreach( $form_data as $name => $value )
		{
			$is_valid_field	= $this->validate_field( $name, $value );
			$error_num		= 0 + !$is_valid_field; // int coercion (faster than casting)
			$error_count   += $error_num;

			$field_data				= array();
			$field_data[ 'value' ]	= $value;
			$field_data[ 'valid' ]	= $is_valid_field;
			$result[ $name ]		= $field_data;
		}

		return array( ( $error_count === 0 ), $result );
	}


	protected function validate_field( $name, $value )
	{
		return !$this->is_blank( $name, $value );
	}
	

	protected function is_blank( $value )
	{
		if( $value === null
		 || strlen( $value ) <= 0
		 || strlen( preg_replace( '/\s+/', '', $value ) ) <= 0 )
		{
			return true;
		}
		return false;
	}

}

?>