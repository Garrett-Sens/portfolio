/*
 * GLOBAL.JS
 *
 * A collection of useful JavaScript functions I've collected and written
 */

// useful for testing the speed of a given function
var time_function = function( fn, args, this_value, iterations )
{
	var start, end, i;

	if( typeof fn !== 'function' )
	{
		throw new Error( "First argument must be function" );
	}

	if( typeof iterations === 'undefined' )
	{
		iterations = 100000;
	}

	start = 0;
	end   = 0;

	start = window.performance.now(); // in milliseconds
	for( i = 0; i < iterations; i++ )
	{
		fn.apply( this_value, args );
	}
	end = window.performance.now();

	return ms_to_string( end - start );
};


if( typeof Function.prototype.time !== 'function' )
{
	Function.prototype.time = function( args, this_value, iterations ){ return time_function( this, args, this_value, iterations ); };
}


var pad_with_zero = function( num )
{
	return ( num < 10 ) ? ( "0" + num ) : num;
}


// convert number of milliseconds to human-readable format
var ms_to_string = function( milliseconds )
{
	var date, return_string, d, h, m, s, ms;

	date = new Date( milliseconds );
	return_string = '';
	d = date.getUTCDate() - 1;
	h = date.getUTCHours();
	m = date.getUTCMinutes();
	s = date.getUTCSeconds();
	ms = date.getUTCMilliseconds();

	if( d > 0 )
	{
		return_string += d + ' days, ';
	}

	if( h > 0 )
	{
		return_string += h + ' hours, ';
	}

	if( m > 0 )
	{
		return_string += m + ' minutes, ';
	}

	return_string += pad_with_zero( s ) + ':' + pad_with_zero( ms ).toString().substr( 0, 2 ) + ' seconds';

	return return_string;
};


var get_type = function( obj )
{
	var obj_string, match;

	if( obj === null )
	{
		return 'undefined';
	}

	if( typeof obj === 'undefined' )
	{
		return 'undefined';
	}
	
	obj_string = Object.prototype.toString.call( obj );

	//return obj.toString().match( /^\[object (\w+)\]$/ )[1];
	match = obj_string.match(/\[(function|object)\s+(\S+)\]/);
	if( match === false )
	{
		return 'undefined';
	}

	return match[2].toLowerCase();
};


var is_type = function( obj, type )
{
	//return ( new RegExp( '^\\[(function|object)\\s+' + type + '\\]$' ) ).test( obj.toString() );
	return ( get_type( obj ) === type.toLowerCase() );
};


// add string.trim() to IE < 9
if( typeof String.prototype.trim !== 'function' )
{
	String.prototype.trim = function()
	{
		return this.replace( /^\s+|\s+$/g, '' );
	};
}

// add function.bind() to IE < 9
// source: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind
if( typeof Function.prototype.bind !== 'function' )
{
	Function.prototype.bind = function( oThis )
	{
		if ( typeof this !== 'function' )
		{
			// closest thing possible to the ECMAScript 5
			// internal IsCallable function
			throw new TypeError( 'Function.prototype.bind - what is trying to be bound is not callable' );
		}

		var aArgs   = Array.prototype.slice.call( arguments, 1 ),
			fToBind = this,
			fNOP = function() {},
			fBound = function()
			{
				return fToBind.apply(
					this instanceof fNOP && oThis ? this : oThis, aArgs.concat( Array.prototype.slice.call( arguments ) )
				);
			};

		fNOP.prototype = this.prototype;
		fBound.prototype = new fNOP();

		return fBound;
	};
}


var get_random_string = function( length, char_set )
{
	var char_set_length, random_string, i;
	
	if( typeof length === 'undefined' )
	{
		length = 16;
	}

	if( typeof char_set === 'undefined' )
	{
		char_set = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	}

	char_set_length	= 0;
	char_set_length		= char_set.length;

	random_string	= '';
	for( i = 0; i < length; i++ )
	{
    	random_string += char_set.charAt( Math.floor( Math.random() * char_set_length ) );
	}

	return random_string;
};


/*
 * source http://stackoverflow.com/questions/2735067/how-to-convert-a-dom-node-list-to-an-array-in-javascript
 */
var obj_to_array = function( obj )
{
	var array, i;

	array = [];
	/*
	 * crazy for-loop explanation:
	 * 	i = obj.length because we are iterating backwords
	 * 	obj.length >>> 0 forces i to be an unsigned integer of 32 bits (UInt32) See https://msdn.microsoft.com/en-us/library/342xfs5s(v=vs.94).aspx
	 *	i-- when i === 0, stops the loop; also decrements i
	 * 	; ) no need for third part of the loop creation param
	 */
	for( i = obj.length >>> 0; i--; )
	{
		array[i] = obj[i];
	}
	return array;

	// slower method
	//return Array.prototype.slice.call( obj );
};


/*
 * Extend HTMLCollection with to_array fn
 * note: extending DOM objects works in IE > 7
 */
if( typeof HTMLCollection.prototype.to_array !== 'function' )
{
	HTMLCollection.prototype.to_array = function(){ return obj_to_array( this ); };
}

/*
 * Extend NodeList with to_array fn (some browsers use HTMLCollection; others use NodeList)
 * note: extending DOM objects works in IE > 7
 */
if( typeof NodeList.prototype.to_array !== 'function' )
{
	NodeList.prototype.to_array = function(){ return obj_to_array( this ); };
}


/*
 * tests whether an array contains every element from another array
 * source: http://stackoverflow.com/questions/8628059/check-if-every-element-in-one-array-is-in-a-second-array
 */
