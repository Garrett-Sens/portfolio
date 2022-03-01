<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This is the main routing page of the Mentor Connection application.
|
*/

use Illuminate\Support\Facades\Route;


Route::get( '/test', 'TestController@index' );

Route::get( '/phpinfo', 'TestController@phpInfo' );

Route::get( '/clearcache', 'TestController@clearCache' )->name( 'clear_cache' );

// login and logout pages
Auth::routes();

// all other pages require login first
Route::group(
	[
		'middleware' => ['auth','web']
	],
	function()
	{
		Debugbar::addMeasure('web.php group', LARAVEL_START, microtime(true));

		Route::get( '/', 'HomeController@index' );

		Route::get( '/home', 'HomeController@index' )->name( 'home' );

		Route::resource(
			'/users',
			'UserController'
		);

		// add custom routes before adding the resource
		Route::get(
			'/mentees/{mentee}/home',
			'MenteeController@home'
		)->name( 'mentees.home' );

		Route::get(
			'/mentees/{mentee}/mentors',
			'MenteeController@mentors'
		)->name( 'mentees.mentors' );

		Route::get(
			'/mentees/{mentee}/unset_photo',
			'MenteeController@unsetPhoto'
		)->name( 'mentees.unset_photo' );

		Route::get(
			'/mentees/{mentee}/unsubscribe',
			'MenteeController@unsubscribe'
		)->name( 'mentees.unsubscribe' );

		Route::resource(
			'/mentees',
			'MenteeController'
		);

		// add custom routes before adding the resource
		Route::get(
			'/mentors/{mentor}/home',
			'MentorController@home'
		)->name( 'mentors.home' );

		Route::get(
			'/mentors/{mentor}/mentees',
			'MentorController@mentees'
		)->name( 'mentors.mentees' );

		Route::get(
			'/mentors/{mentor}/unset_photo',
			'MentorController@unsetPhoto'
		)->name( 'mentors.unset_photo' );

		Route::get(
			'/mentors/{mentor}/unsubscribe',
			'MentorController@unsubscribe'
		)->name( 'mentors.unsubscribe' );

		Route::get(
			'/mentors/{mentor}/remove/{mentee}',
			'MentorController@removeMentee'
		)->name( 'mentors.removeMentee' );

		Route::resource(
			'/mentors',
			'MentorController'
		);

		Route::get(
			'/mentorship_requests/accept/{mentorship_request}',
			'MentorshipRequestController@accept'
		)->name( 'mentorship_requests.accept' );

		Route::post(
			'/mentorship_requests/reject/{mentorship_request}',
			'MentorshipRequestController@reject'
		)->name( 'mentorship_requests.reject' );

		Route::resource(
			'/mentorship_requests',
			'MentorshipRequestController'
		);

		Route::resource(
			'/profilephotos',
			'ProfilePhotoController'
		);

		Route::get(
			'/divisions/get/{key?}/{value?}',
			'DivisionController@get'
		)->name( 'divisions.get' );

		Route::get(
			'/sections/get/{key?}/{value?}',
			'SectionController@get'
		)->name( 'sections.get' );

		Route::group(
			[
				'middleware' => ['type:admin']
			],
			function()
			{
				Route::get(
					'/admin/',
					'AdminController@index'
				)->name( 'admin.index' );

				Route::get(
					'/admin/config',
					'AdminController@config'
				)->name( 'admin.config' );

				Route::get(
					'/admin/dashboard.report',
					'AdminController@dashboardReport'
				)->name( 'admin.dashboardReport' );

				Route::get(
					'/admin/departments.report',
					'AdminController@departmentsReport'
				)->name( 'admin.departmentsReport' );

				Route::get(
					'/admin/mentors.report',
					'AdminController@mentorsReport'
				)->name( 'admin.mentorsReport' );

				Route::get(
					'/admin/mentees.report',
					'AdminController@menteesReport'
				)->name( 'admin.menteesReport' );

				Route::get(
					'/admin/mentorship.requests.report',
					'AdminController@mentorshipRequestsReport'
				)->name( 'admin.mentorshipRequestsReport' );

				Route::get(
					'/admin/delete.mentorship.requests',
					'AdminController@deleteMentorshipRequests'
				)->name( 'admin.delete_mentorship_requests' );
				
				Route::get(
					'/admin/create.mentors',
					'AdminController@createMentors'
				)->name( 'admin.create_mentors' );

				Route::get(
					'/admin/delete.mentors',
					'AdminController@deleteMentors'
				)->name( 'admin.delete_mentors' );

				Route::get(
					'/admin/create.mentees',
					'AdminController@createMentees'
				)->name( 'admin.create_mentees' );

				Route::get(
					'/admin/delete.mentees',
					'AdminController@deleteMentees'
				)->name( 'admin.delete_mentees' );

				Route::get(
					'/academic_ranks/edit.all',
					'AcademicRankController@editAll'
				)->name( 'academic_ranks.editAll' );
				
				Route::resource(
					'academic_ranks',
					'AcademicRankController'
				);

				Route::get(
					'/credentials/edit.all',
					'CredentialController@editAll'
				)->name( 'credentials.editAll' );

				Route::resource(
					'credentials',
					'CredentialController'
				);
				
				Route::get(
					'/departments/edit.all',
					'DepartmentController@editAll'
				)->name( 'departments.editAll' );
				
				Route::resource(
					'departments',
					'DepartmentController'
				);
				
				Route::get(
					'/divisions/edit.all',
					'DivisionController@editAll'
				)->name( 'divisions.editAll' );
				
				Route::resource(
					'divisions',
					'DivisionController'
				);
				
				Route::get(
					'/sections/edit.all',
					'SectionController@editAll'
				)->name( 'sections.editAll' );
				
				Route::resource(
					'sections',
					'SectionController'
				);

				Route::get(
					'/organizations/edit.all',
					'OrganizationController@editAll'
				)->name( 'organizations.editAll' );

				Route::resource(
					'organizations',
					'OrganizationController'
				);

				Route::get(
					'/mentorship_topics/edit.all',
					'MentorshipTopicController@editAll'
				)->name( 'mentorship_topics.editAll' );

				Route::resource(
					'mentorship_topics',
					'MentorshipTopicController'
				);

				Route::get(
					'/practice_years/edit.all',
					'PracticeYearsController@editAll'
				)->name( 'practice_years.editAll' );

				Route::resource(
					'practice_years',
					'PracticeYearsController'
				);

				// for testing only
				Route::get(
					'/mentorship_requests/{mentorship_request}/mentor_email',
					'MentorshipRequestController@mentorEmail'
				)->name( 'mentorship_requests.mentor_email' );

				// for testing only
				Route::get(
					'/mentorship_requests/{mentorship_request}/mentee_email',
					'MentorshipRequestController@menteeEmail'
				)->name( 'mentorship_requests.mentee_email' );

				// for testing only
				Route::get(
					'/mentorship_requests/{mentorship_request}/approval_email',
					'MentorshipRequestController@approvalEmail'
				)->name( 'mentorship_requests.approval_email' );

				// for testing only
				Route::get(
					'/mentorship_requests/{mentorship_request}/rejection_email',
					'MentorshipRequestController@rejectionEmail'
				)->name( 'mentorship_requests.rejection_email' );
				
				Route::get(
					'/admin/create.reference.data',
					'AdminController@createReferenceData'
				)->name( 'admin.create-reference-data' );
				
				Route::get(
					'/admin/delete.reference.data',
					'AdminController@deleteReferenceData'
				)->name( 'admin.delete-reference-data' );

				Route::get(
					'/admin/create.department.data',
					'AdminController@createDepartmentData'
				)->name( 'admin.create-department-data' );
				
				Route::get(
					'/admin/delete.department.data',
					'AdminController@deleteDepartmentData'
				)->name( 'admin.delete-department-data' );
				
				Route::get(
					'/admin/create.credential.data',
					'AdminController@createCredentialData'
				)->name( 'admin.create-credential-data' );
				
				Route::get(
					'/admin/delete.credential.data',
					'AdminController@deleteCredentialData'
				)->name( 'admin.delete-credential-data' );

				Route::get(
					'/admin/create.practice.years.data',
					'AdminController@createPracticeYearsData'
				)->name( 'admin.create-practice-years-data' );
				
				Route::get(
					'/admin/delete.practice.years.data',
					'AdminController@deletePracticeYearsData'
				)->name( 'admin.delete-practice-years-data' );

				Route::get(
					'/admin/create.academic.rank.data',
					'AdminController@createAcademicRankData'
				)->name( 'admin.create-academic-rank-data' );
				
				Route::get(
					'/admin/delete.academic.rank.data',
					'AdminController@deleteAcademicRankData'
				)->name( 'admin.delete-academic-rank-data' );

				Route::get(
					'/admin/create.mentorship.topic.data',
					'AdminController@createMentorshipTopicData'
				)->name( 'admin.create-mentorship-topic-data' );
				
				Route::get(
					'/admin/delete.mentorship.topic.data',
					'AdminController@deleteMentorshipTopicData'
				)->name( 'admin.delete-mentorship-topic-data' );

				Route::get(
					'/admin/create.student.status.data',
					'AdminController@createStudentStatusData'
				)->name( 'admin.create-student-status-data' );
				
				Route::get(
					'/admin/delete.student.status.data',
					'AdminController@deleteStudentStatusData'
				)->name( 'admin.delete-student-status-data' );
			}
		);
	}
);
