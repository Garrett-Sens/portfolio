<?php

/**
 * Edu_Utah_Som_Data_OracleTable
 * @author Garrett Sens
 * 
 * The Zend_Db_Table class acts as an object-oriented interface to database tables. This class both extends and simplifies Zend_Db_Table's methods when working with Oracle SQL databases. 
 */

/*
 * Instructions
 *
TABLE INSTANTIATION

	Zend_Loader::loadClass( 'Edu_Utah_Som_Data_OracleTable' );

	$table = new Edu_Utah_Som_Data_OracleTable(
		$this->manager->getDatabase( SomDental_Model_ApplicationManager::DATABASE_TYPE_ACL ), 'SOMADMINISTRATION',
		'APPLICATIONUSER'
	);


SELECT

1.

This PHP command

	$table->select(
		array(
			'where' => array(
				'APPLICATIONUSERID' => 'APPU7030832'	
			)
		)
	);

creates this SQL query:

	SELECT "APPLICATIONUSER".* FROM "SOMADMINISTRATION"."APPLICATIONUSER" WHERE (APPLICATIONUSERID = 'APPU7030832')

and returns this array:
	array(
		0 => array(
			'APPLICATIONUSERID' => 'APPU7030832',
			'USEREMAIL' => 'garrett.sens@hsc.utah.edu',
			'USERUNID' => 'u6005058'
			'NAMEFIRST' => 'Garrett'
			'NAMEMIDDLE' => null
			'NAMELAST' => 'Sens'
			'PHONEHOME' => null
			'PHONEMOBILE' => '801-822-1096'
			'PHONEOFFICE' => null
			'ISACTIVE' => '1'
			'DATEENTERED' => '01-DEC-15'
			'AAMCID' => null
			'PAGER' => null
			'USERPREFIX' => null
			'USERSUFFIX' => null
		)
	)


2.

This PHP command

	$table->select(
		array(
			'columns' => array( 'NAMEFIRST', 'NAMELAST' )
			'where' => array(
				'USERUNID' => 'u0480643',
				'OR' => array(
					'NAMELAST' => array( 'Smith', 'Jones' ),
					'NAMEFIRST' => 'Robert'
				)
			),
			'order' => 'NAMELAST ASC'
		)
	);

creates this SQL query

	SELECT "APPLICATIONUSER"."NAMEFIRST", "APPLICATIONUSER"."NAMELAST" FROM "SOMADMINISTRATION"."APPLICATIONUSER" WHERE ((NAMEFIRST = 'Robert') OR (NAMELAST IN ('Smith', 'Jones'))) AND (USERUNID = 'u0480643') ORDER BY "NAMELAST" ASC

and returns this array

	array(
		0 => array(
			'NAMEFIRST' => 'Jake',
			'NAMELAST' => 'Jones'
		)
	)



INSERT

$table->insert(
	array(
		'data' => array(
			'USERUNID' => 'u1111113',
			'NAMEFIRST' => 'Test',
			'NAMELAST' => 'McTesterson',
			'USEREMAIL' => 'test3@test.com',
			'USERPASSWORD' => 'testtesttest'
		)
	)
);


UPDATE

$table->update(
	array(
		'data' => array(
			'USERUNID' => 'u1111333'
		),
		'where' => array(
			'APPLICATIONUSERID' => 'APPU7031173'
		)
	)
);


DELETE

$table->delete(
	array(
		'where' => array(
			'APPLICATIONUSERID' => array( 'APPU7031173', 'APPU7031172', 'APPU7031169' )
		)
	)
);

In order to pull primary keys from the table metadata, views must also a have (disabled) primary key.

To add a primary key to a view, run an alter command like this one

	alter view viewstudentexam add 
	(
		constraint pk_viewstudentexam
		primary key (studentexamid) rely disable novalidate
	)

*/

Zend_Loader::loadClass('Zend_Cache');
Zend_Loader::loadClass('Zend_Db_Table');
Zend_Loader::loadClass('Edu_Utah_Som_Data_Table');
Zend_loader::loadClass('Edu_Utah_Som_Crypt_Crypt');

class Edu_Utah_Som_Data_OracleTable
{
	const DATE_FORMAT_DEFAULT = 'YYYY-MM-DD HH24:MI:SS';
	const DATE_FORMAT_AMERICAN = 'MON DD, YYYY HH:MI AM';
	const DATE_FORMAT_AMERICAN_S = 'MON DD, YYYY HH:MI:SS AM';
	const DATE_FORMAT_DATEPICKER = 'MM/DD/YYYY';
	private $adapter;
	private $profiler;
	private $table;
	private $nameSchema;
	private $nameTable;
	private $doDebug = false;
	
	/**
	 * Constructor
	 *
	 * @param  adapter		$adapterDatabase	The Oracle database adapter
	 * @param  string		$nameSchema			The name of the schema
	 * @param  string		$nameTable			The name of the table
	 * @param  array		$config				Array of config options passed to Zend_Db_Table
	 * @return void
	 */
	public function __construct( $adapterDatabase, $nameSchema, $nameTable, $config = array() )
	{		
		$nameSchema = strtoupper( $nameSchema );
		$nameTable = strtoupper( $nameTable );

		// set up config
		$config[Zend_Db_Table::ADAPTER] = $adapterDatabase;
		$config[Zend_Db_Table::SCHEMA] = $nameSchema;
		$config[Zend_Db_Table::NAME] = $nameTable;

		// cache table metadata
		$cacheFrontend = array('automatic_serialization'=>true);
		$cacheBackend = array('cache_dir'=>'/tmp');
		$cache = Zend_Cache::factory('Core','File',$cacheFrontend,$cacheBackend);
		$config[Zend_Db_Table_Abstract::METADATA_CACHE] = $cache; //same as $config[metadataCache']
		
		if( !isset( $config[Zend_Db_Table::SEQUENCE] ) )
		{
			$config[Zend_Db_Table::SEQUENCE] = false;
		}

		$this->table = new Edu_Utah_Som_Data_Table( $config );

		$hasMetaData = $this->table->setupMetadata();
		$metadata = $this->table->getMetadata();

		if( empty( $metadata ) )
		{
			throw new Exception( 'OracleTable: ' . $nameSchema . '.' . $nameTable . ' does not exist or is inaccessible' );
		}

		// 
		// assign class properties
		// 
		$this->adapter = $adapterDatabase;
		$this->profiler = $this->adapter->getProfiler();
		$this->profiler->setEnabled( true ); // turn on the profiler so we can return the query string in the query return
		$this->nameSchema = $nameSchema;
		$this->nameTable = $nameTable;
	}