var array_contains_array = function( big_array, small_array )
{
	var i, j, l, m;
	big_array.sort();
	small_array.sort();

	for( i = 0, j = 0, l = big_array.length, m = small_array.length; i < l && j < m; )
	{
		if( big_array[i] < small_array[j] )
		{
			i++;
		} else if( big_array[i] === small_array[j] )
		{
			i++;
			j++;
		} else
		{
			// small_array[j] not in big_array
			return false;
		}
	}

	// make sure there are no elements left in small_array
	return j === small_array.length;
};


/*
 * Extend Array with contains_array fn
 * note: extending DOM objects works in IE > 7
 */
if( Array.prototype.contains_array !== 'function' )
{
	Array.prototype.contains_array = function( small_array ){ return array_contains_array( this, small_array ); };
}


/*
 * returns true if object "obj" is a DOM Node
 * source:http://stackoverflow.com/questions/384286/javascript-isdom-how-do-you-check-if-a-javascript-object-is-a-dom-object
 */
var is_node = function( obj )
{
	return (
		typeof Node === "object" ? obj instanceof Node :
		obj && typeof obj === "object" && typeof obj.nodeType === "number" && typeof obj.nodeName === "string"
	);
};


/*
 * returns true if object "obj" is a DOM Element
 * source:http://stackoverflow.com/questions/384286/javascript-isdom-how-do-you-check-if-a-javascript-object-is-a-dom-object
 */
var is_element = function( obj )
{
	return (
		typeof HTMLElement === "object" ? obj instanceof HTMLElement : //DOM2
		obj && typeof obj === "object" && obj !== null && obj.nodeType === 1 && typeof obj.nodeName === "string"
	);
};


// Chrome calls it "NodeList"; Firefox and others call it "HTMLCollection"; IE8 calls it "Object"
// source: user Tomalak at http://stackoverflow.com/questions/7238177/detect-htmlcollection-nodelist-in-javascript
var is_node_list = function( nodes )
{
	var stringRepr;

	// get toString() for this object
	stringRepr = Object.prototype.toString.call( nodes );

	// if IE < 9
	if( !window.addEventListener ) { // this is a hack, I know

		return nodes.isObject && // (IE < 9 don't support "typeof")
			/^\[object (HTMLCollection|NodeList|Object)\]$/.test( stringRepr ) &&
			nodes.hasOwnProperty('length') &&
			( nodes.length === 0 || ( typeof nodes[0] === "object" && nodes[0].nodeType > 0 ) );
	} else
	{
		return typeof nodes === 'object' &&
		/^\[object (HTMLCollection|NodeList|Object)\]$/.test( stringRepr ) &&
		nodes.hasOwnProperty('length') &&
		( nodes.length === 0 || ( typeof nodes[0] === "object" && nodes[0].nodeType > 0 ) );
	}
};

/*
 * Class Functions
 */

/*
 * note: the reason I don't do a simple
 *	 return element.className.indexOf( class_name ); // may return false positive if element has class with class_name as substring
 * is that I don't want to have false positives for classes
 * that happen to contain the class name I'm looking for
 */
/*
var has_class = function( element, class_name )
{
	var current_class	= '';
	current_class		= element.className;

	if( current_class === null || get_type( current_class ) === 'undefined' || current_class.trim().length < 1 )
	{
		return false;
	}

	var class_array			= [];
	class_array				= current_class.split( ' ' );

	var class_array_length	= 0;
	class_array_length		= class_array.length;

	// iterate over class_array looking for argument class_name
	for( var i = 0; i < class_array_length; i++ )
	{
		var test_class	= '';
		test_class		= class_array[i];

		if( test_class === class_name )
		{
			return true;
		}
	}

	return false;
};
*/

var has_classes = function( element, class_string )
{
	var classes_array, element_classes_array;

	if( !is_element( element ) )
	{
		return false;
	}

	classes_array		= class_string.split( ' ' );
	element_classes_array = element.className.split( ' ' );

	return element_classes_array.contains_array( classes_array );
};


/*
 * Extend Node with has_class fn
 * note: extending DOM objects works in IE > 7
 */
if( Node.prototype.has_class !== 'function' )
{
	Node.prototype.has_class = function( class_string ){ return has_classes( this, class_string ); };
}


/*
 * Extend Node with has_classes fn
 * note: extending DOM objects works in IE > 7
 */
if( Node.prototype.has_classes !== 'function' )
{
	Node.prototype.has_classes = function( class_string ){ return has_classes( this, class_string ); };
}


var add_class = function( obj, new_class )
{
	var current_class	= '';
	current_class		= obj.className;

	if( current_class === null || current_class.trim().length < 1 )
	{
		obj.className = new_class;
	} else if( obj.has_class( new_class ) === false )
	{
		obj.className += ' ' + new_class;
	} else
	{
		return false;
	}

	return true;
};


/*
 * Extend Node with add_class fn
 * note: extending DOM objects works in IE > 7
 */
if( typeof Node.prototype.add_class !== 'function' )
{
	Node.prototype.add_class = function( new_class ){ return add_class( this, new_class ); };
}


var remove_class = function( DOM_element, class_name )
{
	var class_string, class_array, class_array_length, class_index, i, test_class;

	// get string of class names attached to Element
	class_string	= '';
	class_string	= DOM_element.className;

	if( class_string === null || class_string.length < 1 )
	{
		return false;
	}

	class_array	= [];
	class_array	= class_string.split( ' ' );

	class_array_length	= 0;
	class_array_length	= class_array.length;

	class_index	= -1;

	// iterate over class_array looking for argument class_name
	for( i = 0; i < class_array_length; i++ )
	{
		test_class	= '';
		test_class	= class_array[i];

		if( test_class === class_name )
		{
			class_index = i;
			break;
		}
	}

	// if the element had the needle class, remove it
	if( class_index >= 0 )
	{
		// remove argument class_name from class_array
		class_array.splice( class_index, 1 ); // remove 1 item starting at class_index; class_array's size is now one less

		// reassign leftover classes back to DOM_element
		DOM_element.className	= class_array.join( ' ' );

		return true;
	}

	return false;
};


