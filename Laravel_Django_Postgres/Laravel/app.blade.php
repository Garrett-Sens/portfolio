<?php
/**
 * layouts/app.blade.php
 * @author Garrett Sens
 * 
 * This is the main application layout.
 */
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<!-- CSRF Token -->
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>{{ config('app.name', 'Laravel') }}{{ isset( $title ) ? ' - ' . $title : '' }}</title>

		{{-- Favicon --}}
		<link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">

		<!-- Styles -->
		<link type="text/css" rel="stylesheet" href="{{ mix('css/app.css') }}">
		<link type="text/css" rel="stylesheet" href="{{ mix('css/styles.css') }}">
		<link type="text/css" rel="stylesheet" media="print" href="{{ asset('css/print.css') }}">
		@yield( 'css' )
	</head>
	<body>
		<div id='layout'>
			<header id='header' class='grid-box'>
				<div id='header-elements'>
					<nav id='header-logo'>
						<a id='home' href="/"><img alt="University of Utah Health" src="{{ asset('images/logo-uhealth.svg' ) }}"></a>
					</nav>
					<div id='site-name'>
						<a id='site-name-big' href="/">Mentor Connection</a>
					</div>
					<nav id='nav-primary'>
						<ul class='no-print'>
							<li><a href='https://tools.medicine.utah.edu/'>Tools</a></li>
							<li><a href="https://tools.medicine.utah.edu/som.computer.support/support.ticket/ticket.create">Help</a></li>
							@auth
							<li class="nav-item dropdown">
								<div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
									<a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Log Out') }}</a>
									<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
										@csrf
									</form>
								</div>
							</li>
							@elseif( Route::currentRouteName() !== 'login' )
							<li><a href="{{ route( 'login' ) }}">{{ __('Log In') }}</a></li>
							@endauth
						</ul>
					</nav>
				</div>
			</header>
			<aside id='side1' class='grid-box'>
				<nav id='nav-secondary' class="no-print">
					<ul>
						@auth
							@if( Auth::user()->hasType( Auth::user()::MENTOR_TYPE ) )
							<li><a class='anchor-button' href="{{ route( 'mentors.home', [ 'mentor' => Auth::user()->getRole() ] ) }}">Home</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentors.show', [ 'mentor' => Auth::user()->getRole() ] ) }}">My Profile</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentors.mentees', [ 'mentor' => Auth::user()->getRole() ] ) }}">My Mentees</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentees.index' ) }}">Search Mentees</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentors.index' ) }}">Search Mentors</a></li>
							@elseif( Auth::user()->hasType( Auth::user()::MENTEE_TYPE ) )
							<li><a class='anchor-button' href="{{ route( 'mentees.home', [ 'mentee' => Auth::user()->getRole() ] ) }}">Home</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentees.show', [ 'mentee' => Auth::user()->getRole() ] ) }}">My Profile</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentees.mentors', [ 'mentee' => Auth::user()->getRole() ] ) }}">My Mentors</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentors.index' ) }}">Search Mentors</a></li>
							@elseif( Auth::user()->hasType( Auth::user()::ADMIN_TYPE ) )
							<li><a class='anchor-button' href="{{ route( 'admin.dashboardReport' ) }}">Dashboard</a></li>
							<li><a class='anchor-button' href="{{ route( 'admin.departmentsReport' ) }}">Departments Report</a></li>
							<li><a class='anchor-button' href="{{ route( 'admin.mentorsReport' ) }}">Mentors Report</a></li>
							<li><a class='anchor-button' href="{{ route( 'admin.menteesReport' ) }}">Mentees Report</a></li>
							<li><a class='anchor-button' href="{{ route( 'admin.mentorshipRequestsReport' ) }}">Requests Report</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentors.index' ) }}">Search Mentors</a></li>
							<li><a class='anchor-button' href="{{ route( 'mentees.index' ) }}">Search Mentees</a></li>
							<li><a class='anchor-button' href="{{ route( 'users.create' ) }}">Create User</a></li>
							<li><a class='anchor-button' href="{{ route( 'admin.config' ) }}">Config</a></li>
							@endif
						@endauth
					</ul>
				</nav>
			</aside>
			<main id='content' class='grid-box'>
				@if( count( $errors ) > 0 )
					<section class='error'>
						<h3>Error</h3>
						<ul id='ul-errors' class='plain-list'>
							@foreach( $errors->all() as $error )
								<li>{{$error}}</li>
							@endforeach
						</ul>
					</section>
				@endif

				@yield( 'content' )
			</main>
			<aside id='side2' class='grid-box'></aside>
			<div id='footer_background'></div>
			<footer id='footer' class='grid-box'>
				<p>School of Medicine Tools Application | Copyright &copy; <?php echo date('Y') ?> University of Utah School of Medicine</p>
				<p>30 N. 1900 E. Salt Lake City, UT 84132 | 801.581.7201</p>
			</footer>
		</div>
		<div id='dialogs'>
			<div id='dialog-user-session-timeout' class='dialog'>
				Your session is about to expire and you will be logged out of the system soon. Would you like to continue to use this application? If so, click the "Extend Session" button.
			</div>
			<div id='dialog-user-refresh-error' class='dialog'>
				Error: the session has already expired. Please log in again.
			</div>
		</div>
		<div id='overlay' style='display: none'><div id='loading-wheel'></div></div>
		<!-- Scripts -->
		<script src="{{ mix('js/manifest.js') }}"></script>
		<script src="{{ mix('js/vendor.js') }}"></script>
		<script src="{{ mix('js/app.js') }}"></script>
		@yield( 'js' )
	</body>
</html>
