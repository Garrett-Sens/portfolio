<?php

/**
 * Mentor
 * @author Garrett Sens
 * 
 * This model handles communication with the /mentors API endpoint.
 */

namespace App\Models;

use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\AcademicRank;
use App\Models\ApiModel;
use App\Models\Mentee;
use App\Models\Department;
use App\Models\Division;
use App\Models\Section;
use App\Models\Credential;
use App\Models\MentorshipTopic;
use App\Models\MentorshipRequest;
use App\Models\PracticeYears;
use App\Models\ProfilePhoto;

class Mentor extends ApiModel
{
	// This property is directly used and pluralized by the API Wrapper (ex : getUsers).
	protected $entity = 'mentors';

	// If your API ressource can be identified with a unique key you can define 
	// the primary key. By default it is 'id'.
	protected $primaryKey = 'id';

	public function getAcademicRankAttribute()
	{
		$idAcademicRank = $this->attributes['academic_rank'];
		if( $idAcademicRank )
		{
			return AcademicRank::where( ['is_active' => true] )->findOrFail( $idAcademicRank );
		}
	}

	// in order for mentor profiles to be visible to mentees, they need to fill out basic information and at least one of the fields that explains what they do so we can search for them by keyword
	public function getIsReadyAttribute()
	{
		return  !empty( $this->attributes['name'] ) &&
				!empty( $this->attributes['contact_email'] ) &&
				(
					!empty( $this->attributes['bio'] ) ||
					!empty( $this->attributes['professional_interests'] ) ||
					!empty( $this->attributes['mentorship_topics'] )
				);
	}

	public function getDepartmentModelAttribute()
	{
		$departmentId = $this->getAttribute( 'department' );
		if( $departmentId )
		{
			return Department::where( ['is_active' => true] )->findOrFail( $departmentId );
		}
	}

	public function getDivisionModelAttribute()
	{
		$divisionId = $this->getAttribute( 'division' );
		if( $divisionId )
		{
			return Division::where( ['is_active' => true] )->findOrFail( $divisionId );
		}
	}

	public function getSectionModelAttribute()
	{
		$sectionId = $this->getAttribute( 'section' );
		if( $sectionId )
		{
			return Section::where( ['is_active' => true] )->findOrFail( $sectionId );
		}
	}

	public function getOrganizationAttribute()
	{
		$organizations = [];

		$section = $this->getAttribute( 'section_model' );
		if( $section )
		{
			$organizations[] = $section;
		}

		$division = $this->getAttribute( 'division_model' );
		if( $division )
		{
			$organizations[] = $division;
		}

		$department = $this->getAttribute( 'department_model' );
		if( $department )
		{
			$organizations[] = $department;
		}

		if( count( $organizations ) )
		{
			return implode( ', ', array_column( array_filter( $organizations ), 'name' ) );
		}
	}

	public function getStudentsAttribute()
	{
		$listStudents = $this->attributes['students'];
		
		if( $listStudents )
		{
			$collectionMentee = [];

			foreach( $listStudents as $dataStudent )
			{
				$collectionMentee[] = new Mentee( $dataStudent );
			}

			return new Collection( $collectionMentee );
		}
	}

	public function getMenteesAttribute()
	{
		return $this->getStudentsAttribute();
	}

	public function getCredentialsAttribute()
	{
		$listCredentialIds = $this->attributes['credentials'];

		if( $listCredentialIds )
		{
			$credentials = [];
			foreach( $listCredentialIds as $id )
			{
				$credentials[] = Credential::where( ['is_active' => true] )->findOrFail( $id );
			}

			return $credentials;
		}
	}

	public function getMentorshipTopicsAttribute()
	{
		$listMentorshipTopicIds = $this->attributes['mentorship_topics'];

		if( $listMentorshipTopicIds )
		{
			$mentorshipTopics = [];
			foreach( $listMentorshipTopicIds as $id )
			{
				$mentorshipTopics[] = MentorshipTopic::where( ['is_active' => true] )->findOrFail( $id );
			}

			return $mentorshipTopics;
		}
	}

	public function getYearsInPracticeAttribute()
	{
		$idPracticeYears = $this->attributes['years_in_practice'];
		if( $idPracticeYears )
		{
			return PracticeYears::where( ['is_active' => true] )->findOrFail( $idPracticeYears );
		}
	}