	/**
	 * Make the Zend_Db_Table object public
	 */
	public function getTable()
	{
		return $this->table;
	}


	/**
	 * Make the Zend_Db_Table_Abstract->info() function public
	 * 
	 * @param unknown $option
	 */
	public function info($option)
	{
		return $this->table->info($option);
	}
	
	
	/**
	 * Fetch row(s) as an array of arrays
	 *
	 * @param array		$options		An associative array containing options for a select query ('columns', 'where', 'dateformats', 'order', 'limit')
	 * @return array					An indexed array of associative arrays
	 */
	public function select( array $options = array() )
	{
		$typeReturn = 'RESULT SET'; // default return type
		$doEncryptResultSet = true;

		$select = $this->table->select();
		$options = array_change_key_case( $options, CASE_UPPER );

		if( isset( $options['ENCRYPT'] ) && ( $options['ENCRYPT'] === false || $options['ENCRYPT'] === 'false' ) )
		{
			$doEncryptResultSet = false;
			unset( $options['ENCRYPT'] );
		}

		if( isset( $options['RETURNTYPE'] ) )
		{
			switch( strtoupper( $options['RETURNTYPE'] ) )
			{
				case 'QUERY':
					$typeReturn = 'QUERY';
				break;

				case 'SELECT':
					$typeReturn = 'SELECT';
				break;
			}
			unset( $options['RETURNTYPE'] );
		}

		// 
		// prepare columns array
		// 
		$optionColumns = null;
		if( isset( $options['COLUMNS'] ) )
		{
			switch( gettype( $options['COLUMNS'] ) )
			{
				case 'string':
					$optionColumns = array( $options['COLUMNS'] );
					break;

				case 'array':
					$optionColumns = $options['COLUMNS'];
					break;

				default:
					throw new Exception( "Oracle Table (" . $this->table->info( 'name' ) . "): 'COLUMNS' option expects array or string, " . gettype( $options['COLUMNS'] ) . ' given.' );
					break;
			}
		}

		if( $optionColumns === array( '*' ) )
		{
			$optionColumns = array();
		}

		$columns = array();

		// select all columns
		if( empty( $optionColumns ) )
		{
			// avoid using "SELECT *" because it creates a conflict when using Oracle's version of LIMIT
			$columns = $this->table->info( Zend_Db_Table_Abstract::COLS );
		}
		// select given columns only
		else
		{
			// make copy of data with all column names uppercase
			$optionColumns = array_map( 'strtoupper', $optionColumns );

			// pull out column names not found in table
			$columns = $this->filterColumns( $optionColumns );
		}


		// 
		// format dates in select clause
		// 

		if( isset( $options['RETURNDATEFORMATS'] ) )
		{
			$columns = $this->formatDates( $columns, $options['RETURNDATEFORMATS'] );
		}
		else if( isset( $options['DATEFORMATS'] ) )
		{
			$columns = $this->formatDates( $columns, $options['DATEFORMATS'] );
		}
		else
		{
			$columns = $this->formatDates( $columns );
		}


		// 
		// add LIMIT clause
		// 
		if( isset( $options['LIMIT'] ) )
		{
			switch( gettype( $options['LIMIT'] ) )
			{
				case 'array':
					switch( count( $options['LIMIT'] ) )
					{
						case 1:
							$select->limit( (int) $options['LIMIT'][0] );
							break;

						case 2:
							$select->limit( (int) $options['LIMIT'][0], (int) $options['LIMIT'][1] );
							break;

						default:
							throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): The 'LIMIT' option's value must be an array of one or two integers." );
							break;
					}
					break;

				default:
					$select->limit( (int) $options['LIMIT'] );
					break;
			}
		}

		//
		// add FROM clause
		// 

		// check for 'distinct' value (not key, like this: 0 => 'distinct') in options
		$hasDistinctValue = false;
		foreach( $options as $i => $value )
		{
			if( is_string( $value ) && strtoupper( $value ) === 'DISTINCT' )
			{
				unset( $options[$i] ); // remove 'DISTINCT' value
				$hasDistinctValue = true;
				break;
			}
		}
		
		if( count( $columns ) > 0 )
		{		
			if( $hasDistinctValue )
			{			
				$select->distinct()->from(
					$this->table->info( 'schema' ) . '.' . $this->table->info( 'name' ),
					$columns
				);
			}
			else
			{
				$select->from(
					$this->table->info( 'schema' ) . '.' . $this->table->info( 'name' ),
					$columns
				);			
			}
		}
		else
		{
			if( $hasDistinctValue )
			{
				$select->distinct()->from( $this->table->info( 'schema' ) . '.' . $this->table->info( 'name' ) );
			} 
			else
			{
				$select->from( $this->table->info( 'schema' ) . '.' . $this->table->info( 'name' ) );
			}			
		}

		// 
		// add ORDER clause
		// 
		if( isset( $options['ORDER'] ) )
		{
			$select = $this->addOrderClauseToSelectObject( $select, $options['ORDER'] );
		}

		//
		// add WHERE clause
		// 
		if( isset( $options['WHERE'] ) )
		{
			$select = $this->addWhereClauseToSelectObject( $select, $options );
		}