/*
 * Extend Node with remove_class fn
 * note: extending DOM objects works in IE > 7
 */
if( typeof Node.prototype.remove_class !== 'function' )
{
	Node.prototype.remove_class = function( class_name ) { return remove_class( this, class_name ); };
}


/*
 * note: if you want a subset of elements with a class name, use Array.prototype.filter, e.g.,
 * 	var testElements = document.getElementsByClass('test');
 * 	var testDivs = Array.prototype.filter.call(testElements, function(testElement){
 * 		return testElement.nodeName === 'DIV';
 * 	});
 */
var getElementsByClass = function( class_string, parent_elem )
{
	var elems_with_class_name, elems_collection, elems_array, i, l, child_elem;

	if( typeof class_string === 'undefined' )
	{
		throw new Error( "First argument, 'class_string', is required" );
	}

	if( typeof parent_elem === 'undefined' || parent_elem === null  )
	{
		parent_elem	= document;
	}

	elems_with_class_name	= []; // .push method belongs to Array class so can't use array literal ([]) here
	// get all sub-elements under parent_elem element
	elems_collection		= parent_elem.getElementsByTagName( '*' );
	elems_array				= elems_collection.to_array();
	// getElementsByTagName doesn't include the object it's called on so we add it here
	Array.prototype.push.call( elems_array, parent_elem );

	if( elems_array === null )
	{
		return null;
	}

	for( i = 0, l = elems_array.length; i < l; i++ )
	{
		child_elem	= elems_array[i];
		if( child_elem.has_classes( class_string ) )
		{
			Array.prototype.push.call( elems_with_class_name, child_elem );
		}
	}

	return elems_with_class_name;
};


/*
 * Extend HTMLDocument (can't extend Node in IE8) with getElementsByClass fn
 * note: built-in Node.getElementsByClassName() works in IE > 8
 * note: extending DOM objects works in IE > 7, so I guess this extension is only for IE 8...
 */
if( typeof HTMLDocument.prototype.getElementsByClassName !== 'function' )
{
	HTMLDocument.prototype.getElementsByClassName = function( class_name ){ return getElementsByClass( class_name, this ); };
}


/*
 * Extend Node with getElementsByClass fn (custom version of getElementsByClassName fn)
 * note: extending DOM objects works in IE > 7
 */
if( typeof Node.prototype.getElementsByClass !== 'function' )
{
	Node.prototype.getElementsByClass = function( class_name ){ return getElementsByClass( class_name, this ); };
}


/*
 * retrieves certain DOM elements as defined by "callback" function using level-order tree traversal (https://en.wikipedia.org/wiki/Tree_traversal)
 */
var getElementsByFunction = function( start_object, callback, callback_args )
{
	var elements, element;

	// I think getElementsByTagName( '*' ) is faster...
	function traverse_DOM( callback, callback_args )
	{
		//console.log( this );

		if( this === window )
		{
			throw new Error( 'getElementsByFunction fn called on Window object; creates infinite loop' );
		}
		
		if( this.nodeType === 1 ) {	// skip non-HTMLElement nodes

			element = callback.call( this, callback_args );
			if( element !== null )
			{
				elements.push( element );
			}
		}

		if( this.nextSibling !== null )
		{
			traverse_DOM.call( this.nextSibling, callback, callback_args );
		}

		if( this.firstChild !== null )
		{
			traverse_DOM.call( this.firstChild, callback, callback_args );
		}
	}
	
	elements = [];
	traverse_DOM.call( start_object, callback, callback_args );
	return elements;
};


/*
 * Extend Node with getElementsByFunction fn
 * note: extending DOM objects works in IE > 7
 *
 * Example with callback arguments:
 * 	document.getElementById( 'my_div' ).traverse_elements(
 * 		function( args ){
 * 			console.log( this );
 * 			console.log( args );
 * 		},
 * 		[ 0, 1, 2 ]
 * 	);
 *
 * If you want the traversal to stop at a certain point, as in a search, return true from the callback function
 * when the conditions are met, e.g.,
 *
 *	var target = document.getElementById( 'my_div' ).traverse_elements(
 * 		function(){
 * 			if( this.has_class( 'foo' ) ){
 * 				return true;
 * 			}
 * 			return false;
 * 		}
 *	);
*/
if( typeof Node.prototype.getElementsByFunction !== 'function' )
{
	Node.prototype.getElementsByFunction = function( callback, callback_args ) { return getElementsByFunction( this, callback, callback_args ); };
}


/*
 * retrieves a single DOM element as defined by "callback" function using level-order tree traversal then stops
 */
function getElementByFunction( start_object, callback, callback_args )
{
	function traverse_DOM( callback, callback_args, done )
	{
		var callback_return, target;

		if( this === window )
		{
			throw new Error( 'getElementByFunction fn called on Window object; creates infinite loop' );
		}

		if( done === true )
		{
			return;
		}
		
		if( this.nodeType === 1 ) {	// skip non-HTMLElement nodes

			callback_return = callback.call( this, callback_args );
			if( callback_return === true )
			{
				done = true;
				return this;
			}
		}

		if( this.nextSibling !== null )
		{
			target = traverse_DOM.call( this.nextSibling, callback, callback_args, done );
			if( target !== null )
			{
				return target;
			}
		}

		if( this.firstChild !== null )
		{
			target = traverse_DOM.call( this.firstChild, callback, callback_args, done );
			if( target !== null )
			{
				return target;
			}
		}

		return null;
	}

	return traverse_DOM.call( start_object, callback, callback_args, false );
}