	public static function getCredentialIdsFromDegreesString( $degrees )
	{
		$credentials = [];

		$listCredentialtring = explode( ', ', $degrees);

		foreach( $listCredentialtring as $credentialString )
		{
			$credentialMatches = Credential::where( 'name', $credentialString )->where( 'is_active', true )->get();
			if( count( $credentialMatches ) === 1 )
			{
				$credentials[] = $credentialMatches[0]->id;
			}
			else
			{
				// try again without periods
				$credentialString = str_replace( '.', '', $credentialString );
				$credentialMatches = Credential::where( 'name', $credentialString )->where( 'is_active', true )->get();
				if( count( $credentialMatches ) === 1 )
				{
					$credentials[] = $credentialMatches[0]->id;
				}
			}
		}

		return $credentials;
	}

	/**
	 * get pending requests attached to this mentor
	 *
	 * @return Models\MentorshipRequest
	 */
	public function getMentorshipRequests()
	{
		$requests = MentorshipRequest::where( [ 'mentor_id' => $this->id ] )->get();
		return $requests;
	}

	/**
	 * get pending requests attached to this mentor
	 *
	 * @return Models\MentorshipRequest
	 */
	public function getPendingMentorshipRequests()
	{
		$requests = MentorshipRequest::where( [ 'mentor_id' => $this->id, 'acceptance_indicator' => 3 ] )->get();
		return $requests;
	}
	
	/**
	 * create Mentor instance from User instance
	 *
	 * @param User $user an instance of App\Models\User
	 * @return App\Models\Mentor
	 */
	public static function createFromUser( User $user )
	{
		$attributesUser = $user->getAttributes();

		$attributesMentor = array();
		$attributesMentor['unid'] = $attributesUser['userunid'];
		$attributesMentor['name'] = ( $attributesUser['namefirstpreferred'] ?? $attributesUser['namefirst'] ) . ' ' . $attributesUser['namelast'];
		$attributesMentor['credentials'] = self::getCredentialIdsFromDegreesString( $attributesUser['degrees'] );

		if( $attributesUser['primary_rank'] && strlen( $attributesUser['primary_rank'] ) )
		{
			// translate academic rank string to the syntax we use in our database
			if( preg_match( '/((\w+ ?)+)\(((\w+ ?)+)\)/', $attributesUser['primary_rank'] ) ) // e.g., "Assistant Professor (Clinical)"
			{
				$attributesUser['primary_rank'] = preg_replace( '/((\w+ ?)+)\(((\w+ ?)+)\)/', '$1– $3', $attributesUser['primary_rank'] ); // ... becomes "Assistant Professor – Clinical".
			}

			$academicRank = AcademicRank::where( [ 'name' => $attributesUser['primary_rank'] ] )->where( 'is_active', true )->get();

			if( count( $academicRank ) === 1 )
			{
				$attributesMentor['academic_rank'] = $academicRank[0]->id;
			}
		}
		
		if( $attributesUser['primary_dept'] && strlen( $attributesUser['primary_dept'] ) )
		{
			$department = Department::where( [ 'name' => $attributesUser['primary_dept'] ] )->where( 'is_active', true )->get();

			if( count( $department ) === 1 )
			{
				$attributesMentor['department'] = $department[0]->id;

				if( $attributesUser['primary_division'] && strlen( $attributesUser['primary_division'] ) )
				{
					$division = Division::where( [ 'name' => $attributesUser['primary_division'] ] )->where( 'is_active', true )->get();

					if( count( $division ) === 1 )
					{
						$attributesMentor['division'] = $division[0]->id;
					}
				}
			}
		}

		$attributesMentor['contact_email'] = $attributesUser['useremail'];
		$attributesMentor['mentee_capacity'] = 0;

		return self::create( $attributesMentor );
	}

	/**
	 * @param array $attributes
	 *
	 * @return $this
	 *
	 * @throws ApiException
	 */
	public function update(array $attributes = [])
	{
		return parent::update( $attributes );
	}

	/**
	 * remove mentee from mentor
	 */
	public function removeMentee( Mentee $mentee )
	{
		$this->clearCache();
		return $this->getApi()->removeMentee( $this->attributes['id'], $mentee->id );
	}
}
