/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

const mix = require('laravel-mix');

mix.js([
	'resources/js/overlay.js',
	'resources/js/datatable_additions.js',
	'resources/js/photo.js',
	'resources/js/initQuill.js',
	'resources/js/dialog.js',
	'resources/js/form.js',
	'resources/js/rows.js',
	'resources/js/wysiwyg.js',
	'resources/js/app.js'
], 'public/js/app.js')

.copyDirectory('node_modules/font-awesome/fonts/', 'public/fonts')

// copy images from datatables except the favicon
.copy('node_modules/datatables.net-dt/images/sort_asc.png', 'public/images')
.copy('node_modules/datatables.net-dt/images/sort_asc_disabled.png', 'public/images')
.copy('node_modules/datatables.net-dt/images/sort_both.png', 'public/images')
.copy('node_modules/datatables.net-dt/images/sort_desc.png', 'public/images')
.copy('node_modules/datatables.net-dt/images/sort_desc_disabled.png', 'public/images')

.sass('resources/sass/app.scss', 'public/css/app.css')

.styles([
	'node_modules/tingle.js/dist/tingle.css',
	'node_modules/quill/dist/quill.core.css',
	'node_modules/quill/dist/quill.snow.css',
	'resources/css/normalize.css',
	'resources/css/overlay.css',
	'resources/css/main.css',
], 'public/css/styles.css')

.styles('resources/css/print.css', 'public/css/print.css')

// keep libraries in separate "vendor.js" file so they can be cached separately
.extract([ 	
	'jquery',
	'datatables.net',
	'datatables.net-dt',
	'datatables.net-buttons',
	'datatables.net-buttons-dt',
	'pdfmake',
	'jszip',
	'moment',
	'tingle.js',
	'quill'
])
.autoload({ // make these modules with global variables available to other modules
	jquery: ['$', 'window.jQuery', 'jQuery', 'jquery'],
	DataTable : 'datatables.net-dt'
});

if( mix.inProduction() )
{
	mix.version();
}
else
{
	mix.sourceMaps();
}
