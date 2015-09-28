
/**
 *	Creates non-modal dialog boxes and displays them in the middle of the viewport
 *	@author Garrett Sens, Feb 2015
 *
 *	@param string [dialog_id] The i.d. of the div within #dialog_overlay containing the html of the dialog box (initially hidden)
 *	@param int/string [assigned_width] Replaces the default dialog box width
 */
var Dialog = function( dialog_id, assigned_width )
{
	var box_elem, overlay_elem, site_wrap_elem, device_width, viewport_width;

	if( typeof dialog_id === 'undefined' || dialog_id === null || dialog_id.length === 0 )
	{
		throw new Error( "Missing first argument, the dialog HTML element's ID." );
	}

	box_elem 		= document.getElementById( dialog_id );
	overlay_elem	= document.getElementById( 'dialog_overlay' );
	site_wrap_elem	= document.getElementById( 'site_wrap' );
	device_width	= window.screen.availWidth;
	viewport_width	= window.innerWidth
				   || document.documentElement.clientWidth	// cross-browser
				   || document.body.clientWidth;			// cross-browser

	if( box_elem === null )
	{
		throw new Error( 'No element on the page matches ID "' + dialog_id + '".' );
	}

	if( overlay_elem === null )
	{
		throw new Error( 'No element on the page matches ID "dialog_overlay".' );
	}

	this.id			= dialog_id;
	this.box		= box_elem;
	this.overlay	= overlay_elem;
	this.site_wrap;
	this.header;
	this.body;
	this.footer;
	this.assigned_width;
	this.body_inside; // not currently used
	this.mobile		= false;

	if( typeof assigned_width !== 'undefined' )
	{
		assigned_width		= parseInt( ( '' + assigned_width ).replace( /^([\d.]+)(px)?&/, "$1" ) );
		if( !isNaN( assigned_width ) )
		{
			this.assigned_width	= assigned_width;
		}
	}

	if( site_wrap_elem !== null )
	{
		this.site_wrap	= site_wrap_elem;
	}

	// mobile layouts
	if( device_width < viewport_width )
	{
		this.mobile		= true;
	}

	//console.log( 'device_width: ' + device_width );

	// set default display style for dialog box. JS can't get CSS display rule so we have to tolerate some redundancy
	box_elem.style.display	= 'none';

	// initialize dialog display counter used in show() and hide()
	overlay_elem.setAttribute( 'boxes_on_display', 0 );

	// get dialog box's header and footer and their dimensions
	this.parse_dialog_box();

	this.add_events();
};


Dialog.prototype.parse_dialog_box = function()
{
	var header, footer, body, body_inside;

	//console.log( this.box.getElementsByClass( 'header' ) );

	header	= this.box.getElementsByClass( 'header' )[0],
	footer	= this.box.getElementsByClass( 'footer' )[0],
	body	= this.box.getElementsByClass( 'body' )[0],
	body_inside	= document.createElement( 'DIV' );

	/*
	 * add inner wrap div around content (so content won't overflow .body)
	 */

	if( body.firstChild !== null && body.firstChild.has_class( 'body_inner' ) )
	{
		body_inside = body.firstChild;
	} else
	{
		body_inside.add_class( 'body_inner' );

		// stick inner wrapper between body div and body div's contents
		while( body.hasChildNodes() )
		{
			body_inside.appendChild( body.firstChild ); // stick all of body's children to body_inside
		}
		body.appendChild( body_inside ); // stick body_inside to body
	}

	this.header			= header;
	this.body			= body;
	this.footer			= footer;
	this.body_inside	= body_inside;
};


Dialog.prototype.add_events = function()
{
	var close_buttons, close_buttons_length, i;

	/*
	 * set dialog box's dimensions once on load and again whenever viewport resizes
	 */
	add_event( window, 'load', this.set_size.bind( this ) );
	add_event( window, 'resize', this.set_size.bind( this ) );

	/*
	 * close box events
	 */
	close_buttons = this.box.getElementsByClass( 'dialog_close' );
	if( close_buttons !== null )
	{
		close_buttons_length = close_buttons.length;
		if( close_buttons_length > 1 )
		{
			for( i = 0, l = close_buttons_length; i < l; i++ )
			{
				add_event( close_buttons[i], 'click', this.hide.bind( this ) );
			}
		} else if( close_buttons_length === 1 )
		{
			add_event( close_buttons[0], 'click', this.hide.bind( this ) );
		}
	}
};


