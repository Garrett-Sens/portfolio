<?php

/**
 * SomGeneticStudent_IndexController
 * @author Garrett Sens
 * 
 * This controller controls layout, response handling, and database interactions for the student side of our case logging application for our Genetics students. 
 */

class SomGeneticStudent_IndexController extends Edu_Utah_Som_Controller_Action
{
	private $manager;
	private $isSomGenetic;
	private $typesUser;
	
	public function preDispatch()
	{
		Zend_Loader::loadClass( 'SomGeneticStudent_Model_ApplicationManager' );
		$this->manager = SomGeneticStudent_Model_ApplicationManager::getInstance();
		$this->manager->initView( $this->view );

		$request = $this->getRequest();

		//disable layout and views if the request is an ajax request
		if($request->isXmlHttpRequest())
		{
			$this->_helper->layout()->disableLayout();
			$this->_helper->viewRenderer->setNoRender(true);

			$contextSwitch = $this->getHelper('ContextSwitch');
			$contextSwitch->addActionContext($request->getActionName(),array('json'));
			$contextSwitch->setAutoJsonSerialization(true);
			$contextSwitch->initContext('json');
		}

		Zend_Loader::loadClass( 'Zend_Auth' );

		$auth = Zend_Auth::getInstance();
		$identity = $auth->getIdentity();
		$identityFields = $identity->toArray();
		$idUser = $identityFields['userId'];

		// generate academic year string
		$this->yearAcademic = SomGeneticStudent_Model_ApplicationManager::getCurrentAcademicYear();

		// temp
		// $this->yearAcademic = '2020/2021';

		$this->view->yearAcademic = $this->yearAcademic;

		$this->idUser = $idUser;
		$this->view->idUser = $idUser;

		$this->typesUser = $this->manager->typesUser;
		$this->view->typesUser = $this->typesUser;

		// if this property wasn't first set by SomGenetic/IndexController, then set it here
		$this->isSomGenetic = false; // used by this application to determine if this request is from a student or an admin/supervisor
		$this->view->isSomGenetic = $this->isSomGenetic;
	}

	
	public function indexAction()
	{
		
	}


	public function caseLogListAction()
	{
		$layout = Zend_Layout::getMvcInstance();
		$layout->setLayout( 'garrettColumn2' );
		
		Zend_Loader::loadClass( 'Zend_Auth' );

		$auth = Zend_Auth::getInstance();
		$identity = $auth->getIdentity();
		$identityFields = $identity->toArray();

		$applicationUserId = $identityFields['userId'];

		$isProduction = false;
		if( defined( 'APP_CONFIG_INI' ) && APP_CONFIG_INI === 'production' )
		{
			$isProduction = true;
		}

		$year = intval( substr( $this->yearAcademic, 0, 4 ) );
		if( $year > 2019 )
		{
			$is2020Form = true;
		}
		else
		{
			$is2020Form = false;
		}

		$this->view->applicationUserId = $applicationUserId;
		$this->view->isProduction = $isProduction;
		$this->view->is2020Form = $is2020Form;
	}


