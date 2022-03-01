<?php

/**
 * MentorController
 * @author Garrett Sens
 * 
 * This controller controls the main mentor pages.
 */

namespace App\Http\Controllers;

use CURLFile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use App\APIs\MentorApi;
use App\Models\Email;
use App\Models\Mentor;
use App\Models\Mentee;
use App\Models\Credential;
use App\Models\AcademicRank;
use App\Models\MentorshipTopic;
use App\Models\ProfilePhoto;
use App\Models\PracticeYears;
use App\Models\Department;
use App\Models\Division;
use App\Models\Section;

class MentorController extends UserController
{
	public function __construct()
	{
		// automatically connect the resource methods in this controller with MentorPolicy, so that mentors can only view and edit their own pages
		$this->authorizeResource( Mentor::class );
	}

	public function home( Mentor $mentor )
	{
		$this->authorize( 'home', $mentor ); // not covered by $this->authorizeResource()
		
		$title = 'Home';

		return view( 'mentors.home', compact( 'title', 'mentor' ) );
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index( Request $request )
	{
		$title = 'Search Mentors';
		$data = $request->all();

		// cache reference data
		$this->addFormReferenceProperties();

		unset( $data['_method'] );
		unset( $data['_token'] );

		if( empty( $data['keywords'] ) )
		{
			unset( $data['keywords'] );	
		}

		// get page number
		if( isset( $data['page'] ) && strlen( $data['page'] ) )
		{
			$page = (int)$data['page'];
			unset( $data['page'] );
		}
		else
		{
			$page = 1;
		}

		// for keyword and filter searches of mentors
		$modelsPerPage = config( 'app.results_per_page' );

		if( $data )
		{
			// get matching mentors from API
			if( Auth::user()->isAdmin() )
			{
				$results = Mentor::search( $data )->paginate( $modelsPerPage, $page );
			}
			// only get "ready" mentors
			else
			{
				$data['require_complete'] = true;
				unset( $data['require_complete'] );
				$results = Mentor::search( $data )->paginate( $modelsPerPage, $page );
			}
		}
		else
		{
			$results = Mentor::search( ['require_complete' => true] )->where( ['ordering' => 'name'] )->paginate( $modelsPerPage, $page );
		}

		$mentors = $results['data'];
		$currentPageIndex = $results['current_page'];
		$total = $results['total'];
		$numberOfPages = intval( ceil( $total / $modelsPerPage ) );
		$lastModelIndex = $modelsPerPage * $currentPageIndex;
		$firstModelIndex = $lastModelIndex - $modelsPerPage + 1;

		if( $currentPageIndex === $numberOfPages )
		{
			$lastModelIndex = $total;
		}

		if( $firstModelIndex === $lastModelIndex )
		{
			$resultsMessage = "Showing " . $firstModelIndex . " of " . $total . " results";
		}
		else
		{
			$resultsMessage = "Showing " . $firstModelIndex . " - " . $lastModelIndex . " of " . $total . " results";
		}

		// get data for hiding mentorship request buttons if already requested
		$requestedMentorIds = null;
		if( Auth::user()->hasType( Auth::user()::MENTEE_TYPE ) )
		{
			$mentee = Auth::user()->getRole();

			$mentorshipRequests = $mentee->getMentorshipRequests()->sortByDesc('created_at_date');

			$requestedMentorIds = $mentorshipRequests->pluck( 'mentor_id' )->unique();
		}

		return view( 'mentors.index', compact( 'title', 'mentors', 'data', 'requestedMentorIds', 'currentPageIndex', 'numberOfPages', 'resultsMessage' ) );
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		return parent::create();
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store( Request $request )
	{
		return parent::store( $request );
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\Mentor  $mentor
	 * @return \Illuminate\Http\Response
	 */
	public function show( Mentor $mentor )
	{
		$title = 'Mentor: ' . $mentor->name;

		// get data for hiding mentorship request buttons if already requested
		$requestedMentorIds = null;
		$mentorshipRequests = null;
		if( Auth::user()->isAdmin() )
		{
			$mentorshipRequests = $mentor->getMentorshipRequests()->sortByDesc('accepted_ind');
		}
		else if( Auth::user()->hasType( Auth::user()::MENTEE_TYPE ) )
		{
			$mentee = Auth::user()->getRole();

			$mentorshipRequests = $mentee->getMentorshipRequests()->sortByDesc('created_at_date');

			$requestedMentorIds = $mentorshipRequests->pluck( 'mentor_id' )->unique();
		}
		
		return view( 'mentors.show', compact( 'title', 'mentor', 'requestedMentorIds', 'mentorshipRequests' ) );
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Models\Mentor  $mentor
	 * @return \Illuminate\Http\Response
	 */
	public function edit( Mentor $mentor )
	{
		ini_set( 'file_uploads', 'On' );
		
		$title = 'Edit Mentor: ' . $mentor->name;

		$this->addFormReferenceProperties();

		return view( 'mentors.edit', compact( 'title', 'mentor' ) );
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Mentor  $mentor
	 * @return \Illuminate\Http\Response
	 */
	public function update( Request $request, Mentor $mentor )
	{
		$data = $request->all();
		
		// the API doesn't like these
		unset( $data['_method'] );
		unset( $data['_token'] );

		// prepare photo for API
		if( isset( $data['photo'] ) && $data['photo'] !== null )
		{
			// var_dump( $data['photo'] ); exit;
			if( $data['photo']->getError() !== null && $data['photo']->getError() !== 0 )
			{
				$message = $data['photo']->getErrorMessage();
				return view( 'error', compact( 'message' ) );
			}

			$photoProfile = ProfilePhoto::createFromFile( $data['photo'] );
			$data['photo'] = $photoProfile->id;
		}

		// take out empty interests
		if( isset( $data['professional_interests'] ) )
		{
			foreach( $data['professional_interests'] as $i => $interest )
			{
				if( $interest === null )
				{
					unset( $data['professional_interests'][$i] );
				}
			}

			if( count( $data['professional_interests'] ) === 0 )
			{
				$data['professional_interests'] = null;
			}
		}
		
		if( isset( $data['personal_interests'] ) )
		{
			foreach( $data['personal_interests'] as $i => $interest )
			{
				if( $interest === null )
				{
					unset( $data['personal_interests'][$i] );
				}
			}

			if( count( $data['personal_interests'] ) === 0 )
			{
				$data['personal_interests'] = null;
			}
		}

		if( isset( $data['department'] ) && strlen( $data['department'] ) > 0 )
		{
			$data['department'] = intval( $data['department'] );
		}
		else
		{
			$data['department'] = null;
		}

		if( isset( $data['division'] ) && strlen( $data['division'] ) > 0 )
		{
			$data['division'] = intval( $data['division'] );
		}
		else
		{
			$data['division'] = null;
		}

		if( isset( $data['section'] ) && strlen( $data['section'] ) > 0 )
		{
			$data['section'] = intval( $data['section'] );
		}
		else
		{
			$data['section'] = null;
		}

		// if checkboxes are all unchecked, add those keys back in so API will remove them
		if( !array_key_exists( 'mentorship_topics', $data ) )
		{
			$data['mentorship_topics'] = [];
		}

		if( !array_key_exists( 'credentials', $data ) )
		{
			$data['credentials'] = [];
		}

		// clear out empty WYSIWYG fields
		if( array_key_exists( 'bio', $data ) && $data['bio'] === "{\"ops\":[{\"insert\":\"\\n\"}]}" )
		{
			$data['bio'] = null;
		}

		if( array_key_exists( 'education_history', $data ) && $data['education_history'] === "{\"ops\":[{\"insert\":\"\\n\"}]}" )
		{
			$data['education_history'] = null;
		}

		$mentor = $mentor->update( $data );

		return redirect()->route( 'mentors.show', $mentor );
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Mentor  $mentor
	 * @return \Illuminate\Http\Response
	 */
	public function destroy( Request $request, Mentor $mentor )
	{
		$data = $request->all();

		$mentor->delete();

		if( isset( $data['isUser'] ) && $data['isUser'] === 'true' )
		{
			Auth::logout();
			return redirect( 'login' );
		}

		return redirect( 'mentors' );
	}

	public function mentees( Mentor $mentor )
	{
		$this->authorize( 'mentees', $mentor ); // not covered by $this->authorizeResource()

		$mentorshipRequests = $mentor->getMentorshipRequests()->sortByDesc('accepted_ind');
		$title = 'My Mentees';

		return view( 'mentors.mentees', compact( 'title', 'mentor', 'mentorshipRequests' ) );
	}

	public function removeMentee( Mentor $mentor, Mentee $mentee )
	{
		$this->authorize( 'mentees', $mentor ); // not covered by $this->authorizeResource()

		$mentor->removeMentee( $mentee );

		return redirect()->back();
	}

	/**
	 * Remove the photo attached to the mentor
	 *
	 * @param  \App\Models\Mentor  $mentor
	 * @return \Illuminate\Http\Response
	 */
	public function unsetPhoto( Mentor $mentor )
	{
		$mentor->photo = null;
		$mentor->save();
		$mentor->clearCache();

		return redirect()->back();
	}

	/**
	 * Unsubscribe this mentor from all future emails
	 *
	 * @param  \App\Models\Mentor  $mentor
	 * @return \Illuminate\Http\Response
	 */
	public function unsubscribe( Mentor $mentor )
	{
		$title = "Unsubscribe";
		$mentor->do_contact = false;
		$mentor->save();
		$mentor->clearCache();

		return view( 'mentors.unsubscribe', compact( 'title', 'mentor' ) );
	}

	private function addFormReferenceProperties()
	{
		$listAcademicRank = AcademicRank::where( 'is_active', true )->get()->sortBy( 'name', SORT_NATURAL|SORT_FLAG_CASE );
		$listAcademicRankNames = [];
		foreach( $listAcademicRank as $dataAcademicRank )
		{
			$listAcademicRankNames[$dataAcademicRank['id']] = $dataAcademicRank['name'];
		}
		View::share( 'listAcademicRank', $listAcademicRankNames );

		$listCredential = Credential::where( 'is_active', true )->get()->sortBy( 'name', SORT_NATURAL|SORT_FLAG_CASE );
		$listCredentialNames = [];
		foreach( $listCredential as $dataCredential )
		{
			$listCredentialNames[$dataCredential['id']] = $dataCredential['name'];
		}
		View::share( 'listCredential', $listCredentialNames );

		$listMentorshipTopics = MentorshipTopic::where( 'is_active', true )->get()->sortBy( 'name', SORT_NATURAL|SORT_FLAG_CASE );
		$listMentorshipTopicNames = [];
		foreach( $listMentorshipTopics as $dataMentorshipTopic )
		{
			$listMentorshipTopicNames[$dataMentorshipTopic['id']] = $dataMentorshipTopic['name'];
		}
		View::share( 'listMentorshipTopics', $listMentorshipTopicNames );

		$listPracticeYears = PracticeYears::where( 'is_active', true )->get()->sortBy( 'order' )->values()->all(); // https://stackoverflow.com/questions/53355957/laravel-sortby-not-working-to-sort-a-collection
		$listPracticeYearNames = [];
		foreach( $listPracticeYears as $dataPracticeYear )
		{
			$listPracticeYearNames[$dataPracticeYear['id']] = $dataPracticeYear['name'];
		}
		View::share( 'listPracticeYears', $listPracticeYearNames );

		// this creates the whole department hierarchy
		$listDepartment = Department::where( 'is_active', true )->get()->sortBy( 'name', SORT_NATURAL|SORT_FLAG_CASE );
		View::share( 'listDepartment', $listDepartment );	
	}
}