/*
 * Extend Node with getElementByFunction fn
 * note: extending DOM objects works in IE > 7
 */
if( typeof Node.prototype.getElementByFunction !== 'function' )
{
	Node.prototype.getElementByFunction = function( callback, callback_args ) { return getElementByFunction( this, callback, callback_args ); };
}

/*
function is_dialog_close()
{
	return this.has_class( 'dialog_close' );
}

var target = document.getElementById( 'mailing_empty' ).getElementByFunction( is_dialog_close );
console.log( target );
*/

/*
function is_dialog_close()
{
	if( this.has_class( 'dialog_close' ) )
	{
		return this;
	}

	return null;
}

var target = document.getElementsByFunction( is_dialog_close );
console.log( target );


//times are not looking good...
console.log( time_function( getElementsByClass, ['dialog_close'], null, 100000 ) );
console.log( time_function( document.body.getElementsByFunction, new Array( is_dialog_close ), document.body, 100000 ) );
*/


/*
 *	Cookie Functions
 */

var cookie_exists = function( cookie_name )
{
	var cookie_array, i, cookie;

	cookie_array	= [];
	cookie_array 		= document.cookie.split( ';' );

	for( i = 0; i < cookie_array.length; i++ )
	{
		cookie	= '';
		cookie		= cookie_array[i];

		while ( cookie.charAt( 0 ) == ' ' )
		{
			cookie = cookie.substring( 1, cookie.length );
		}

		if ( cookie.indexOf( cookie_name ) === 0 )
		{
			return true;
		}
	}

	return false;
};


var set_cookie = function( cookie_name, cookie_value, exdays )
{
	var date, expires;

	date = new Date();
	// setTime() is in milliseconds since Jan 1, 1970
	// getTime() returns the number of milliseconds from Jan 1, 1970 to now
	date.setTime( date.getTime() + ( exdays * 24 * 60 * 60 * 1000 ) );

	expires = '';
	expires		= 'expires=' + date.toUTCString();

	document.cookie = cookie_name + '=' + cookie_value + '; ' + expires;
};


var get_cookie = function( cookie_name )
{
	var name, cookie_array, i, cookie;

	name	= '';
	name		= cookie_name + '=';

	cookie_array	= [];
	cookie_array 		= document.cookie.split( ';' );

	for( i = 0; i < cookie_array.length; i++ )
	{
		cookie	= '';
		cookie		= cookie_array[i];

		while ( cookie.charAt(0)==' ')
		{
			cookie = cookie.substring(1);
		}

		if ( cookie.indexOf( name ) === 0)
		{
			return cookie.substring( name.length, cookie.length );
		}
	}

	return '';
};

var delete_cookie = function( name )
{
	document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
};

//set a basic cookie. If the next page picks it up, the user has cookies enabled
var start_cookies_enabled_test = function( cookie_test_name )
{
	// test cookie will expire in one hour
	set_cookie( cookie_test_name, 'cookie test', 0.0146 );
};

var cookies_enabled = function( cookie_test_name )
{
	if( cookie_exists( cookie_test_name ) )
	{
		//delete_cookie( cookie_test_name );
		return true;
	}

	return false;
};


/*
 * HTML Functions
 */

/*
 * hide HTML element
 */
var hide = function( element )
{
	element.setAttribute( 'default_display', element.style.display );
	element.style.display = 'none';
};


/*
 * Extend Element with hide fn
 * note: extending DOM objects works in IE > 7
 */
if( typeof Element.prototype.hide !== 'function' )
{
	Element.prototype.hide = function(){ return hide( this ); };
}

/*
 * show HTML element; only works if HTML element has been called first
 */
var show = function( element )
{
	element.style.display = element.getAttribute( 'default_display' );
};


/*
 * Extend Element with show fn
 * note: extending DOM objects works in IE > 7
 */
if( typeof Element.prototype.show !== 'function' )
{
	Element.prototype.show = function(){ return show( this ); };
}


/*
 * note: scope and value of 'this' inside Event Listener:
 * 	http://stackoverflow.com/questions/13996263/javascript-scope-addeventlistener-and-this
 * 	http://stackoverflow.com/questions/1338599/the-value-of-this-within-the-handler-using-addeventlistener
 * 	http://stackoverflow.com/questions/1803195/addeventlistener-and-the-scope-of-this
 */

var add_event = function( object, event_type, callback )
{
	//console.log( object );
	//console.log( event_type );
	if( typeof object === 'undefined' )
	{
		throw new Error( "Missing first argument, 'object'" );
	}

	if( typeof event_type === 'undefined' )
	{
		throw new Error( "Missing first argument, 'event_type'" );
	}

	if( typeof callback === 'undefined' )
	{
		throw new Error( "Missing second argument, 'callback'" );
	}

	// remove leading "on..."
	event_type = event_type.replace( /^on/, '' );

	if( MODERN_BROWSER )
	{
		object.addEventListener( event_type, callback, false );
	} else if( IE )
	{
		object.attachEvent( 'on' + event_type, callback );
	} else
	{
		throw new Error( "This browser won't accept .addEventListener nor .attachEvent" );
	}
};


/*
 * Extend EventTarget (Element, Document, Window) with add_event fn
 * note: extending DOM objects works in IE > 7
 */
