

/**
 *	Validates HTML forms and converts HTML5-only attributes 'placeholder' and 'required' to HTML4 equivalents
 *	@author Garrett Sens, May 2015
 *	@param string [form_id] The id of the HTML form.
 *	@param function [invalid_form_callback] A function to be called if the form fails validation
 *	@param function [valid_form_callback] A function to be called if the form passes validation
 *
 *	@todo what about adding a Field class? Would that save me from having to iterate over the field elements multiple times?
 */
function Form( form_id, invalid_form_callback, valid_form_callback )
{
	var form, fields;

	if( typeof form_id === 'undefined' )
	{
		throw new Error( 'The form i.d. is a required parameter' );
	}

	if( form_id === null )
	{
		throw new Error( 'Null form i.d. argument.' );
	}

	if( typeof invalid_form_callback === 'function' )
	{
		this.invalid_form_callback = invalid_form_callback;
	}

	if( typeof valid_form_callback === 'function' )
	{
		this.valid_form_callback = valid_form_callback;
	}

	form	= document.getElementById( form_id );
	if( form === null )
	{
		throw new Error( 'No DOM element found with form i.d. ' + form_id );
	}

	this.valid	= false;
	this.form	= form;

	fields		= [];
	fields		= this.create_fields_array( form );
	
	/*
	 * convert HTML5 placeholders to HTML4 custom "form_placeholder" attributes
	 */
	fields		= this.convert_HTML5_attributes( fields );

	/*
	 * save fields as class property
	 */
	this.fields	= fields;

	/*
	 * validate form upon form submit
	 */
	this.add_submit_event();

	/*
	 * add on-the-fly validation to each form field
	 */
	this.add_on_fly_events();
}


/*
 * convert form's elements to array; remove submit button and others
 */
Form.prototype.create_fields_array = function( form )
{
	var fields, validation_fields, i, field;

	//fields	= form.elements.to_array();

	fields				= form.elements;
	validation_fields	= [];

	for( i = 0; i < fields.length; i++ )
	{
		field = fields[i];

		if( field.type === 'submit' || field.type === 'fieldset' )
		{
			continue;
		}
		
		validation_fields.push( field );
	}

	return validation_fields;
};


// converts HTML5 "placeholder" and "required" attributes to custom attributes "form_placeholder" and "form_required"
Form.prototype.convert_HTML5_attributes = function( fields )
{
	var fields_length, i, field, placeholder_value, required_value;

	if( typeof fields === 'undefined' )
	{
		throw new Error( 'The fields array is a required parameter' );
	}

	if( fields === null )
	{
		throw new Error( 'Null fields argument.' );
	}

	fields_length	= 0;
	fields_length	= fields.length;

	for( i = 0; i < fields_length; i++ )
	{
		field	= fields[i];

		if( field.type === 'submit' )
		{
			continue;
		}

		/*
		 * convert placeholder att.
		 */
		placeholder_value = '';
		if( typeof field.placeholder !== 'undefined' && field.placeholder !== null )
		{
			placeholder_value = field.placeholder;
		} else
		{
			placeholder_value = field.getAttribute( 'placeholder' );
		}

		if( placeholder_value !== null && placeholder_value.length > 0 )
		{
			field.removeAttribute( 'placeholder' );
			field.setAttribute( 'form_placeholder', placeholder_value );

			// don't override value already there (if user hits back-button after form submitted, their values should still be there without "placeholder" class
			if( field.value !== null && field.value.length === 0 )
			{
				field.value	= placeholder_value;
				field.add_class( 'placeholder' );
			}
		}


		/*
		 * convert required att.
		 */
		if( field.getAttribute( 'required' ) !== null )
		{
			field.removeAttribute( 'required' );
			field.setAttribute( 'form_required', '' );
		}

	}

	return fields;
};


Form.prototype.add_submit_event = function()
{
	var form_class, form_required, valid_form, fields, fields_length;

	form_class 	= this;
	valid_form	= false;

	this.form.add_event( 'submit', function( event )
	{
		form_class.submit_event	= event;

		fields			= form_class.fields;
		fields_length	= fields.length;

		valid_form		= form_class.validate_form();
		form_class.valid= valid_form;

		if( valid_form )
		{
			form_class.remove_default_values();

			if( typeof form_class.valid_form_callback === 'function' )
			{
				form_class.valid_form_callback();
			}

			// form submits here
		} else
		{
			form_class.prevent_form_submission();

			// remove focus from current element so user has to click in to remove placeholder and "invalid" class
			document.activeElement.blur();

			if( typeof form_class.invalid_form_callback === 'function' )
			{
				form_class.invalid_form_callback();
			}
		}

		// temp
		//form_class.prevent_form_submission();
	} );
};


Form.prototype.add_on_fly_events = function()
{
	var fields, form_class, i, l;

	fields			= this.fields;
	form_class 		= this;

	for( i = 0, l = fields.length; i < l; i++ )
	{
		/*
		 * closure here fixes the variable reference problem (event listeners attach only to last element in array)
		 * http://stackoverflow.com/questions/19586137/addeventlistener-using-for-loop-and-passing-values
		 */
		(function()
		{
			var field;

			field = fields[i];

			add_event( field, 'blur', function( event )
			{
				form_class.validate_field( field );
			} );

			add_event( field, 'focus', function( event )
			{
				form_class.reset( field );
			} );

			/*
			 * For dropdowns, we use both 'onchange' and 'onblur' events because
			 * onchange works best for selects, except when the user clicks on
			 * the button and clicks out without changing the value, then you
			 * want to use 'onblur'
			 */
			if( field.tagName === 'SELECT' )
			{
				add_event( field, 'change', function( event )
				{
					form_class.validate_field( field );
				} );
			}
		}());
	}
};