/*
 *	SET_SIZE()
 *	establishes dimensions and position of dialog box
 *	called when Dialog object is created and again every time the viewport is resized
 */
Dialog.prototype.set_size = function()
{
	var dialog_hidden;

	//console.log( this.id + ' set_size()' );

	// save whether the overlay and box are initially displayed, so we don't hide them when the viewport is resized
	dialog_hidden	= false;
	// remember, .style.display may be empty string ('') here
	dialog_hidden	= ( this.box.style.display === 'none' );

	/*
	// briefly display the dialog box (but keep the overlay hidden) to get offset dimensions
	if( dialog_hidden )
	{
		this.show();
	}
	*/

	this.overlay.style.display	= 'block';
	this.box.style.display		= 'block';
	//window.alert( this.id + ' set_size start' );

	if( typeof this.assigned_width !== 'undefined' && this.assigned_width !== null )
	{
		this.set_width( this.assigned_width );
	} else
	{
		this.set_width();
	}

	this.set_height();

	/*
	// restore overlay and dialog box to their original display states
	if( dialog_hidden )
	{
		this.hide();
	}
	*/

	this.overlay.style.display	= 'none';
	this.box.style.display		= 'none';
	//window.alert( this.id + ' set_size end' );
	
};


/*
 * Set CSS Width and Left/Right Rules
 */
Dialog.prototype.set_width	= function( assigned_width ) {

	var dialog_box, assigned_width, box_width, site_wrap, viewport_width, site_width, left;

	dialog_box		= this.box,
	assigned_width	= this.assigned_width,
	box_width		= 0,
	site_wrap,
	viewport_width	= window.innerWidth
				   || document.documentElement.clientWidth	// cross-browser
				   || document.body.clientWidth,			// cross-browser
	site_width		= 0,
	left			= 0;

	if( typeof assigned_width === 'number' )
	{
		box_width	= assigned_width;
	} else
	{
		/* 
		 * using the site wrapper's width instead of the viewport's width will prevent the dialog boxes
		 * from being too wide on widescreen monitors
		 */
		
		if( typeof this.site_wrap !== 'undefined' && site_wrap !== null )
		{
			site_wrap	= this.site_wrap;
			site_width	= Math.round( Math.min( site_wrap.scrollWidth, viewport_width ) );
		} else
		{
			site_width	= viewport_width;
		}

		// mobile layouts that don't use viewport meta tag
		if( this.mobile )
		{
			// 800 seems like a good max width for dialogs on larger mobile screens, like tablets
			box_width	= Math.round( Math.min( 800, Number( .8 * site_width ) ) );
		}
		// desktop layouts and mobile layouts that use viewport meta tag
		else
		{
			// box width = 220px < 80% of site width/viewport < 600px. 220px is the smallest width where the text still looks ok
			box_width	= Math.round( Math.min( 600, Math.max( 220, Number( 0.8 * site_width ) ) ) ); /* now that we're using meta viewport tag, even mobile goes here and 80% looks better on mobile */
		}
	}
	

	/*
	 * horizontally center the dialog box in the viewport
	 * note: setting style.right doesn't leave room for a scroll bar to appear and ends up smashing the content
	 */
	left	= Math.round( Number( 0.5 * Number( viewport_width - box_width ) ) );


	dialog_box.style.width	= box_width + 'px';
	dialog_box.style.left	= left + 'px';
	
	//console.log( 'viewport width: ' + viewport_width + '; site width: ' + site_width + '; box width: ' + box_width + '; box left: ' + left );
}


/*
 * Set CSS Height and Top/Bottom Rules
 *
 * The scroll box dialog box has a fixed height which is 60% of the viewport.
 * The header and footer's heights are set by CSS.
 * The body of the dialog, however, has a variable height
 */