/* doesn't work in IE8
if( typeof EventTarget.prototype.add_event !== 'function' )
{
	EventTarget.prototype.add_event = extension_add_event;
}
*/

/*
 * Extend HTMLDocument with add_event fn
 * note: extending DOM objects works in IE > 7
 */
if( typeof HTMLDocument.prototype.add_event !== 'function' )
{
	HTMLDocument.prototype.add_event = function( event_type, callback ){ return add_event( this, event_type, callback ); };
}


/*
 * Extend Element with add_event fn
 * note: extending DOM objects works in IE > 7
 * note: window.add_event() won't work like document.add_event() because technically
 * the add_event fn is a property of window
 */
if( typeof Element.prototype.add_event !== 'function' )
{
	Element.prototype.add_event = function( event_type, callback ){ return add_event( this, event_type, callback ); };
}

/*
 * Solves problem of onsubmit event listeners not triggering when submitting a form with JavaScript's .submit() fn.
 * source: http://stackoverflow.com/questions/645555/should-jquerys-form-submit-not-trigger-onsubmit-within-the-form-tag
 */
var manual_form_submit = function( form )
{
	//get the form element's document to create the input control with
	//(this way will work across windows in IE8)
	var button = form.ownerDocument.createElement( 'input' );

	//make sure it can't be seen/disrupts layout (even momentarily)
	button.style.display = 'none';

	//make it such that it will invoke submit if clicked
	button.type = 'submit';

	//append it and click it
	form.appendChild( button ).click();

	//if it was prevented, make sure we don't get a build up of buttons
	form.removeChild( button );
};

/*
 * Extend HTMLFormElement with manual_submit fn
 * note: extending DOM objects works in IE > 7
 */
HTMLFormElement.prototype.manual_submit = function(){ return manual_form_submit( this ); };


/*
 * asynchronously retrieves string from URI via GET
 * "We use GET to load a document or run a script, and POST to pass parameters to a script on the server"
 * source: http://www.xul.fr/ajax/get-or-post.php
 */
var ajax_get = function( uri, callback )
{
	var xhr, response;

	if( window.XMLHttpRequest )
	{
		xhr	= new XMLHttpRequest();
	} else
	{
		xhr = new ActiveXObject( "Microsoft.XMLHTTP" );
	}

	xhr.onload = function()
	{
		if( xhr.status !== 200 )
		{
			throw new Error( 'XMLHTTPRequest object returned status ' + xhr.status );
		}

		response	= '';
		response		= xhr.responseText;

		if( typeof callback === 'function' )
		{
			callback( response );
		}

    };

    xhr.open("GET", uri, true);
	xhr.send();
};


/*
 * asynchronously sends URI-encoded string to URI via POST
 *
 * call it like this:
 *	uri		= order_form.action;
 *	values	= serialize( order_form );
 *
 *	ajax_post( uri, values, function( data ) {
 *		do_thing( data );
 *	});
 *
 *
 * xhr.onload is equivalent to:
 *	xhr.onreadystatechange = function() {
 *		if( xhr.readyState == 4 )
 *			...
 *
 * If .onload fails, try .onreadystatechange.
 * see: http://stackoverflow.com/questions/9181090/is-onload-equal-to-readystate-4-in-xmlhttprequest
 */
var ajax_post = function( uri, values, callback )
{
	if( typeof uri === 'undefined' || uri === null )
	{
		throw new Error( 'Undefined URI argument' );
	}

	var xhr, response;

	if( window.XMLHttpRequest )
	{
		xhr	= new XMLHttpRequest();
	} else
	{
		xhr = new ActiveXObject( "Microsoft.XMLHTTP" );
	}

	xhr.onload = function()
	{
		if( xhr.status !== 200 )
		{
			throw new Error( 'XMLHTTPRequest object returned status ' + xhr.status );
		}

		response	= '';
		response		= xhr.responseText;

		if( typeof callback === 'function' )
		{
			callback( response );
		}
	};

	xhr.open( 'POST', uri, true );
	xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

	if( typeof values !== 'undefined' && values !== null )
	{
		xhr.send( values );
	} else
	{
		xhr.send();
	}
}


/**
 * name:	serialize.js
 * author:	brettz9
 * source:	https://gist.github.com/brettz9/7147458
 *
 * Replacement for jQuery's $(form).serialize()
 *
 * Adapted from {@link http://www.bulgaria-web-developers.com/projects/javascript/serialize/}
 * Changes:
 *	 Ensures proper URL encoding of name as well as value
 *	 Preserves element order
 *	 XHTML and JSLint-friendly
 *	 Disallows disabled form elements and reset buttons as per HTML4 [successful controls]{@link http://www.w3.org/TR/html401/interact/forms.html#h-17.13.2}
 *		 (as used in jQuery). Note: This does not serialize <object>
 *		 elements (even those without a declare attribute) or
 *		 <input type="file" />, as per jQuery, though it does serialize
 *		 the <button>'s (which are potential HTML4 successful controls) unlike jQuery
 * @license MIT/GPL
*/
var serialize = function( form )
{
	'use strict';

	var i, j, len, jLen, formElement, q = [];

	function urlencode( str )
	{
		// http://kevin.vanzonneveld.net
		// Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
		// PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
		return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
			replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
	}

	function addNameValue( name, value )
	{
		q.push(urlencode(name) + '=' + urlencode(value));
	}

	if( !form || !form.nodeName || form.nodeName.toLowerCase() !== 'form' )
	{
		throw new Error( 'First argument must be form object.' );
	}

	for( i = 0, len = form.elements.length; i < len; i++ )
	{
		formElement = form.elements[i];

		if( formElement.name === '' || formElement.disabled )
		{
			continue;
		}

		switch( formElement.nodeName.toLowerCase() )
		{
			case 'input':

				switch( formElement.type )
				{
					case 'text':
					case 'hidden':
					case 'password':
					case 'button': // Not submitted when submitting form manually, though jQuery does serialize this and it can be an HTML4 successful control
					case 'submit':
						addNameValue(formElement.name, formElement.value);
						break;
					case 'checkbox':
					case 'radio':
						if (formElement.checked)
						{
							addNameValue(formElement.name, formElement.value);
						}
						break;
					case 'file':
						// addNameValue(formElement.name, formElement.value); // Will work and part of HTML4 "successful controls", but not used in jQuery
						break;
					case 'reset':
						break;
				}
				break;

			case 'textarea':

				addNameValue(formElement.name, formElement.value);
				break;

			case 'select':

				switch (formElement.type)
				{
					case 'select-one':
						addNameValue(formElement.name, formElement.value);
						break;
					case 'select-multiple':
						for (j = 0, jLen = formElement.options.length; j < jLen; j++)
						{
							if (formElement.options[j].selected)
							{
								addNameValue(formElement.name, formElement.options[j].value);
							}
						}
						break;
				}
				break;

			case 'button': // jQuery does not submit these, though it is an HTML4 successful control
				switch (formElement.type)
				{
					case 'reset':
					case 'submit':
					case 'button':
						addNameValue(formElement.name, formElement.value);
						break;
				}
				break;
		}
	}

	return q.join('&');
}


