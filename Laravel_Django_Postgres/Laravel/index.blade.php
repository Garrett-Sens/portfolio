<?php
/**
 * mentors/index.blade.php
 * @author Garrett Sens
 * 
 * This view shows all mentors and handles mentor searching.
 */
?>
@extends('layouts.app')

@section('css')
@parent
<style>

	
</style>
@endsection

@section('content')
	<h1>Mentors</h1>
	<section id='search-mentors' class='block'>
		<h3 class='block-heading'>Search Mentors</h3>
		<div class='block-body'>
			@include(
				'forms.mentorSearchForm',
				[
					'method' => 'GET',
					'action' => route('mentors.index'),
					'listDepartment' => $listDepartment,
					'submitButton' => 'Create',
					'data' => $data
				]
			)
		</div>
	</section>
	<section>
		@if( ( $data && count( $data ) ) && ( !$mentors || !count( $mentors ) ) )
		<h4 style='text-align: center;'>No mentors found. Try broadening your search.</h4>
		@else
		<h4 style='text-align: center;'>{{ $resultsMessage }}</h4>
		@endif

		<div>
			<ul id='mentor-list' class='plain-list person-list'>
				@if( $mentors && count( $mentors ) )
					@foreach( $mentors as $mentor )
						@if( $mentor->isReady || Auth::user()->isAdmin() )
						<li>@include( 'layouts.mentorPreview', ['mentor' => $mentor, 'urlDetail' => route( 'mentors.show', $mentor->id )] )</li>
						@endif
					@endforeach
				@endif
			</ul>
		</div>
	</section>
	<section>
		<div>
			<ul class='pages-list plain-list horizontal-list'>
				@if( $currentPageIndex > 1 )
					<?php
						$previousPageIndex = $currentPageIndex - 1;
						$data['page'] = $previousPageIndex;
					?>
					<li class='page-nav' style='flex: 1 0 170px;'><a href="{{ route('mentors.index', $data ) }}">Previous Page</a></li>
				@endif
				@for( $i = 1; $i <= $numberOfPages; $i++ )
					@if( $i === $currentPageIndex )
						<li><span>{{ $i }}</span></li>
					@else
						<?php $data['page'] = $i; ?>
						<li><a href="{{ route('mentors.index', $data ) }}">{{$i}}</a></li>
					@endif
				@endfor
				<?php $nextPageIndex = $currentPageIndex + 1; ?>
				@if( $currentPageIndex < $numberOfPages )
					<?php $data['page'] = $nextPageIndex; ?>
					<li class='page-nav' style='flex: 0 0 118px;'><a href="{{ route('mentors.index', $data ) }}">Next Page</a></li>
				@endif
			</ul>
		</div>
	</section>
@endsection

@section('footer')
	
@endsection

@section('js')
@parent
<script type='text/javascript'>

	// show overlay when requesting mentorship
	document.querySelectorAll( '.mentorship-form' ).forEach( function( form, key )
	{
		form.addEventListener( 'submit', function( ev )
		{
			ev.preventDefault();

			const nameMentor = form.querySelector('input[name=mentor_name]').value;

			const dialog = Dialog.createDialog(
				"<h2>Mentorship Request</h2><form><textarea name='explanation' class='textarea-request-explanation' rows='6' style='width: 100%' placeholder='Add a personal note to your request (optional).'></textarea></form>",
				"Send Request",
				form
			);

			// open modal dialog
			dialog.open();
		});
	});
		
</script>
@endsection