		switch( $typeReturn )
		{
			case 'RESULT SET':
				// perform the select query
				$setResult = $this->table->fetchAll( $select )->toArray();

				if( $doEncryptResultSet === true )
				{
					// add encrypted keys to result set
					$setResult = $this->encryptIdFieldsInResultSet( $setResult, $this->table->info( Zend_Db_Table_Abstract::PRIMARY ) );
				}

				return $setResult;
			break;

			case 'QUERY':
				return $select->assemble();
			break;

			case 'SELECT':
				return $select;
			break;
		}
	}


	/**
	 * Insert a row into this table
	 *
	 * @param array			$data		Column-value pairs.
	 */
	public function insert( array $options )
	{
		$options = array_change_key_case( $options, CASE_UPPER );

		$doEncryptResultSet = true;
		if( isset( $options['ENCRYPT'] ) && ( $options['ENCRYPT'] === false || $options['ENCRYPT'] === 'false' ) )
		{
			$doEncryptResultSet = false;
			unset( $options['ENCRYPT'] );
		}

		if( !isset( $options['DATA'] ) )
		{
			throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): The insert method requires an array argument with a 'DATA' key." );
		}

		// make copy of data with all column names uppercase
		$dataUpper = array_change_key_case( $options['DATA'], CASE_UPPER );

		// decrypt encrypted values
		$dataDecrypted = self::decryptValues( $dataUpper );

		// pull out column names not found in table
		$dataMatching = $this->filterColumns( $dataDecrypted );

		if( empty( $dataMatching ) )
		{
			throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): Invalid 'data' clause:" . json_encode( $options['data'] ) );
		}
	
		// 
		// if $options['data'] argument contains array values, separate them into individual inserts and perform the inserts
		// 

		// check if multiple insert is possible
		$numColumnsWithMultipleValues = 0;
		$keyColumnWithMultipleValues = '';
		foreach( $dataMatching as $nameColumn => $value )
		{
			if( is_array( $value ) )
			{
				$numColumnsWithMultipleValues++;

				if( $numColumnsWithMultipleValues > 1 )
				{
					throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): Inserting multiple rows is possible, but only one column can have multiple values in the INSERT." );
				}

				$keyColumnWithMultipleValues = $nameColumn;
			}
		}

		$listInsertData = array();

		if( $numColumnsWithMultipleValues > 0 )
		{
			foreach( $dataMatching[$keyColumnWithMultipleValues] as $key => $value )
			{
				// start with original data
				$listInsertData[$key] = $dataMatching;
				// replace key with multiple values with a single value
				$listInsertData[$key][$keyColumnWithMultipleValues] = $value;
			}
		}
		else
		{
			// put data in array so foreach below works whether a column has multiple values or not
			$listInsertData[] = $dataMatching;
		}

		// 
		// perform insert operation
		// 

		$return = array();
		
		foreach( $listInsertData as $dataMatching )
		{
			// change empty values to null
			$dataWithNull = $dataMatching;
			foreach( $dataWithNull as $key => $value )
			{
				if( self::isNullValue( $value ) )
				{
					$dataWithNull[$key] = null; // new Zend_Db_Expr( 'NULL' ); ? temp
				}
				// ignore empty arrays
				else if( is_array( $value ) && count( $value ) === 0 )
				{
					unset( $dataWithNull[$key] );
				}
			}

			// format dates
			if( isset( $options['DATEFORMATS'] ) )
			{
				$dataDateFormatted = $this->formatDates( $dataWithNull, $options['DATEFORMATS'] );
			}
			else
			{
				$dataDateFormatted = $this->formatDates( $dataWithNull );
			}

			$this->adapter->beginTransaction();

			try
			{
				$keysPrimary = $this->table->info( Zend_Db_Table_Abstract::PRIMARY );
				$returnInsert = $this->table->insertReturn( $dataDateFormatted );

				$encryptedPrimaryKeyValues = [];
				if( $doEncryptResultSet )
				{
					foreach( $returnInsert['primary_keys'] as $key => $value )
					{
						$encryptedPrimaryKeyValues[$key . 'ENCRYPTED'] = Edu_Utah_Som_Crypt_Crypt::encryptEncode( array( 'value' => $value ) );
					}

					if( count( $encryptedPrimaryKeyValues ) === 1 )
					{
						$encryptedPrimaryKeyValues = array_values( $encryptedPrimaryKeyValues )[0];
					}
				}

				// if only one primary key, reduce array to single value
				if( count( $returnInsert['primary_keys'] ) === 1 )
				{
					$returnInsert['primary_keys'] = array_values( $returnInsert['primary_keys'] )[0];
				}

				$stringQuery = $this->getStringOfLastQuery() . ' with values: ' . json_encode( $dataDateFormatted );

				$return[] = array( 'status' => 'success', 'return' => $returnInsert['row_count'], 'id' => $returnInsert['primary_keys'], 'idencrypted' => $encryptedPrimaryKeyValues, 'query' => $stringQuery );

				$this->adapter->commit();
			}
			catch( Zend_Db_Exception $e )
			{
				$this->adapter->rollback();

				// allow inserts to fail quietly if duplicate found. This allows us to attempt to perform an insert without first checking if the row exists
				if( strpos( $e->getMessage(), 'ORA-00001' ) !== false ) // if "unique constraint violated" (duplicate record) error
				{
					$messageReturn = 'Insert failed. Duplicate found for ' . json_encode( $dataMatching ) . '; ' . $e->getMessage();
					$return[] = array( 'status' => 'failure', 'return' => $messageReturn );
				}
				// general error
				else
				{
					throw new Zend_Db_Exception( $e->getMessage() );
				}
			}
		}

		return $return;
	}


	/**
	 * Update a row in this table
	 *
	 * @param array			$data			Column-value pairs.
	 * @param string|array	$dataWhere		A WHERE clause or column-value pairs.
	 */
	public function update( array $options )
	{
		$GLOBALS['STOPWATCH']['checkpoint'][] = ['file'=>__FILE__, 'line'=>__LINE__, 'time'=>microtime(true), 'name'=>'us' . substr($this->nameTable, 0,18)];
		$options = array_change_key_case( $options, CASE_UPPER );

		if( !isset( $options['DATA'] ) )
		{
			throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): The update method requires an array argument with a 'data' key." );
		}

		if( !isset( $options['WHERE'] ) )
		{
			throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): The update method requires an array argument with a 'where' key." );
		}

		$doReturnQueryString = false;

		if( isset( $options['RETURNTYPE'] ) && strtoupper( $options['RETURNTYPE'] ) === 'QUERY' )
		{
			$doReturnQueryString = true;
			unset( $options['RETURNTYPE'] );
		}

		// make copy of data with all column names uppercase
		$dataUpper = array_change_key_case( $options['DATA'], CASE_UPPER );

		// decrypt encrypted values
		$dataDecrypted = self::decryptValues( $dataUpper );

		// pull out column names not found in table
		$dataMatching = $this->filterColumns( $dataDecrypted );

		if( empty( $dataMatching ) )
		{
			throw new Exception( 'OracleTable (' . $this->table->info( 'name' ) . '): Invalid "data" clause:' . json_encode( $options['DATA'] ) );
		}

		// format dates
		if( isset( $options['DATEFORMATS'] ) )
		{
			$dataDateFormatted = $this->formatDates( $dataMatching, $options['DATEFORMATS'] );
		}
		else
		{
			$dataDateFormatted = $this->formatDates( $dataMatching );
		}

		// change empty values to null
		$dataWithNull = $dataDateFormatted;
		foreach( $dataDateFormatted as $key => $value )
		{
			if( self::isNullValue( $value ) )
			{
				$dataWithNull[$key] = null;
			}
			// ignore empty arrays
			else if( is_array( $value ) && count( $value ) === 0 )
			{
				unset( $dataWithNull[$key] );
			}
		}

		$selectTemp = $this->table->select();
		$select = $this->addWhereClauseToSelectObject( $selectTemp, $options );
		$partWhere = $select->getPart( Zend_Db_Select::WHERE );

		if( empty( $partWhere ) )
		{
			throw new Exception( 'OracleTable (' . $this->table->info( 'name' ) . '): Invalid "where" clause:' . json_encode( $options['WHERE'] ) );
		}
		
		$returnUpdate = $this->table->update( $dataWithNull, implode( ' ', $partWhere ) );

		if( $doReturnQueryString )
		{
			return $this->getStringOfLastQuery() . ' with values: ' . json_encode( $dataWithNull ); 
		}

		return $returnUpdate;
	}


	/**
	 * Delete a row in this table
	 *
	 * @param string|array	$dataWhere		A WHERE clause or column-value pairs
	 */
	public function delete( array $options = array() )
	{
		$options = array_change_key_case( $options, CASE_UPPER );

		if( !isset( $options['WHERE'] ) )
		{
			throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): The delete method requires an array argument with a 'where' key." );
		}

		$doReturnQueryString = false;

		if( isset( $options['RETURNTYPE'] ) && strtoupper( $options['RETURNTYPE'] ) === 'QUERY' )
		{
			$doReturnQueryString = true;
			unset( $options['RETURNTYPE'] );
		}

		$selectTemp = $this->table->select();
		$this->addWhereClauseToSelectObject( $selectTemp, $options );
		$partWhere = $selectTemp->getPart( Zend_Db_Select::WHERE );

		if( empty( $partWhere ) )
		{
			throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): Invalid 'where' clause:" . json_encode( $options['WHERE'] ) );
		}

		try
		{
			$returnDelete = $this->table->delete( implode( ' ', $partWhere ) );

			$GLOBALS['STOPWATCH']['checkpoint'][] = ['file'=>__FILE__, 'line'=>__LINE__, 'time'=>microtime(true), 'name'=>'de' . substr($this->nameTable, 0,18)];
			if( $doReturnQueryString )
			{
				return $this->getStringOfLastQuery();
			}

			return $returnDelete;
		}
		catch( Zend_Db_Exception $e )
		{
			// allow delete to fail quietly if foreign key error
			if( strpos( $e->getMessage(), 'ORA-02292' ) !== false )
			{
				$messageReturn = $e->getMessage();
				$returnDelete = array( 'status' => 'failure', 'return' => $messageReturn );
				return $returnDelete;
			}
			// general error
			else
			{
				throw new Zend_Db_Exception( $e->getMessage() );
			}
		}
	}


	public static function getCurrentDateString( $formatDate )
	{
		switch( strtoupper( $formatDate ) )
		{
			case 'AMERICAN':
				return date( "F j, Y g:i a" );
				break;

			case 'AMERICAN_S':
				return date( "F j, Y g:i:s a" );
				break;

			case 'DATEPICKER':
				return date( "m/d/Y" );
				break;

			case 'TIMESTAMP':
				return time();
				break;

			case null:
			case '':
			case 'DEFAULT':
			default:
				return date( "Y-m-d G:i:s" );
				break;
		}
	}


	public static function getDateString( $timestamp, $formatDate )
	{
		switch( strtoupper( $formatDate ) )
		{
			case 'AMERICAN':
				return date( "F j, Y g:i a", $timestamp );
				break;

			case 'AMERICAN_S':
				return date( "F j, Y g:i:s a", $timestamp );
				break;

			case 'DATEPICKER':
				return date( "m/d/Y, $timestamp" );
				break;

			case 'TIMESTAMP':
				return time();
				break;

			case null:
			case '':
			case 'DEFAULT':
			default:
				return date( "Y-m-d G:i:s", $timestamp );
				break;
		}
	}


	/**
	 * Encrypt the values of columns ending in 'ID' in a result set and add them to the result set
	 *
	 * @param array			$setResult		The Oracle DB result set returned by fetch()
	 * @param array			$keysPrimary	An indexed array of column names of this table's primary keys
	 * @return array						The Oracle DB result set returned by fetch() including encrypted key-value pairs
	 */
	public static function encryptIdFieldsInResultSet( $setResult, $keysPrimary = array() )
	{
		if( empty( $setResult ) || !is_array( $setResult ) )
		{
			return $setResult;
		}

		$arrayKeysToBeEncrypted = array();

		foreach( $setResult[0] as $nameColumn => $data )
		{
			if( in_array( $nameColumn, $keysPrimary ) || substr( $nameColumn, -2 ) === 'ID' ) // also encrypt anything that ends in "ID", but isn't the primary key, like "APPLICATIONUSERID" in many tables
			{
				$arrayKeysToBeEncrypted[] = $nameColumn;
			}
		}

		if( count( $arrayKeysToBeEncrypted ) === 0 )
		{
			return $setResult;
		}

		for( $i = 0, $l = count( $setResult ); $i < $l; $i++ )
		{
			foreach( $arrayKeysToBeEncrypted as $nameColumn )
			{
				if( empty( $setResult[$i][$nameColumn] ) )
				{
					$setResult[$i][$nameColumn . 'ENCRYPTED'] = null;
				}
				else
				{
					$setResult[$i][$nameColumn . 'ENCRYPTED'] = Edu_Utah_Som_Crypt_Crypt::encryptEncode( array( 'value' => $setResult[$i][$nameColumn] ) );
				}
			}
		}

		return $setResult;
	}



	/**
	 * Return boolean representing whether $array is an associative array (it contains one or more string keys)
	 *
	 * @link	http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential/4254008#4254008
	 */
	public static function isAssociativeArray( $array )
	{
		return !empty( $array ) && is_array( $array ) && ( bool )count( array_filter( array_keys( $array ), 'is_string' ) );
	}


	/**
	 * Attach a WHERE clause to a Zend_Db_Table_Select object
	 *
	 * @param Zend_Db_Table_Select	$select  		A Zend_Db_Table_Select object
	 * @param string|array			$options		The array argument passed to OracleTable
	 */
	private function addWhereClauseToSelectObject( $select, $options = null )
	{
		// if $options doesn't have a 'where' key-value pair, don't add a where clause to the $select
		if( empty( $options ) || !isset( $options['WHERE'] ) || empty( $options['WHERE'] ) )
		{
			return $select;
		}

		// if $options['WHERE'] is a string, then pass it directly to the $select
		if( !is_array( $options['WHERE'] ) )
		{
			$select->where( $options['WHERE'] );
			return $select;
		}
		
		$isCaseSensitiveWhere = true;
		if( isset( $options['CASESENSITIVEWHERE'] ) && filter_var( $options['CASESENSITIVEWHERE'], FILTER_VALIDATE_BOOLEAN ) === false ) // if coming through ajax, the value may be 'false' so force 'false' to be false
		{
			unset( $options['CASESENSITIVEWHERE'] );
			$isCaseSensitiveWhere = false;
		}

		// change keys to uppercase
		$options['WHERE'] = array_change_key_case( $options['WHERE'], CASE_UPPER );

		$dataDateFormat = array();
		if( isset( $options['WHERE']['DATEFORMATS'] ) )
		{
			$dataDateFormat = $options['WHERE']['DATEFORMATS'];
		}
		else if( isset( $options['DATEFORMATS'] ) )
		{
			$dataDateFormat = $options['DATEFORMATS'];
		}

		$stringWhere = $this->buildWhereString( $options['WHERE'], 'AND', $dataDateFormat, $isCaseSensitiveWhere );
		$stringWhere .= ' ';

		$select->where( $stringWhere );

		return $select;
	}


	/**
	 * Combine WHERE conditions with AND or OR and attach to the Zend_Db_Table_Select object
	 *
	 * @param Zend_Db_Table_Select		$select				A Zend_Db_Table_Select object
	 * @param string/array				$dataWhere			Data going into the WHERE clause
	 * @param string					$joiner				Either 'AND' or 'OR'
	 * @param string					$dataDateFormat		Optional. An array of date formats with column names (or '*' for all columns) as keys and date format strings as values
	 * @return Zend_Db_Table_Select							Modified $select with WHERE part added
	 */
	private function buildWhereString( $dataWhere, $joiner, $dataDateFormat = array(), $isCaseSensitiveWhere = true, $level = -1 )
	{
		$stringWhere = '';
		$listWhereClauseStringAll = array();

		// convert keys to upper case
		$dataWhere = array_change_key_case( $dataWhere, CASE_UPPER );

		$level++; // this is important when we do recursive calls below

		// 
		// recursive calls for OR and AND
		// 

		// recursive call if nested OR
		if( isset( $dataWhere['OR'] ) )
		{
			$stringWhereOr = $this->buildWhereString( $dataWhere['OR'], 'OR', $dataDateFormat, $isCaseSensitiveWhere, $level );
			$stringWhere .= ' ( ' . trim( $stringWhereOr ) . ' )';
			unset( $dataWhere['OR'] );
		}


		// recursive call if nested AND
		if( isset( $dataWhere['AND'] ) )
		{
			$stringWhereAnd = $this->buildWhereString( $dataWhere['AND'], 'AND', $dataDateFormat, $isCaseSensitiveWhere, $level );
			$stringWhere .= ' ( ' . trim( $stringWhereAnd ) . ' )';
			unset( $dataWhere['AND'] );
		}


		// 
		// convert comparator symbol (NOT, >, !=, LIKE,...) data into where clause strings
		// 

		foreach( $dataWhere as $comparator => $dataColumn )
		{
			// convert greater than/less than shortcuts to symbols
			switch( $comparator )
			{
				case 'GT':
					$comparator = '>';
				break;

				case 'LT':
					$comparator = '<';
				break;
			}

			// divide "where" data into arguments using given comparator key
			switch( $comparator )
			{
				case 'NOT':
				case '!=':
					unset( $dataWhere[$comparator] );

					// special case -- if we are attempting a NOT IN but one of the values is NULL, then pull it out because NOT IN ( ..., NULL ) does not work in SQL
					foreach( $dataColumn as $key => $value )
					{
						if( is_array( $value ) )
						{
							foreach( $value as $i => $v )
							{
								if( self::isNullValue( $v ) )
								{
									$dataDetached = array(
										$key => $v
									);

									$listWhereClauseString = $this->buildWhereClauseString( $dataDetached, '!=', $dataDateFormat, $isCaseSensitiveWhere );
									$listWhereClauseStringAll = array_merge( $listWhereClauseStringAll, $listWhereClauseString );

									unset( $dataColumn[$key][$i] );
								}
							}
						}
					}

					$listWhereClauseString = $this->buildWhereClauseString( $dataColumn, '!=', $dataDateFormat, $isCaseSensitiveWhere );
					$listWhereClauseStringAll = array_merge( $listWhereClauseStringAll, $listWhereClauseString );
				break;

				case '>':
				case '>=': 
				case '<':
				case '<=':
				case 'NOT LIKE':
				case 'LIKE':
				case 'REGEXP_LIKE':
					unset( $dataWhere[$comparator] );

					// because we can't use 'IN' SQL operator for these operations, we have to break up array values into separate comparisons
					foreach( $dataColumn as $nameColumn => $data )
					{
						if( is_array( $data ) )
						{
							foreach( $data as $value )
							{
								$listWhereClauseString =   $this->buildWhereClauseString( array( $nameColumn => $value ), $comparator, $dataDateFormat, $isCaseSensitiveWhere );
								$listWhereClauseStringAll = array_merge( $listWhereClauseStringAll, $listWhereClauseString );
							}
						}
						else
						{
							$listWhereClauseString = $this->buildWhereClauseString( array( $nameColumn => $data ), $comparator, $dataDateFormat, $isCaseSensitiveWhere );
							$listWhereClauseStringAll = array_merge( $listWhereClauseStringAll, $listWhereClauseString );
						}
					}
				break;

				case '=':
					unset( $dataWhere[$comparator] );
					$listWhereClauseString = $this->buildWhereClauseString( $dataColumn, $comparator, $dataDateFormat, $isCaseSensitiveWhere );
					$listWhereClauseStringAll = array_merge( $listWhereClauseStringAll, $listWhereClauseString );
				break;
			}
		}

		// convert regular column-value data into where clause strings
		if( !empty( $dataWhere ) )
		{
			$listWhereClauseString = $this->buildWhereClauseString( $dataWhere, '=', $dataDateFormat, $isCaseSensitiveWhere );

			if( empty( $listWhereClauseString ) && empty( $listWhereClauseStringAll ) )
			{
				throw new Exception( 'OracleTable (' . $this->table->info( 'name' ) . '): Invalid "where" clause: ' . json_encode( $dataWhere ) );
			}

			$listWhereClauseStringAll = array_merge( $listWhereClauseStringAll, $listWhereClauseString );
		}

		// if there aren't any more where clauses to add to the where string, then end here
		if( empty( $listWhereClauseStringAll ) )
		{
			return $stringWhere;
		}

		//
		// add all where string clauses to the main where string
		//

		// if adding to other where clauses, put a joiner string (AND) in between
		if( strlen( $stringWhere ) > 0 )
		{
			$stringWhere = $stringWhere . ' ' . $joiner;
		}

		// group each where clause string (important for ORs)
		foreach( $listWhereClauseStringAll as $i => $stringClause )
		{
			// join all parts following first part
			if( $i !== 0 )
			{
				$stringWhere = $stringWhere . ' ' . $joiner;
			}
			
			$stringWhere = $stringWhere . ' ' . $stringClause; // wrap in parentheses so nested ANDs and ORs group properly
		}
  
		return $stringWhere;
	}


	/*
	 * Build WHERE conditions (e.g., A = B, A LIKE B, A IS NOT NULL) and return as array
	 */
	private function buildWhereClauseString( $dataWhere, $comparator = '=', $dataDateFormat, $isCaseSensitiveWhere )
	{
		// make copy of data with all column names uppercase
		$dataWhereUpper = array_change_key_case( $dataWhere, CASE_UPPER );

		// decrypt encrypted values
		$dataWhereDecrypted = self::decryptValues( $dataWhereUpper );

		// 
		// pull out column names not found in table
		// 
		try
		{
			$dataWhereMatching = $this->filterColumns( $dataWhereDecrypted );
		}
		catch( Exception $e )
		{
			$errorMessageFilter = "OracleTable (" . $this->table->info( 'name' ) . "): 'where' option " . json_encode( $dataWhereDecrypted ) . " contains no columns found in the Oracle table '" . $this->info( 'name') . "'.";
			throw new Exception( $errorMessageFilter );
		}

		// format date columns
		$dataWhereFormatted = $this->formatDates( $dataWhereMatching, $dataDateFormat );

		// 
		// determine the correct comparison language
		// 
		$listWhereClauseString = array();

		foreach( $dataWhereFormatted as $nameColumn => $value )
		{
			$isValueNull = false;
			$partComparator = '';

			// treat null, '', and 'NULL' as null
			if( self::isNullValue( $value ) )
			{
				$isValueNull = true;
				$partComparator = '';

				// translate certain comparators to 'IS' if comparing to NULL
				switch( $comparator )
				{
					case 'LIKE':
					case 'REGEXP_LIKE': // unlikely that someone would pass a null value to REGEXP_LIKE, but it's possible. I'm going to treat it as a simple equality comparison
					case '=':
						$partComparator = 'IS ?';
					break;

					case 'NOT LIKE':
					case '!=':
						$partComparator = 'IS NOT ?';
					break;

					default:
						$partComparator = $comparator . ' ?';
					break;
				}

				$value = new Zend_Db_Expr( 'NULL' );
			}
			// when comparing to array of values
			else if( is_array( $value ) )
			{
				// skip empty arrays
				if( count( $value ) === 0 )
				{
					continue;
				}

				switch( $comparator )
				{
					case '=':
						$partComparator = 'IN(?)';
						break;

					case '!=':
						$partComparator = 'NOT IN(?)';
						break;

					default:
						throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): Unable to build a SQL query where an array is part of a comparison unless the comparison operator is '=' or '!=' (or 'NOT'). The comparator is '" . $comparator . "'. The problematic array is: " . json_encode( $value ) );
						break;
				}
			}
			// all other comparisons
			else
			{
				switch( $comparator )
				{
					case 'NOT LIKE':
					case 'LIKE':
						// if the query has 'like', then we assume that it wants autocompletion
						// add wildcards if user did not have any
						if( strpos( $value, '%' ) === false )
						{
							$value = '%' . $value . '%'; 
						}

						// case sensitive comparison
						if( $isCaseSensitiveWhere === true ) // $isCaseSensitiveWhere is true by default
						{
							$partComparator = $comparator . ' ?';
						}
						// case insensitive comparison
						else
						{
							$partComparator = $comparator . ' UPPER(?)';
						}
					break;

					case 'REGEXP_LIKE':
						$listWhereClauseString[] = 'REGEXP_LIKE( ' . $nameColumn . ', \'' . $value . '\' )';
						continue 2; // continue works on switches too so continue 2 ends both the switch and the foreach around it
					break;
					
					default:
						$partComparator = $comparator . ' ?';
					break;
				}			
			}

			// case sensitive comparison -- maintain local value's and db value's cases
			$doNotUppercase = $isCaseSensitiveWhere === true // $isCaseSensitiveWhere is true by default
				|| strpos( $this->table->info('metadata')[$nameColumn]['DATA_TYPE'], 'CHAR' ) === false  // don't uppercase non-string fields (e.g., DATE and NUMBER)
				|| $isValueNull; // don't uppercase null values
			
			if( $doNotUppercase ) 
			{
				$stringWhereClause = $nameColumn . ' ' . $partComparator;
			}
			// case insensitive comparison
			else
			{
				// make db value uppercase
				$stringWhereClause = "UPPER(" . $nameColumn . ")" . ' ' . $partComparator;

				// make local value(s) uppercase
				if( gettype( $value ) === 'array' )
				{
					$valueUpper = array();
					foreach( $value as $v )
					{
						$valueUpper[] = strtoupper( $v );
					}
					$value = $valueUpper;
				}
				else
				{
					$value = strtoupper( $value );
				}
			}

			$stringWhereClauseWithValue = '';

			// don't quote null values
			if( $isValueNull === true )			
			{
				$stringWhereClauseWithValue = str_replace( '?', $value, $stringWhereClause );
			}
			// quote values
			// note: quoting is not as secure as prepared statements. We should switch to prepared statements
			else
			{
				$stringWhereClauseWithValue = $this->adapter->quoteInto( $stringWhereClause, $value );
			}

			$listWhereClauseString[] = $stringWhereClauseWithValue;
		}

		return $listWhereClauseString;
	}


	/**
	 * Add ORDER BY clause to Zend_Db_Table_Select object
	 *
	 * @param Zend_Db_Table_Select		$select
	 * @param string/array				Data going into the ORDER BY clause
	 * @return Zend_Db_Table_Select		Modified $select with ORDER BY clause
	 */
	private function addOrderClauseToSelectObject( $select, $dataOrder )
	{
		$meta = $this->table->info('metadata');

		switch( gettype( $dataOrder ) )
		{
			case 'array':

				$dataOrderFinal = array();

				foreach( $dataOrder as $stringOrder )
				{
					$stringOrderUpper = strtoupper( $stringOrder );
					$stringOrderUpperParts = explode( ' ', $stringOrderUpper );
					$metaElement = array();
					$stringOrderFieldPart = $this->table->info( 'name' ) . '.' . $stringOrderUpperParts[0];

					//find out what kind of field we're dealing with, if it's a date field, we can't use the
					//LOWER() statement to handle sorting; it completely throws off sorting
					if( array_key_exists ( strtoupper( $stringOrderUpperParts[0] ), $meta ) )
					{
						$metaElement = $meta[$stringOrderUpperParts[0]];
					}
					
					//if our type is not a DATE or NUMBER, wrap it with the LOWER() function
					if( isset( $metaElement['DATA_TYPE'] ) && $metaElement['DATA_TYPE'] != 'DATE' && $metaElement['DATA_TYPE'] != 'NUMBER' )
					{
						$stringOrderFieldPart = 'LOWER(' . $stringOrderFieldPart . ')';
					}

					// case-insensitive ordering
					if( count( $stringOrderUpperParts ) > 1 )
					{
						$stringOrderFinal = $stringOrderFieldPart . ' ' . $stringOrderUpperParts[1];
					}
					else if( count( $stringOrderUpperParts ) > 0 )
					{
						$stringOrderFinal = $stringOrderFieldPart . ' ASC';
					}
					$dataOrderFinal[] = $stringOrderFinal;
				}

				$select->order( $dataOrderFinal );

				break;

			case 'string':

				return $this->addOrderClauseToSelectObject( $select, array( $dataOrder ) );

				break;


			default:

				throw new Exception( "Oracle Table (" . $this->table->info( 'name' ) . "): 'order' option expects array or string, " . gettype( $dataOrder ) . ' given.' );

				break;
		}

		return $select;
	}


	/**
	 * Remove columns from query that do not exist in the specified table
	 *
	 * @param array		$data		An associative array with column names as keys and database values/Zend_Db_Expr's as values
	 * @return array				Modified $data
	 */
	private function filterColumns( $data )
	{
		if( empty( $data ) )
		{
			return $data;
		}

		$columnsAcceptable = array_merge(
			$this->table->info( Zend_Db_Table_Abstract::COLS ),
			array(
				'ROWNUM'
			)
		);

		$columnsFiltered = array();

		if( self::isAssociativeArray( $data ) )
		{
			$columnsFiltered = array_intersect_key( $data, array_flip( $columnsAcceptable ) );
		}
		else if( is_array( $data ) )
		{
			$columnsFiltered = array_intersect( $data, $columnsAcceptable );
		}

		return $columnsFiltered;
	}


	/**
	 * Replace date values with formatted Zend_Db_Expr's
	 *
	 * @param array		$data				Either an indexed array of column names or an associative array with column names as keys and database values/Zend_Db_Expr's as values
	 * @param string	$dataDateFormat		Optional. An array of date formats with column names (or '*' for all columns) as keys and date format strings as values
	 * @return array						Modified $data
	 */
	private function formatDates( $data, $dataDateFormat = array() )
	{
		if( empty( $data ) )
		{
			return array();  
		}

		//var_dump( $data, $dataDateFormat );
		if( empty( $dataDateFormat ) )
		{
			$dataDateFormat = array( '*' => self::DATE_FORMAT_DEFAULT );
		}
		else if( !is_array( $dataDateFormat ) )
		{
			throw new Exception( "OracleTable (" . $this->table->info( 'name' ) . "): The second argument of formatDates method should be type array, " . gettype( $dataDateFormat ) . ' passed instead.' );
		}
		else
		{
			$dataDateFormat = array_change_key_case( $dataDateFormat, CASE_UPPER );
		}

		$dataDateFormatFinal = array();
		$isDataAssociative = self::isAssociativeArray( $data );

		reset( $dataDateFormat ); // move array pointer to first element to set up key() below
		$keyFirst = key( $dataDateFormat ); // get first key from array
 
		// if all columns in result set will share a single date format
		if( $keyFirst === '*' )
		{
			/**
			 * @todo	For queries with a 'where' option and '*' in 'dateformats', this section runs twice per query (once for the 'data'/'columns' part and once for the 'where' part).
			 *			It would be nice if I could find a way to run it only once per query, but doing so would require some kind of query object as a class property.
			 */

			// assign the passed-in format to all database table columns of data type "DATE"
			foreach( $this->table->info( Zend_Db_Table_Abstract::METADATA ) as $nameColumn => $metadata )
			{
				if( $metadata['DATA_TYPE'] === 'DATE' )
				{
					$dataDateFormatFinal[$nameColumn] = $dataDateFormat[$keyFirst]; // give every date column the default format
				}
			}
		}
		// if a format is set column-by-column
		else
		{
			$dataDateFormatFinal = $this->filterColumns( $dataDateFormat );
			$columnsOfData = array();

			if( $isDataAssociative )
			{
				$columnsOfData = array_keys( $data );
			}
			else
			{
				$columnsOfData = $data;
			}

			foreach( $columnsOfData as $nameColumn )
			{
				if( $this->table->info( Zend_Db_Table_Abstract::METADATA )[$nameColumn]['DATA_TYPE'] === 'DATE' )
				{
					if( !array_key_exists( $nameColumn, $dataDateFormatFinal ) )
					{
						$dataDateFormatFinal[$nameColumn] = self::DATE_FORMAT_DEFAULT;
					}
				}
			}
		}


		// if insert/update or where
		if( $isDataAssociative )
		{
			foreach( $dataDateFormatFinal as $nameColumn => $formatDate )
			{
				// skip column names in the 'dateformats' option that were not also passed to the 'data' option
				if( !isset( $data[$nameColumn] ) )
				{
					continue;
				}
				
				// no need to format null values
				if( self::isNullValue( $data[$nameColumn] ) )
				{
					continue;
				}

				if( is_array( $data[$nameColumn] ) )
				{
					$dataFormatted = array();
					foreach( $data[$nameColumn] as $value )
					{
						$dataFormatted[] = self::buildToDateString( $value, $formatDate );
					}
					$data[$nameColumn] = $dataFormatted;
				}
				else
				{
					$data[$nameColumn] = self::buildToDateString( $data[$nameColumn], $formatDate );
				}
			}
		}
		// if select 
		else if( is_array( $data ) )
		{
			$doSelectAll = false;
			
			// if no columns specified, select all
			if( count( $data ) === 0 )
			{
				$doSelectAll = true;
				$data = array( "*" );
			}

			foreach( $dataDateFormatFinal as $nameColumn => $formatDate )
			{
				// check if the column name passed to the 'dateformats' option was also passed to the 'data' option
				// of the select
				if( !$doSelectAll && !in_array( $nameColumn, $data ) )
				{
					continue;
				}

				// remove date-formatted columns from the original list of columns so that we don't select
				// the same column twice
				if( ( $key = array_search( $nameColumn, $data ) ) !== false ) 
				{
					unset( $data[$key] );
				}

				$data[$nameColumn] = self::buildToCharString( $nameColumn, $formatDate );
			}
				
		}

		return $data;
	}


	/**
	 * Build a TO_CHAR Zend_Db_Expr
	 *
	 * @param string	$nameColumn			The name of the column to-be-formatted
	 * @param string	$formatDate	The Oracle date format
	 * @return Zend_Db_Expr					A Zend_Db_Expr for the given date
	 */
	private static function buildToCharString( $nameColumn, $formatDate )
	{
		switch( strtoupper( $formatDate ) )
		{
			case null:
			case '':
			case 'DEFAULT':
				$formatDate = self::DATE_FORMAT_DEFAULT;
				break;

			case 'AMERICAN':
				$formatDate = self::DATE_FORMAT_AMERICAN;
				break;

			case 'AMERICANMS':
				$formatDate = self::DATE_FORMAT_AMERICAN_S;
				break;

			case 'DATEPICKER':
				$formatDate = self::DATE_FORMAT_DATEPICKER;
				break;

			case 'TIMESTAMP':
				// convert PHP timestamp (integer since Jan 1, 1970) to Oracle CHAR
				// can you select timestamps from Oracle?
				throw new Exception( "OracleTable (buildToCharString(" . $nameColumn . ', ' . $formatDate . ")): For now, you can't return something as a timestamp." );
				break;

			default:
				break;
		}
		
		return new Zend_Db_Expr( "TO_CHAR( " . $nameColumn . ", '" . $formatDate . "' )" );
	}


	/**
	 * Builds a TO_DATE Zend_Db_Expr
	 *
	 * @param string	$stringDate			The date string
	 * @param string	$formatDate	The Oracle date format
	 * @return Zend_Db_Expr					A Zend_Db_Expr for the given date
	 */
	private static function buildToDateString( $stringDate, $formatDate )
	{
		switch( strtoupper( $formatDate ) )
		{
			case null:
			case '':
			case 'DEFAULT':
				$formatDate = self::DATE_FORMAT_DEFAULT;
				break;

			case 'AMERICAN':
				$formatDate = self::DATE_FORMAT_AMERICAN;
				break;

			case 'DATEPICKER':
				$formatDate = self::DATE_FORMAT_DATEPICKER;
				break;

			case 'TIMESTAMP':
				// convert PHP timestamp (integer since Jan 1, 1970) to Oracle DATE
				return new Zend_Db_Expr( "TO_DATE( '01/01/1970', 'MM/DD/YYYY' ) + " . $stringDate . " / (24 * 60 * 60)" );
				break;

			default:
				break;
		}
		
		$stringExpression = "TO_DATE( '" . $stringDate . "', '" . $formatDate . "' )";
		
		return new Zend_Db_Expr( $stringExpression );
	}


	private static function isNullValue( $value )
	{
		return $value === null || $value === '' || ( !is_array( $value ) && strtoupper( $value ) === 'NULL' ); // strtoupper throws error on arrays, so check if it's not an array first
	}
	

	/**
	 * Decrypt encrypted values before query is sent to database
	 *
	 * @param array		$data		An associative array with column names as keys and database values/Zend_Db_Expr's as values
	 * @return array				Modified $data
	 */
	private static function decryptValues( $data )
	{
		foreach( $data as $nameColumn => $value )
		{
			if( substr( $nameColumn, -9, 9 ) === 'ENCRYPTED' )
			{
				$nameColumnWithoutEncryptedWord = substr( $nameColumn, 0, -9 );

				// skip columns whose decrypted value is already in the data
				if( isset( $data[$nameColumnWithoutEncryptedWord] ) )
				{
					unset( $data[$nameColumn] ); // remove column with encrypted key-value pair

					continue;
				}

				$valueDecrypted = null;

				switch( gettype( $value ) )
				{
					case 'array':
						$valueDecrypted = array();
						for( $i = 0, $l = count( $value ); $i < $l; $i++ )
						{
							if( self::isNullValue( $value[$i] ) )
							{
								$valueDecrypted[] = null;
							}
							else
							{
								$valueDecrypted[] = Edu_Utah_Som_Crypt_Crypt::decryptDecode( array( 'value' => $value[$i] ) );
							}
						}
					break;

					case 'string':
						unset( $data[$nameColumn] );

						if( self::isNullValue( $value ) )
						{
							$valueDecrypted = null;
						}
						else
						{
							$valueDecrypted = Edu_Utah_Som_Crypt_Crypt::decryptDecode( array( 'value' => $value ) );
						}
					break;
				}

				unset( $data[$nameColumn] ); // remove column with encrypted key-value pair
				$data[$nameColumnWithoutEncryptedWord] = $valueDecrypted;
			}
		}

		return $data;
	}


	/**
	 * Get last query body as string
	 */
	private function getStringOfLastQuery()
	{
		$query  = $this->profiler->getLastQueryProfile();
		$params = $query->getQueryParams();
		$stringQuery  = $query->getQuery();

		foreach( $params as $param )
		{
			$stringQuery = preg_replace( '/\\?/', "'" . $param . "'", $stringQuery, 1 );
		}
		return $stringQuery;
	}

}

?>