/*
 * Get query string from URL
 * source: http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
 *
 * use:
 *	 var test_value	= query_string[ 'test' ];
 */
var query_string = (function(a)
{
	if (a === "") return {};
	var b = {};
	for (var i = 0; i < a.length; ++i)
	{
		var p=a[i].split('=', 2);
		if (p.length == 1)
			b[p[0]] = "";
		else
			b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
	}
	return b;
})(window.location.search.substr(1).split('&'));


/*
 * Get query string from URL, alternative method
 * source: http://stackoverflow.com/questions/11582512/how-to-get-url-parameters-with-javascript/11582513#11582513
 */
var get_URL_Parameter = function( name )
{
	return decodeURIComponent( ( new RegExp( '[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)' ).exec( location.search ) || [,""] )[1].replace( /\+/g, '%20' ) ) || null;
};


/*
 * This function dynamically sets image wrapper divs' CSS widths
 * which is necessary to float groups of mixed content together,
 * such as an image with a caption below it.
 */
var set_caption_wrap_width = function()
{
	add_event( window, 'load', set_caption_wrap_width_helper );
};


var set_caption_wrap_width_helper = function()
{
	var page, caption_wrap_divs, i, caption_wrap_div, caption_wrap_imgs, final_width, j, caption_wrap_img, left_right_margins, image_width;

	page	= document.getElementsByClass( 'page' )[0];
	
	// find every div with class name 'caption'
	caption_wrap_divs	= [];
	caption_wrap_divs	= page.getElementsByClass( 'captioned' );

	if( caption_wrap_divs === null || caption_wrap_divs.length <= 0 )
	{
		return false;
	}

	// iterate over every div with class "caption" on the page
	for ( i = 0; i < caption_wrap_divs.length; i++ )
	{
		caption_wrap_div	= null;
		caption_wrap_div	= caption_wrap_divs[i];

		// get each image within div with "captioned" class
		caption_wrap_imgs	= [];
		caption_wrap_imgs	= caption_wrap_div.getElementsByTagName( 'IMG' );

		if( !caption_wrap_imgs )
		{
			continue;
		}

		// search image children of "captioned" div for the one with the widest width
		final_width			= 0;
		
		for ( j = 0; j < caption_wrap_imgs.length; j++ )
		{
			caption_wrap_img	= null;
			caption_wrap_img	= caption_wrap_imgs[j];

			//console.log( caption_wrap_img.src );

			left_right_margins	= 0;
			left_right_margins	= Number( caption_wrap_img.style.marginLeft.split( 'px' )[0] ) + Number( caption_wrap_img.style.marginRight.split( 'px' )[0] );

			image_width		   	= 0;
			image_width			= Number( caption_wrap_img.offsetWidth + left_right_margins );

			//console.log( caption_wrap_img.offsetWidth );

			final_width			= Math.max( image_width, final_width );
		}

		// set the .image_wrap div's CSS width as the sum of its children images' widths and l/r margins
		caption_wrap_div.style.width = final_width + 'px';
		return true;
	}
};


/*
 * Limits the characters a given textarea can hold; similar to HTML attribute "maxlength"
 */
var add_char_limit = function( textarea, limit ) {
	add_event( textarea, 'keypress', function( event ) {
		if( textarea.value.length >= limit ) {
			event.preventDefault();
		}
	} );

	add_event( textarea, 'input', function( event ) {
		console.log( textarea.value.length );
		if( textarea.value.length >= limit ) {
			textarea.value = textarea.value.substr(0,limit);
		}
	} );
};


/*
 * Adds a limit to how many times a user can add a newline in a textarea
 */
var add_row_limit = function( textarea, limit, next_element )
{
	var focus_on_next_element;

	/*
	 * After text has number of rows equal to limit, make the next "enter" go to
	 * the next element instead of creating a new line
	 */
	add_event( textarea, 'keydown', function( event )
	{
		focus_on_next_element	= false;
		focus_on_next_element	= enter_key_to_next_element( event, textarea, limit, next_element );
	} );


	/*
	 * these two events could be used to reformat pasted text into the right number of rows
	 * but I haven't figured out how to do that without conflicting with the keydown event above...
	 */

	/*
	add_event( textarea, 'input', function( event )
	{
		extra_rows_to_spaces( textarea, limit );
	} );

	// Safari textarea
	add_event( textarea, 'textInput', function( event )
	{
		extra_rows_to_spaces( textarea, limit );
	} );
	*/
};