Dialog.prototype.set_height	= function() {

	var site_wrap, viewport_height, site_wrap, box_height, header_height, footer_height, body_height, top;

	site_wrap,
	viewport_height	= window.innerHeight
				   || document.documentElement.clientHeight	// cross-browser
				   || document.body.clientHeight,			// cross-browser
	site_height		= 0,
	box_height		= 0,
	header_height	= 0,
	footer_height	= 0,
	body_height		= 0,
	top				= 0;

	/* 
	 * using the site wrapper's width instead of the viewport's width will prevent the dialog boxes
	 * from being too wide on widescreen monitors
	 */

	if( typeof this.site_wrap !== 'undefined' && site_wrap !== null )
	{
		site_wrap	= this.site_wrap;
		site_height	= Math.round( Math.min( site_wrap.clientHeight, viewport_height ) );
	} else
	{
		site_height	= viewport_height;
	}

	/*
	 * set a maximum height for the dialog box
	 * (the body of the dialog is proportional to the viewport height, so if
	 * the viewport is too short the box will be too short to display anything)
	 */
	box_height	= Math.round( Math.min( 0.6 * site_height, this.box.clientHeight ) );

	
	/**
	 * @todo
	 * maybe I should let the dialog box have its natural height unless it's too tall,
	 * in which case I could add a "scroll" class to .body_inner that would add the scroll bar
	*/


	/*
	 * set body height
	 */
	if( typeof this.header !== 'undefined' && typeof this.footer !== 'undefined' && this.header !== null && this.footer !== null )
	{
		header_height	= Math.round( this.header.offsetHeight );
		footer_height	= Math.round( this.footer.offsetHeight );
		body_height		= box_height - header_height - footer_height;
		this.body.style.height	= body_height + 'px';
	}

	
	/*
	 * vertically center the dialog box
	 * note: if you set style.bottom, there is no room to absorb scroll bars if they appear
	 */
	top	= Math.round( Number( 0.5 * Number( site_height - box_height ) ) );
	
	
	this.box.style.top 		= top + 'px';
	this.box.style.height	= box_height + 'px';

	//console.log( 'viewport height: ' + viewport_height + '; site height: ' + site_height + '; box height: ' + box_height + '; box top: ' + top );
}


Dialog.prototype.show = function()
{
	//window.alert( this.id + ' show start' );
	var boxes_on_display;
	
	if( this.box.style.display === 'block' ) {	// might be empty string at this point (remember, css styles and inline js styles are separate)
		return;
	}

	//console.log( 'showing ' + this.id );

	/*
	 * for the rare case where multiple dialog boxes are visible at the same time,
	 * we set a counter so that we hide the overlay only when the last dialog box is hidden
	 */
	boxes_on_display	= this.overlay.getAttribute( 'boxes_on_display' );

	this.overlay.setAttribute( 'boxes_on_display', ++boxes_on_display );

	this.overlay.style.display	= 'block';
	this.box.style.display	= 'block';

	//window.alert( this.id + ' show end' );
};


Dialog.prototype.show_on_window_load = function() {

	// modern browsers
	if( window.addEventListener )
	{
		window.addEventListener( 'load', function( event )
		{
			this.show();
		}.bind( this ), false );
	}
	// IE < 9
	else if( window.attachEvent )
	{
		window.attachEvent( 'onload', function( event )
		{
			this.show();
		}.bind( this ) );
	}
}


Dialog.prototype.hide = function()
{
	var boxes_on_display;

	if( this.box.style.display === 'none' )
	{
		return;
	}

	//console.log( 'hiding ' + this.id );

	/*
	 * for the rare case where two or more dialog boxes are visible at the same time,
	 * we set a counter so that we hide the overlay only when the last dialog box is hidden
	 */
	boxes_on_display	= this.overlay.getAttribute( 'boxes_on_display' );

	this.overlay.setAttribute( 'boxes_on_display', --boxes_on_display );

	if( boxes_on_display <= 0 )
	{
		this.overlay.style.display	= 'none';
	}
	
	this.box.style.display	= 'none';
};