	private function caseLogForm( $is2020Form )
	{
		$layout = Zend_Layout::getMvcInstance();
		$layout->setLayout( '2columnNavigationMainNewPrint' );
		
		Zend_Loader::loadClass('SomCurriculum_Model_ApplicationManager');

		$request = $this->getRequest();

		$adapterSomStudentAffairs = $this->manager->getDatabase( SomGeneticStudent_Model_ApplicationManager::DATABASE_TYPE_STUDENTAFFAIRS );

		$params = $request->getParams();
		$isEdit = isset( $params['id'] ); // if $isEdit is true, then this is case log has been created and saved by the student. New case log entry urls have no 'id' parameter.

		$isAcknowledgedByStudent = false;
		$isAcknowledgedBySupervisor = false;

		if( $request->isPost() )
		{
			$post = $request->getPost();

			// var_dump( $post );
			// exit;

			if( isset( $post['SUBMIT'] ) )
			{
				switch( $post['SUBMIT'] )
				{
					// if student is saving the page
					case 'SAVE':
					case 'STUDENT_ACKNOWLEDGE': // save the page again before acknowledging
						$post['ACADEMIC_YEAR'] = $this->yearAcademic;
						$post['INSERTED_BY'] = $this->idUser;
						$post['STUDENT_ID'] = $this->idUser;

						$idCaseLog = $this->createOrEditCaseLog( $post, $adapterSomStudentAffairs, $isEdit, $params );

						// if first time saving
						if( $isEdit === false  )
						{
							// get new case log id
							if( $idCaseLog !== null )
							{
								$tableGpgcCaselog = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog' );
								$listCaseLog = $tableGpgcCaselog->select(
									array(
										'where' => array(
											'CASELOG_ID' => $idCaseLog
										),
										'dateformats' => array(
											'ACTIVITY_DATE' => 'DEFAULT'
										)
									)
								);

								// refresh the page with id param so we can enter edit mode
								if( $is2020Form )
								{
									$this->_helper->redirector( 'case-log-2020', 'index', null, array( 'id' => $listCaseLog[0]['CASELOG_IDENCRYPTED'] ) );
								}
								else
								{
									$this->_helper->redirector( 'case-log', 'index', null, array( 'id' => $listCaseLog[0]['CASELOG_IDENCRYPTED'] ) );
								}

								exit;
							}
							// if first time saving, but failed initial validation, then populate the page
							else
							{
								$this->view->dataGpgcCaseLog = $post;
							}
						}

						// if not acknowledging, skip following section
						if( $post['SUBMIT'] === 'SAVE' )
						{
							break;
						}

						// if student is acknowledging the case log
						// case 'STUDENT_ACKNOWLEDGE':
						$viewGpgcCaselog = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'view_gpgc_caselog' );
						$listCaseLog = $viewGpgcCaselog->select(
							array(
								'columns' => array(
									'CASE_CATEGORY_TYPE_NAME'
								),
								'where' => array(
									'CASELOG_ID' => $idCaseLog
								),
								'dateformats' => array(
									'ACTIVITY_DATE' => 'DEFAULT'
								)
							)
						);
						$dataCaseLog = $listCaseLog[0];

						$isValid = $this->validateCaseLogForm( $post, $dataCaseLog, $is2020Form );

						if( $isValid === true )
						{
							$datetime = new DateTime();
							$dateNow = $datetime->format('Y-m-d H:i:s');

							$tableGpgcCaselog = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog' );
							$returnUpdateGpgcCaselog = $tableGpgcCaselog->update(
								array(
									'data' => array(
										'STUDENT_ACKNOWLEDGE_DATE' => $dateNow
									),
									'where' => array(
										'CASELOG_IDENCRYPTED' => $params['id']
									),
									'dateformats' => array(
										'ACTIVITY_DATE' => 'DEFAULT'
									)
								)
							);

							// go to case log list page after acknowledging
							$this->_helper->redirector( 'case-log-list', 'index' );
						}

						// $this->view->doCloseWindow = true;
						// return;

					break;

					// if supervisor is acknowledging the case log
					case 'SUPERVISOR_ACKNOWLEDGE':

						$datetime = new DateTime();
						$dateNow = $datetime->format('Y-m-d H:i:s');

						$tableGpgcCaselog = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog' );
						$returnUpdateGpgcCaselog = $tableGpgcCaselog->update(
							array(
								'data' => array(
									'SUPERVISOR_ACKNOWLEDGE_DATE' => $dateNow,
									'SUPERVISOR_REJECTION_MESSAGE' => $post['SUPERVISOR_REJECTION_MESSAGE']
								),
								'where' => array(
									'CASELOG_IDENCRYPTED' => $params['id']
								)
							)
						);

						// go to case log list page after acknowledging
						$this->_helper->redirector( 'case-log-list', 'index' );

						// $this->view->doCloseWindow = true;
						// return;

					break;

					// if supervisor is rejecting the case log
					case 'SUPERVISOR_REJECT':
						$tableGpgcCaselog = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog' );
						$returnUpdateGpgcCaselog = $tableGpgcCaselog->update(
							array(
								'data' => array(
									'STUDENT_ACKNOWLEDGE_DATE' => null,
									'SUPERVISOR_ACKNOWLEDGE_DATE' => null,
									'SUPERVISOR_REJECTION_MESSAGE' => $post['SUPERVISOR_REJECTION_MESSAGE']
								),
								'where' => array(
									'CASELOG_IDENCRYPTED' => $params['id']
								)
							)
						);

						// go to case log list page after acknowledging
						$this->_helper->redirector( 'case-log-list', 'index' );

					break;
				}
			}
		}