/*
 * Extend Element with manual_submit fn
 * note: extending DOM objects works in IE > 7
 */
Element.prototype.add_row_limit = function( limit, next_element ){ return add_row_limit( this, limit, next_element ); };


/*
 * Prevent user from adding new line if at limit
 * source/reference: http://stackoverflow.com/questions/556767/limiting-number-of-lines-in-textarea
 */

var enter_key_to_next_element = function( event, textarea, limit, next_element )
{
	var initial_value, rows_array, num_rows, current_row, keynum;

	initial_value	= '';
	initial_value	= textarea.value;

	// split textarea contents into array of rows. Its length should === number of rows
	rows_array	= [];
	rows_array	= initial_value.split("\n");

	if( rows_array === null )
	{
		return;
	}
 	num_rows	= 0;
	num_rows	= rows_array.length;

	//if text is not over limit, do nothing
	if( num_rows < limit )
	{
		return;
	}

	//console.log( event.type );
	//console.log( "rows_array:\t" + JSON.stringify( rows_array ) + "; num_rows: " + rows_array.length );
	current_row	= 0;
	current_row	= initial_value.substr( 0, textarea.selectionStart ).split( "\n" ).length;

	keynum		= 0;
	keynum 		= event.keyCode || event.which;

	// key number 13 is "Enter"(PC)/"Return"(Mac)
	if( keynum === 13 )
	{
		//console.log( "rows_array:\t" + JSON.stringify( rows_array ) + "; num_rows: " + rows_array.length );

		event.preventDefault(); // http://stackoverflow.com/questions/8269274/disable-a-key-press-event-through-java-script

		// if the cursor is on the last row, go to the next element
		if( current_row >= limit )
		{
			if( next_element )
			{
				next_element.focus();
			}
		}
		// if the cursor is on a row below the limit, put cursor at end of last row
		else
		{
			// force cursor to go to end of last row (hack; this only works when the limit is 2...)
			textarea.value	= '';		
			textarea.value	= initial_value;
		}

		return true;
	}

	return false;
};


/*
 * Imposes a strict limit on the number of rows a textarea can hold.
 * Prevents additional newline characters from being inserted either from keyboard or a paste (if attached with proper events)
 */
var extra_rows_to_spaces = function( textarea, limit )
{
	var rows_array, num_rows, no_blanks_array, i, line, rows_under_limit_array, rows_over_limit_array, output;

	//console.log( textarea.value );

	// split textarea contents into array of rows. Its length should === number of rows
	rows_array			= [];
	rows_array				= textarea.value.split( "\n" );

	//console.log( rows_array );

	return;

	if( rows_array === null )
	{
		return false;
	}

	num_rows			= 0;
	num_rows				= rows_array.length;

	//console.log( event.type );
	//console.log( "rows_array:\t" + JSON.stringify( rows_array ) + "; num_rows: " + rows_array.length );

	//if text is not over limit, do nothing
	if( num_rows < limit )
	{
		//return false;
	}

	/*
	 * Reformat text that exceeds limit so that new rows become spaces instead
	 * helpful reference: http://stackoverflow.com/questions/14259580/textarea-with-limited-lines-and-char-limits
	 */

	// take out blank rows from pasted text
	no_blanks_array	= [];
	for( i = 0; i < num_rows; i++ )
	{
		line	= '';
		line		= rows_array[ i ];

		// if last row, skip blank rows
		if( i > (limit - 1) && line.length === 0 ) { // are newline characters treated as length 0?
			continue;
		}

		// add rows with text to array
		no_blanks_array.push( line );
	}

	//console.log( no_blanks_array );

	//console.log( "no blanks:\t" + JSON.stringify( no_blanks_array ) + "; " + no_blanks_array.length );

	rows_under_limit_array	= [];
	rows_under_limit_array		= no_blanks_array.slice( 0, limit );

	//console.log( rows_under_limit_array );

	rows_over_limit_array	= [];
	rows_over_limit_array		= no_blanks_array.slice( limit );

	//console.log( rows_over_limit_array );

	//console.log( "under limit:\t" + JSON.stringify( rows_under_limit_array ) + "; " + rows_under_limit_array.length );

	output					= '';
	// rows under limit will appear normally
	output						= rows_under_limit_array.join( "\n" );

	//console.log( "over limit:\t" + JSON.stringify( rows_over_limit_array ) + "; " + rows_over_limit_array.length );

	// rows over limit will have spaces between them instead of newline characters
	output					   += ' ' + rows_over_limit_array.join( ' ' );

	// change textarea's value to the reformatted text with the allowed number of rows
	textarea.value = output;

	//console.log( "final:\t" + JSON.stringify( output ) + "; " + output.split("\n").length );
};



/*
 * Centers viewport around anchors when linked to from elsewhere
 * By default, links with a hash to an anchor position the anchor at the very top of the viewport.
 *
 * NEVER BEEN TESTED
 *
 * use:
 * var link	= document.getElementById( 'my_anchor' );
 * add_event( link, 'click', function( link_to_centered_anchor( 'example.html#happiness' ) );
 */