// tests whether a form is ready to be submitted
Form.prototype.validate_form = function()
{
	var fields, invalid_count, i, l, valid;

	fields			= this.fields;
	invalid_count	= 0;

	// call validate_field() on every field
	for( i = 0, l = fields.length; i < l; i++ )
	{
		field		= fields[i];
		
		valid		= this.validate_field( field );
		//console.log( field.id + ': ' + valid );
		
		if( !valid )
		{
			invalid_count++;
		}
	}

	if ( invalid_count !== 0 )
	{
		return false;
	}

	return true;
};


// tests if a field is ready to be submitted
Form.prototype.validate_field = function( field )
{
	var standard_validation_result;

	if( typeof field === 'undefined' )
	{
		throw new Error( 'The field object is a required parameter' );
	}

	if( field === null )
	{
		throw new Error( 'Null field argument.' );
	}

	//console.log( "\n" + 'validating ' + field.id );

	standard_validation_result = this.standard_validation( field );
	if( standard_validation_result !== null )
	{
		return standard_validation_result;
	}

	return this.custom_validation( field );
};


// performs standard checks on form fields (replace in child class)
Form.prototype.standard_validation = function( field )
{
	var form_placeholder, is_required;

	form_placeholder 	= '';
	form_placeholder	= field.getAttribute( 'form_placeholder' );

	is_required			= false;
	is_required			= ( field.getAttribute( 'form_required' ) !== null );

	// skip non-required fields
	if( !is_required )
	{
		// if blank, restore placeholder
		if( this.is_blank_field( field ) )
		{
			if( form_placeholder !== null )
			{
				field.add_class( 'placeholder' );
				field.value = form_placeholder;
			}
		}
		return true;
	}

	// skip fields that have already been validated; select inputs are excluded because they are validated on 'change'
	if( field.tagName !== 'SELECT' && field.has_class( 'invalid' ) )
	{
		// restore placeholder
		if( this.is_blank_field( field ) )
		{
			if( form_placeholder !== null )
			{
				field.add_class( 'placeholder' );
				field.value = form_placeholder;
			}
		}
		return false;
	}

	/*
	 * if field is required and has no value, return boolean (false by default but can be overwritten by child class)
	 * or if field still has default value when form submitted
	 */
	if( this.is_blank_field( field ) )
	{
		field.add_class( 'invalid' );
		if( form_placeholder !== null )
		{
			field.add_class( 'placeholder' );
			field.value = form_placeholder;
		}
		
		return false;
	}

	return null;
};


// performs additional checks on form fields (replace in child class)
Form.prototype.custom_validation = function( field )
{
	return true;
};


// tests whether a field has no value
Form.prototype.is_blank_field = function( field )
{
	var value, no_space_value;

	if( typeof field === 'undefined' )
	{
		throw new Error( 'The field object is a required parameter' );
	}

	if( field === null )
	{
		throw new Error( 'Null field argument.' );
	}

	value	= field.value;

	if( value === null
	 || value.length <= 0
	 || value === field.getAttribute( 'form_placeholder' )
	 || value.replace( /\s+/g, '' ).length <= 0 )
	{
		return true;
	}

	return false;
};


// temporarily removes placeholder and styling for invalid fields
Form.prototype.reset = function( field )
{
	var form_placeholder, value;

	if( typeof field === 'undefined' )
	{
		throw new Error( 'The field object is a required parameter' );
	}

	if( field === null )
	{
		throw new Error( 'Null field argument.' );
	}

	field.remove_class( 'invalid' );

	form_placeholder = field.getAttribute( 'form_placeholder' );
	if( form_placeholder !== null && form_placeholder.length > 0 )
	{
		field.remove_class( 'placeholder' );
		value	= field.value;
		if( value === form_placeholder )
		{
			field.value = '';
		}
	}
};


Form.prototype.prevent_form_submission = function()
{
	var e = this.submit_event;

	if( typeof e === 'undefined' )
	{
		throw new Error( 'The event object is a required parameter' );
	}

	/*
	 *	DON'T SEND FORM
	 *	Because we used 'addEventListener' and 'addEvent', we have to
	 *	do the following instead of simply returning false.
	 */
	// modern browsers
	if ( e.preventDefault )
	{
		e.preventDefault();
	// IE < 9
	} else
	{
		e.returnValue = false;
	}
};


Form.prototype.remove_default_values = function()
{
	var fields, fields_length, i, field;

	fields	= this.fields;
	fields_length	= fields.length;

	// reset values of fields with default values so they're not added to the database
	for( i = 0; i < fields_length; i++ )
	{
		field	= fields[i];
		if( field.getAttribute( 'form_placeholder' ) !== null && field.value === field.getAttribute( 'form_placeholder' ) )
		{
			field.value	= ''; // "field.value = null;" didn't work on iPhone
		}
	}
};