		// 
		// populate form if editing an existing log
		// 

		$params = $request->getParams();

		$yearAcademic = $this->yearAcademic;
		if( $isEdit === true )
		{
			// 
			// get data for this case log from db
			// 
			if( $is2020Form )
			{
				$viewGpgcCaselog = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'view_gpgc_caselog_2020' );
			}
			else
			{
				$viewGpgcCaselog = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'view_gpgc_caselog' );
			}

			$listGpgcCaseLog = $viewGpgcCaselog->select(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					),
					'dateformats' => array(
						'ACTIVITY_DATE' => 'MM/DD/YYYY'
					)
				)
			);

			// var_dump( $listGpgcCaseLog ); exit;

			if( count( $listGpgcCaseLog ) !== 1 )
			{
				throw new Exception( "Unable to retrieve this case log. Please contact School of Medicine IT for assistance." );
			}

			$dataGpgcCaseLog = $listGpgcCaseLog[0];

			$yearAcademic = $dataGpgcCaseLog['ACADEMIC_YEAR']; // this is used later

			if( strlen( $dataGpgcCaseLog['STUDENT_ACKNOWLEDGE_DATE'] ) > 0 )
			{
				$isAcknowledgedByStudent = true;
			}
			
			if( strlen( $dataGpgcCaseLog['SUPERVISOR_ACKNOWLEDGE_DATE'] ) > 0 )
			{
				$isAcknowledgedBySupervisor = true;
			}

			// 
			// get data for this case log's notes from db
			// 
			$viewGpgcNote = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'view_gpgc_note' );
			$listGpgcNote = $viewGpgcNote->select(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);

			// if rejected by supervisor and student must reacknowledge, then show rejection message from supervisor
			// if( $this->view->isAcknowledgedByStudent === false && !empty( $dataGpgcCaseLog['SUPERVISOR_REJECTION_MESSAGE'] ) )
			// {
			// 	$this->view->messageDisplayer()->message(
			// 		array(
			// 			'messagetype' => 'ERROR',
			// 			'messagetitle' => 'Rejected',
			// 			'messagetext' => '<pre>' . $dataGpgcCaseLog['SUPERVISOR_REJECTION_MESSAGE'] . '</pre>'
			// 		)
			// 	);
			// 	$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			// }
			// var_dump($dataGpgcCaseLog); exit;
			$this->view->dataGpgcCaseLog = $dataGpgcCaseLog;
			$this->view->listGpgcNote = $listGpgcNote;
		}


		//get locations
		$viewGpgcLocation = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','view_gpgc_location');
		$listLocations = $viewGpgcLocation->select(
			array(
				'where' => array(
					'ACADEMIC_YEAR' => $yearAcademic
				),
				'order' =>array('LOCATIONNAME')
			)
		);

		$optionsLocation = array(
			'' => ''
		);

		foreach( $listLocations as $dataLocation )
		{
			$optionsLocation[$dataLocation['GPGC_LOCATION_IDENCRYPTED'] . ''] = $dataLocation['LOCATIONNAME']; // make keys strings so they maintain location name order
		}

		//get supervisor names
		$viewGpgcSupervisor = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','view_gpgc_supervisor');
		$listGpgcSupervisor = $viewGpgcSupervisor->select(
			array(
				'columns' => array(
					'GPGC_SUPERVISOR_ID',
					'NAMELAST',
					'NAMEFIRST'
				),
				'where' => array(
					'ACADEMIC_YEAR' => $yearAcademic
				),
				'order' => array( 'NAMELAST', 'NAMEFIRST' )
			)
		);

		$optionsSupervisor = array(
			'' => ''
		);

		foreach( $listGpgcSupervisor as $dataSupervisor )
		{
			$optionsSupervisor[$dataSupervisor['GPGC_SUPERVISOR_IDENCRYPTED'] . ''] = $dataSupervisor['NAMELAST'] . ', ' . $dataSupervisor['NAMEFIRST']; // make keys strings so they maintain location name 
		}

		// get case category types
		$tableGpgcCaseCategoryType = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_case_category_type' );
		$listGpgcCaseCategoryType = $tableGpgcCaseCategoryType->select(
			array(
				'where' => array(
					'ACADEMIC_YEAR' => $yearAcademic
				),
				'order' =>array('CASE_CATEGORY_TYPE_ORDER')
			)
		);

		$optionsCaseCategoryType = array();
		foreach( $listGpgcCaseCategoryType as $dataGpgcCaseCategoryType )
		{
			$optionsCaseCategoryType[$dataGpgcCaseCategoryType['CASE_CATEGORY_TYPE_IDENCRYPTED']] = $dataGpgcCaseCategoryType['CASE_CATEGORY_TYPE_NAME'];
		}

		// get encounters
		// var_dump($adapterSomStudentAffairs); exit;
		$tableGpgcEncounterType = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_encounter_type');

		$listEncounterType = $tableGpgcEncounterType->select(
			array(
				'where' => array(
					'IS_ACTIVE' => 1,
					'ACADEMIC_YEAR' => $yearAcademic
				),
				'order' =>array('ENCOUNTER_TYPE_ORDER')
			)
		);

		$optionsEncounterType = array();
		foreach( $listEncounterType as $dataEncounterType )
		{
			$optionsEncounterType[$dataEncounterType['ENCOUNTER_TYPE_ID']] = $dataEncounterType['ENCOUNTER_TYPE_NAME'];
		}
		// var_dump($optionsEncounterType); exit;

		// get case types
		// var_dump($adapterSomStudentAffairs); exit;
		$tableGpgcCaseType = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_case_type');

		$listCaseType = $tableGpgcCaseType->select(
			array(
				'where' => array(
					'ACADEMIC_YEAR' => $yearAcademic
				),
				'order' => array('CASE_TYPE_ORDER')
			)
		);
		// var_dump($listCaseType); exit;

		$optionsCaseType = array();
		foreach( $listCaseType as $dataCaseType )
		{
			$optionsCaseType[$dataCaseType['CASE_TYPE_IDENCRYPTED']] = $dataCaseType['CASE_TYPE_NAME'];
		}


		// get specialty types
		// var_dump($adapterSomStudentAffairs); exit;
		$tableGpgcSpecialtyType = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_specialty_type');

		$listSpecialtyType = $tableGpgcSpecialtyType->select(
			array(
				'where' => array(
					'ACADEMIC_YEAR' => $yearAcademic
				),
				'order' => array('SPECIALTY_TYPE_ORDER')
			)
		);
		// var_dump($listSpecialtyType); exit;

		$optionsSpecialtyType = array();
		foreach( $listSpecialtyType as $dataSpecialtyType )
		{
			$optionsSpecialtyType[$dataSpecialtyType['SPECIALTY_TYPE_ID']] = $dataSpecialtyType['SPECIALTY_TYPE_NAME'];
		}

		if( $is2020Form )
		{
			// get practice setting types
			// var_dump($adapterSomStudentAffairs); exit;
			$tableGpgcPracticeSettingType = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_practice_setting_type');

			$listPracticeSettingType = $tableGpgcPracticeSettingType->select(
				array(
					'where' => array(
						'ACADEMIC_YEAR' => $yearAcademic
					),
					'order' => array('PRACTICE_SETTING_TYPE_ORDER')
				)
			);
			// var_dump($listPracticeSettingType); exit;

			$optionsPracticeSettingType = array();
			foreach( $listPracticeSettingType as $dataPracticeSettingType )
			{
				$optionsPracticeSettingType[$dataPracticeSettingType['PRACTICE_SETTING_TYPE_ID']] = $dataPracticeSettingType['PRACTICE_SETTING_TYPE_NAME'];
			}


			// get lifecycle
			// var_dump($adapterSomStudentAffairs); exit;
			$tableGpgcLifecycle = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_lifecycle');

			$listLifecycle = $tableGpgcLifecycle->select(
				array(
					'where' => array(
						'ACADEMIC_YEAR' => $yearAcademic
					),
					'order' => array('LIFECYCLE_ORDER')
				)
			);
			// var_dump($listLifecycle); exit;

			$optionsLifecycle = array();
			foreach( $listLifecycle as $dataLifecycle )
			{
				$optionsLifecycle[$dataLifecycle['LIFECYCLE_ID']] = $dataLifecycle['LIFECYCLE_NAME'];
			}

			// get ethnicity reference data
			// var_dump($adapterSomStudentAffairs); exit;
			$tableGpgcEthnicity = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_ethnicity');

			$listEthnicity = $tableGpgcEthnicity->select(
				array(
					'where' => array(
						'ACADEMIC_YEAR' => $yearAcademic
					),
					'order' => array('ETHNICITY_ORDER')
				)
			);
			// var_dump($listEthnicity); exit;

			$optionsEthnicity = array();
			foreach( $listEthnicity as $dataEthnicity )
			{
				$optionsEthnicity[$dataEthnicity['ETHNICITY_ID']] = $dataEthnicity['ETHNICITY_NAME'];
			}


			// get pbc domains
			// var_dump($adapterSomStudentAffairs); exit;
			$tableGpgPBCDomainType = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_pbc_domain_type');

			$listPBCDomainType = $tableGpgPBCDomainType->select(
				array(
					'where' => array(
						'ACADEMIC_YEAR' => $yearAcademic
					),
					'order' => array('PBC_DOMAIN_TYPE_ORDER')
				)
			);
			// var_dump($listPBCDomain); exit;

			$optionsPBCDomainType = array();
			foreach( $listPBCDomainType as $dataPBCDomainType )
			{
				$optionsPBCDomainType[$dataPBCDomainType['PBC_DOMAIN_TYPE_ID']] = $dataPBCDomainType['PBC_DOMAIN_TYPE_NUMBER'] . ': ' . $dataPBCDomainType['PBC_DOMAIN_TYPE_NAME'];
			}

			$this->view->optionsPracticeSettingType = $optionsPracticeSettingType;
			$this->view->optionsLifecycle = $optionsLifecycle;
			$this->view->optionsEthnicity = $optionsEthnicity;
			$this->view->optionsPBCDomainType = $optionsPBCDomainType;
		}
		else
		{
			// get roles
			$viewGpgcRole = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','view_gpgc_role');
			$listRole = $viewGpgcRole->select(
				array(
					'where' => array(
						'ACADEMIC_YEAR' => $yearAcademic
					),
					'order' => array( 'ROLE_TYPE_ORDER', 'ROLE_ORDER' )
				)
			);

			// divide roles into separate arrays, one for each ROLE_TYPE
			$listRoleByType = array();
			foreach( $listRole as $i => $dataRole )
			{
				if( !isset( $listRoleByType[$dataRole['ROLE_TYPE_NAME']] ) )
				{
					$listRoleByType[$dataRole['ROLE_TYPE_NAME']] = array();
				}

				$listRoleByType[$dataRole['ROLE_TYPE_NAME']][] = $dataRole;
			}
			// var_dump( $listGpgcCaseLog ); exit;

			$this->view->listRoleByType = $listRoleByType;
		}

		// lock form if admin/supervisor or if student who acknowledged the form
		$isStudentAcknowledging = false;
		$isSupervisorAcknowledging = false;

		if( $this->isSomGenetic )
		{
			$isSupervisorAcknowledging = $isAcknowledgedByStudent && !$isAcknowledgedBySupervisor;
		}
		else if( !$isAcknowledgedByStudent )
		{
			$isStudentAcknowledging = true;
		}

		// $isFormLocked = $this->isSomGenetic || $isAcknowledgedByStudent;
		$isFormLocked = $isAcknowledgedByStudent;

		// var_dump( $this->isSomGenetic, $isStudentAcknowledging, $isAcknowledgedByStudent, $isSupervisorAcknowledging, $isAcknowledgedBySupervisor ); exit;

		$this->view->isStudentAcknowledging = $isStudentAcknowledging;
		$this->view->isAcknowledgedByStudent = $isAcknowledgedByStudent;
		$this->view->isSupervisorAcknowledging = $isSupervisorAcknowledging;
		$this->view->isAcknowledgedBySupervisor = $isAcknowledgedBySupervisor; // keep this. it is used by old case-log.phtml. 
		$this->view->isFormLocked = $isFormLocked;
		$this->view->isEdit = $isEdit;
		$this->view->listEncounterType = $listEncounterType;
		$this->view->listCaseType = $listCaseType;
		$this->view->optionsLocation = $optionsLocation;
		$this->view->optionsSupervisor = $optionsSupervisor;
		$this->view->optionsEncounterType = $optionsEncounterType;
		$this->view->optionsCaseCategoryType = $optionsCaseCategoryType;
		$this->view->optionsCaseType = $optionsCaseType;
		$this->view->optionsSpecialtyType = $optionsSpecialtyType;
		$this->view->yearAcademic = $this->yearAcademic;
	}


	public function caseLogAction( $isSomGenetic = false )
	{
		$this->isSomGenetic = $isSomGenetic;
		$this->caseLogForm( false );
	}
	

	public function caseLog2020Action( $isSomGenetic = false )
	{
		$this->isSomGenetic = $isSomGenetic;
		$this->caseLogForm( true );
	}


	public function clinicalCaseReportAction()
	{
		$layout = Zend_Layout::getMvcInstance();
		$layout->setLayout( 'garrettColumn2' );
	}


	private function createOrEditCaseLog( $post, $adapterSomStudentAffairs, $isEdit, $params )
	{
		if( empty( $post['ACTIVITY_DATE'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>'The "Date" field is required.'
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return;
		}

		$tableGpgcCaselog = new Edu_Utah_Som_Data_OracleTable($adapterSomStudentAffairs,'somcme','gpgc_caselog');

		if( $isEdit === true )
		{
			//
			// update case log
			//

			// don't change academic year (in case you save a case log in a different academic year than you made it)
			unset( $post['ACADEMIC_YEAR'] );

			$returnUpdateGpgcCaselog = $tableGpgcCaselog->update(
				array(
					'data' => $post,
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					),
					'dateformats' => array(
						'ACTIVITY_DATE' => 'MM/DD/YYYY'
					)
				)
			);

			$post['CASELOG_IDENCRYPTED'] = $params['id'];

			// get new case log id
			$listCaseLog = $tableGpgcCaselog->select(
				array(
					'columns' => array( 'CASELOG_ID' ),
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);

			$idCaseLog = $listCaseLog[0]['CASELOG_ID'];
		}
		else
		{
			//
			// insert case log
			//

			$returnInsertGpgcCaselog = $tableGpgcCaselog->insert(
				array(
					'data' => $post,
					'dateformats' => array(
						'ACTIVITY_DATE' => 'MM/DD/YYYY'
					)
				)
			);

			$idCaseLog = $returnInsertGpgcCaselog[0]['id'];
			$post['CASELOG_ID'] = $idCaseLog;
		}


		//
		// insert case log encounters
		//

		$tableGpgcCaselogEncounter = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog_encounter' );

		if( $isEdit === true )
		{
			$tableGpgcCaselogEncounter->delete(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);
		}
		
		if( !empty( $post['ENCOUNTER_TYPE_ID'] ) )
		{
			$returnInsertGpgcCaselogEncounter = $tableGpgcCaselogEncounter->insert(
				array(
					'data' => $post
				)
			);
		}


		//
		// insert case log specialties
		//

		// var_dump( $post, $params ); exit;
		
		$tableGpgcCaselogSpecialty = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog_specialty' );

		if( $isEdit === true )
		{
			$tableGpgcCaselogSpecialty->delete(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);
		}
		

		if( !empty( $post['SPECIALTY_TYPE_ID'] ) )
		{
			$returnGpgcCaselogSpecialty = $tableGpgcCaselogSpecialty->insert(
				array(
					'data' => $post
				)
			);
		}



		//
		// insert case log practice settings
		//

		// var_dump( $post, $params ); exit;
		
		$tableGpgcCaselogPractice = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog_practice' );

		if( $isEdit === true )
		{
			$tableGpgcCaselogPractice->delete(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);
		}
		

		if( !empty( $post['PRACTICE_SETTING_TYPE_ID'] ) )
		{
			$returnInsertGpgcCaselogPractice = $tableGpgcCaselogPractice->insert(
				array(
					'data' => $post
				)
			);
		}


		//
		// insert case log pbc domains
		//

		// var_dump( $post, $params ); exit;
		
		$tableGpgcCaselogPBCDomain = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog_pbc_domain' );

		if( $isEdit === true )
		{
			$tableGpgcCaselogPBCDomain->delete(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);
		}
		
		// var_dump( $post );
		if( !empty( $post['PBC_DOMAIN_TYPE_ID'] ) )
		{
			$returnInsertGpgcCaselogPractice = $tableGpgcCaselogPBCDomain->insert(
				array(
					'data' => $post
				)
			);

			// var_dump( $returnInsertGpgcCaselogPractice ); exit;
		}

		//
		// insert case log ethnicities
		//

		// var_dump( $post, $params ); exit;
		
		$tableGpgcCaselogEthnicity = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_caselog_ethnicity' );

		if( $isEdit === true )
		{
			$tableGpgcCaselogEthnicity->delete(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);
		}
		
		// var_dump( $post );
		if( !empty( $post['ETHNICITY_ID'] ) )
		{
			$returnInsertGpgcCaselogEthnicity = $tableGpgcCaselogEthnicity->insert(
				array(
					'data' => $post
				)
			);

			// var_dump( $returnInsertGpgcCaselogPractice ); exit;
		}


		//
		// notes
		//

		$tableGpgcNote = new Edu_Utah_Som_Data_OracleTable( $adapterSomStudentAffairs, 'somcme', 'gpgc_note' );

		if( $isEdit === true )
		{
			$tableGpgcNote->delete(
				array(
					'where' => array(
						'CASELOG_IDENCRYPTED' => $params['id']
					)
				)
			);
		}

		//iterate over the post looking for NOTE_TEXT_ values.
		foreach( $post as $key => $value )
		{
			//check if the input name has NOTE_TEXT_
			$roleIdPosition = strpos( $key, 'NOTE_TEXT_' );

			if( $roleIdPosition !== false )
			{
				//get everything after the NOTE_TEXT_ part
				$roleId = substr( $key, 10 );
				if( !empty( $value ) )
				{
					if( $isEdit === true )
					{
						$tableGpgcNote->insert(
							array(
								'data' => array(
									'CASELOG_IDENCRYPTED' => $params['id'],
									'NOTE_TEXT' => $value,
									'ROLE_ID' => $roleId
								)
							)
						);
					}
					else
					{
						$tableGpgcNote->insert(
							array(
								'data' => array(
									'CASELOG_ID' => $idCaseLog,
									'NOTE_TEXT' => $value,
									'ROLE_ID' => $roleId
								)
							)
						);
					}
				}
			}
		}

		return $idCaseLog;
	}


	private function validateCaseLogForm( $post, $dataCaseLog, $is2020Form )
	{
		$dataMessage = array();

		if( $is2020Form )
		{
			$dataMessage = array(
				'ACTIVITY_DATE' => 'The "Case Date" field is required.',
				'LOCATION_IDENCRYPTED' => 'The "Fieldwork Site" field is required.',
				'SUPERVISOR_IDENCRYPTED' => 'The "Fieldwork Supervisor" field is required.',
				'CASE_CATEGORY_TYPE_IDENCRYPTED' => 'The "Case Category" field is required.',
				'ENCOUNTER_TYPE_ID' => 'The "Service Delivery Model" field is required.',
				'CASE_TYPE_IDENCRYPTED' => 'The "Case Type" field is required.',
				'SPECIALTY_TYPE_ID' => 'The "Specialties" field is required.',
				'PRACTICE_SETTING_TYPE_ID' => 'The "Practice Settings" field is required.',
				'LIFECYCLE_ID' => 'The "Stage of Lifecycle" field is required.',
				'IS_INTERPRETER_NEEDED' => 'The "Did patient require an interpreter?" field is required.',
				'ETHNICITY_ID' => 'The "Ethnicity/Race" field is required.',
				'PBC_DOMAIN_TYPE_ID' => 'When Case Category is not set to "Other", at least two PBC Domains are required.',
				'IS_PARTICIPATORY_CASE' => 'The "Does this case fulfill the definition of a participatory case?" field is required.',
				'CASELOG_DIAGNOSIS' => 'The "Diagnosis/Indication" field is required.'
			);
		}
		else
		{
			$dataMessage = array(
				'ACTIVITY_DATE' => 'The "Date" field is required.',
				'LOCATION_IDENCRYPTED' => 'The "Clinic" field is required.',
				'SUPERVISOR_IDENCRYPTED' => 'The "Case Supervisor" field is required.',
				'ENCOUNTER_TYPE_ID' => 'The "Encounter Type" field is required.',
				'CASE_CATEGORY_TYPE_IDENCRYPTED' => 'The "Case Category" field is required.',
				'CASE_TYPE_IDENCRYPTED' => 'The "Case Type" field is required.',
				'SPECIALTY_TYPE_ID' => 'The "Specialty Type" field is required.',
				'CASELOG_DIAGNOSIS' => 'The "Diagnosis/Indication" field is required.'
			);
		}

		if( empty( $post['ACTIVITY_DATE'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['ACTIVITY_DATE']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		if( empty( $post['LOCATION_IDENCRYPTED'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['LOCATION_IDENCRYPTED']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		if( empty( $post['SUPERVISOR_IDENCRYPTED'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['SUPERVISOR_IDENCRYPTED']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		if( empty( $post['CASE_CATEGORY_TYPE_IDENCRYPTED'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['CASE_CATEGORY_TYPE_IDENCRYPTED']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		if( empty( $post['ENCOUNTER_TYPE_ID'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['ENCOUNTER_TYPE_ID']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		if( empty( $post['CASE_TYPE_IDENCRYPTED'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['CASE_TYPE_IDENCRYPTED']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		if( empty( $post['SPECIALTY_TYPE_ID'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['SPECIALTY_TYPE_ID']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		if( $is2020Form )
		{
			if( empty( $post['PRACTICE_SETTING_TYPE_ID'] ) )
			{
				$this->view->messageDisplayer()->message(
					array(
						'messagetype'=>'ERROR',
						'messagetitle'=>'Error',
						'messagetext'=>$dataMessage['PRACTICE_SETTING_TYPE_ID']
					)
				);
				$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
				return false;
			}

			if( empty( $post['LIFECYCLE_ID'] ) )
			{
				$this->view->messageDisplayer()->message(
					array(
						'messagetype'=>'ERROR',
						'messagetitle'=>'Error',
						'messagetext'=>$dataMessage['LIFECYCLE_ID']
					)
				);
				$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
				return false;
			}

			if( !isset( $post['IS_INTERPRETER_NEEDED'] ) )
			{
				$this->view->messageDisplayer()->message(
					array(
						'messagetype'=>'ERROR',
						'messagetitle'=>'Error',
						'messagetext'=>$dataMessage['IS_INTERPRETER_NEEDED']
					)
				);
				$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
				return false;
			}

			if( empty( $post['ETHNICITY_ID'] ) )
			{
				$this->view->messageDisplayer()->message(
					array(
						'messagetype'=>'ERROR',
						'messagetitle'=>'Error',
						'messagetext'=>$dataMessage['ETHNICITY_ID']
					)
				);
				$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
				return false;
			}

			if( $dataCaseLog['CASE_CATEGORY_TYPE_NAME'] !== 'Other' )
			{
				if( empty( $post['PBC_DOMAIN_TYPE_ID'] ) || count( $post['PBC_DOMAIN_TYPE_ID'] ) < 2 )
				{
					$this->view->messageDisplayer()->message(
						array(
							'messagetype'=>'ERROR',
							'messagetitle'=>'Error',
							'messagetext'=>$dataMessage['PBC_DOMAIN_TYPE_ID']
						)
					);
					$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
					return false;
				}
			}
			
			if( !isset( $post['IS_PARTICIPATORY_CASE'] ) )
			{
				$this->view->messageDisplayer()->message(
					array(
						'messagetype'=>'ERROR',
						'messagetitle'=>'Error',
						'messagetext'=>$dataMessage['IS_PARTICIPATORY_CASE']
					)
				);
				$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
				return false;
			}
		}

		if( empty( $post['CASELOG_DIAGNOSIS'] ) )
		{
			$this->view->messageDisplayer()->message(
				array(
					'messagetype'=>'ERROR',
					'messagetitle'=>'Error',
					'messagetext'=>$dataMessage['CASELOG_DIAGNOSIS']
				)
			);
			$this->view->placeholder('column-main-header')->append($this->view->messageDisplayer()); // show error messages
			return false;
		}

		return true;
	}

}

?>