var link_to_centered_anchor = function( href_address )
{
	var href_array, page_address, anchor_id, anchor, anchor_top, window_height, new_position;

	href_array		= href_address.split( '#' );
	page_address	= href_array[0];
	anchor_id		= href_array[1];

	if( !anchor_id )
	{
		return false;
	}

	// if link goes to another page
	if( page_address )
	{
		window.location( page_address );
	}

	anchor			= document.getElementById( anchor_id );

	if( !anchor )
	{
		return false;
	}

	// http://stackoverflow.com/questions/442404/retrieve-the-position-x-y-of-an-html-element
	anchor_top		= anchor.getBoundingClientRect().top;

	window_height	= 0;
	// cross-browser
	window_height 		= window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

	// http://stackoverflow.com/questions/1418838/html-making-a-link-lead-to-the-anchor-centered-in-the-middle-of-the-page
	new_position	= anchor_top - Number( window_height / 2 );

	window.scrollTo( new_position, 0 );
};




/*
 *	checks for errors in credit card number, not whether it is a valid account (Luhn's algorithm)
 *	fastest one I've found
 *	source: ?
 */
var is_valid_luhn = function( number )
{
	var sumTable, sum, flip, i;

	number = number.toString();

	sumTable = [
		[0,1,2,3,4,5,6,7,8,9],
		[0,2,4,6,8,1,3,5,7,9]
	];

	sum = 0;
	flip = 0;

	for( i = number.length - 1; i >= 0; i-- )
	{
		sum += sumTable[ flip++ & 0x1 ][ number[i] ];
	}

	return ( sum % 10 === 0 );
};


/*
 * here's another one
 *
 * Luhn algorithm in JavaScript: validate credit card number supplied as string of numbers
 * @author ShirtlessKirk. Copyright (c) 2012.
 * @license WTFPL (http://www.wtfpl.net/txt/copying)
 */
var luhnChk = (function (arr)
{
    return function (ccNum)
	{

        var
            len = ccNum.length,
            bit = 1,
            sum = 0,
            val;

        while (len)
		{

            val = parseInt(ccNum.charAt(--len), 10);
            sum += (bit ^= 1) ? arr[val] : val;
        }

        return sum && sum % 10 === 0;
    };
}([0, 2, 4, 6, 8, 1, 3, 5, 7, 9]));


/*

// Luhn Algorithm check comparison
var a = '4916392672365123'; // false
var b = '3337021475580746'; // true
var c = '4508209937350839'; // true
var d = '30430750401423'; // false

function cc_fn_test( val )
{
	console.log( is_valid_luhn( val ) );
	console.log( luhnChk( val ) );

	console.log( function(){ return is_valid_luhn( val ) }.time() );
	console.log( function(){ return luhnChk( val ) }.time() );
}

cc_fn_test( a );
cc_fn_test( b );
cc_fn_test( c );
cc_fn_test( d );
*/


/*
 * theoretical limit to expiration date: See http://stackoverflow.com/questions/2500588/maximum-year-in-expiry-date-of-credit-card
 */
var add_cc_exp_year_list = function( year_count )
{
	var cc_exp_year		= document.getElementById( 'payment_cc_exp_year' );

	var year			= 0;
	year				= CURRENT_DATE.getFullYear() + 0; // coerce to INT; "getYear" fn is deprecated

	// add options
	for( var i = 0; i <= year_count; i++ ) {	// I decided to use this year and 20 years after.

		var new_option		= document.createElement( 'option' );

		var new_year		= '';
		new_year			= ( year + i ).toString();

		new_option.text		= new_year;
		// use last two digits for value (that's what the validation code looks for)
		new_option.value	= new_year.substring( 2, 4 );
		// add year option to select
		cc_exp_year.add( new_option );

	}
}


/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */
 /*
  Usage

var now = new Date();

now.format("m/dd/yy");
// Returns, e.g., 6/09/07

// Can also be used as a standalone function
dateFormat(now, "dddd, mmmm dS, yyyy, h:MM:ss TT");
// Saturday, June 9th, 2007, 5:46:21 PM

// You can use one of several named masks
now.format("isoDateTime");
// 2007-06-09T17:46:21

// ...Or add your own
dateFormat.masks.hammerTime = 'HH:MM! "Can\'t touch this!"';
now.format("hammerTime");
// 17:46! Can't touch this!

// When using the standalone dateFormat function,
// you can also provide the date as a string
dateFormat("Jun 9 2007", "fullDate");
// Saturday, June 9, 2007

// Note that if you don't include the mask argument,
// dateFormat.masks.default is used
now.format();
// Sat Jun 09 2007 17:46:21

// And if you don't include the date argument,
// the current date and time is used
dateFormat();
// Sat Jun 09 2007 17:46:22

// You can also skip the date argument (as long as your mask doesn't
// contain any numbers), in which case the current date/time is used
dateFormat("longTime");
// 5:46:22 PM EST

// And finally, you can convert local time to UTC time. Either pass in
// true as an additional argument (no argument skipping allowed in this case):
dateFormat(now, "longTime", true);
now.format("longTime", true);
// Both lines return, e.g., 10:46:21 PM UTC

// ...Or add the prefix "UTC:" to your mask.
now.format("UTC:h:MM:ss TT Z");
// 10:46:21 PM UTC

  */
var dateFormat = function () {
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) throw SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

Date.prototype.format = function( mask, utc ) {
	return dateFormat( this, mask, utc );
};
/*
 * END Date Format 1.2.3
 */


/*
 * Global Variables (properties of window object)
 */

var MODERN_BROWSER, IE, CURRENT_DATE;

// global boolean indicating modern browser
MODERN_BROWSER	= ( get_type( window.addEventListener ) !== 'undefined' );

// global boolean indicating IE v5-8
IE				= ( get_type( window.attachEvent ) !== 'undefined' );

CURRENT_DATE	= new Date();