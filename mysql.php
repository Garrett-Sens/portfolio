<?php

/*
 * Connects, queries, and fetches results from a MySQL database.
 * Queries are performed using prepared statements for security.
 */
class Database
{
	private $host		= '';
	private $username	= '';
	private $password	= '';
	private $name		= '';
	private $mysqli		= null;
	private $php_5_4	= false;

	/**
	 * Connects to MySQL database using MySQLi object
	 *
	 * @param string [$host] The name of the database host server
	 * @param string [$user] A valid username for the database
	 * @param string [$pass] The password associated with the given username
	 * @param string [$database_name] The name of the database
	 * note: If no arguments are passed, Database uses credentials from config.php
	 */
	public function __construct( $host, $username, $password, $database_name )
	{
		$this->host		= $host;
		$this->username	= $username;
		$this->password	= $password;
		$this->name		= $database_name;
		$this->php_5_4	= function_exists( 'mysqli_stmt_get_result' ); // also 'mysqli_stmt::get_result'
		$this->mysqli	= new mysqli( $host, $username, $password, $database_name );
	}


	/*
	 *	close connection when no more calls to this object
	 */
	public function __destruct()
	{
		$this->mysqli->close();
	}


	/**
	 * Creates and executes a prepared statement.
	 *
	 * The purpose of prepared statements is to execute a single query multiple times quickly using different variables.
	 * However, we are using them here for security purposes, aware of the fact that preparing and executing single statements
	 * one at a time goes against the intention of prepared statements. See "Escaping and SQL Injection" at
	 * http://php.net/manual/en/mysqli.quickstart.prepared-statements.php
	 *
	 * For insert and update queries, passing an associative array of column names and values along with the first few
	 * words of the query, up to and including the table name, is sufficient.
	 *
	 * Example:
	 *	$my_table_values = array(
	 *		'Person' => 'Bob Jones',
	 *		'Company => 'BJ Manufacturing'
	 *	);
	 *	$query_fragment	= "INSERT INTO `my_table`";
	 *	database->query( $query_fragment, $my_table_values );
	 *
	 * @param string [$query] Either a valid SQL query or the start of one accompanied by an associative array
	 * @param array [$params] Optional. Either a list of values that will be bound to the prepared statement or an associative
	 * array whose keys match the table's column names.
	 * @return mixed [$result or $success] The result of a SQL SELECT, else boolean.
	 */
	public function query( $query, $params = null )
	{
		if( mysqli_connect_error() )	// backwards-compatible error check
		{
			throw new Exception( 'Failed to connect(' . mysqli_connect_errno() . '): ' . mysqli_connect_error() );
		}

		//var_dump( 'before: ', $query, $params );

		if( empty( $params ) )
		{
			$statement	= $this->prepare( $query );
			return $this->execute( $statement );
		}
		else
		{
			if( !is_array( $params ) )
			{
				$params = array( $params );	 // convert to array because $this->bind_params requires it
			}
			else if( is_assoc( $params ) )	// "is_assoc" is defined in pcam_common/php/core.php)
			{
				// @todo finish reduce_params( $table_name, $params );

				// stick $params keys into $query
				$query = $this->build_query_from_associative_array( $query, array_keys( $params ) );

				// Because we've just filled in the keys (column names), we only need to pass the values
				$params	= array_values( $params );
			}

			//var_dump( 'after: ', $query, $params );

			$statement	= $this->prepare( $query );
			return $this->execute( $statement, $params );
		}
	}


	private function prepare( $query )
	{
		$statement = $this->mysqli->prepare( $query );

		if( $statement === false )
		{
			throw new Exception( "Prepare failed(" . $this->mysqli->errno . "): " . $this->mysqli->error );
		}

		return $statement;
	}


	private function execute( $statement, $params = null )
	{
		if( !empty( $params ) )
		{
			$statement	= $this->bind_params( $statement, $params );
		}

		if( $statement->execute() === false )
		{
			throw new Exception( "Execute failed(" . $statement->errno . "): " . $statement->error );
		}

		return $this->fetch( $statement );
	}


	/**
	 * Calls "bind_param" fn on a prepared statement with a variable number of parameters
	 *
	 * @param mysqli prepared statement [$statement] The prepared statement.
	 * @param array [$params] A list of parameters that will be bound to the statement.
	 *
	 * @return mysqli prepared statement [$statement] The prepared statement.
	 */
	private function bind_params( $statement, $params )
	{
		$bind_types	= '';
		$n = count( $params );

		// get bind types
		for( $i = 0; $i < $n; $i++ )
		{
			$param	= $params[$i];

			if( $param === null )
			{
				$params[$i]	= '';
				$bind_types	.= 's';
			}
			else
			{
				$bind_type	= substr( gettype( $param ), 0, 1 );	// get first letter of $param's data type

				if( empty( $bind_type ) )
				{
					throw new Exception( 'Argument "' . (string)$param . '" has type ' . strtoupper( gettype( $param ) ) . ', which is incompatible with bind_params()' );
				}

				$bind_types	.= $bind_type;
			}
		}
		
		$bind_params	= array();

		// add $bind_types string to $bind_params array
		$bind_params[] = $bind_types;

		// add params
		for( $i = 0; $i < $n; $i++ )
		{
			/* with call_user_func_array, array params must be passed by reference */
			$bind_params[] = &$params[$i];
		}

		//var_dump( $bind_params );

		/*
		 * The reason we use call_user_func_array() to call bind_param() instead of calling it directly
		 * is that call_user_func_array() allows us to pass a variable number of parameters (as an array) to bind_param()
		 */
		if( !call_user_func_array( array( $statement, 'bind_param' ), $bind_params ) )
		{
			throw new Exception( "Parameter bind failed: (" . $statement->errno . ") " . $statement->error );
		}

		return $statement;
	}


	/**
	 * Fetches all rows from a result set - either normal or prepared
	 *
	 * @author: nieprzeklinaj@gmail.com, Garrett Sens
	 * source: http://php.net/manual/en/mysqli-stmt.bind-result.php comment 1
	 *
	 * @param mysqli_stmt or mysqli_result [$mysqli_object]
	 * @return array [$results] A two-dimensional array. First dimension (array[]) is indexed. Second dimension (array[][]) is associative as column name -> value
	 */
	private function fetch( $mysqli_object )
	{
		if( $mysqli_object instanceof mysqli_stmt )
		{
			return $this->fetch_statement( $mysqli_object );
		}
		else if( $mysqli_object instanceof mysqli_result )
		{
			$results	= array();
			while( $row = $mysqli_object->fetch_assoc() )
			{
				$results[] = $row;
			}
			return $results;
		} else
		{
			throw new Exception( 'Type of first parameter must be either mysqli_stmt or mysqli_result' );
		}
	}


	private function fetch_statement( $statement )
	{
		// if PHP > 5.3
		if( $this->php_5_4 )
		{
			// for non-SELECT queries. See http://php.net/manual/en/mysqli-stmt.result-metadata.php (comment 1)
			if( $statement->result_metadata() === false )
			{
				return true;
			}	

			$result = $statement->get_result();
			if( $result === false )
			{
				throw new Exception( "Failed to get result(" . $statement->errno . "): " . $statement->error );
			}

			$statement->free_result(); // do I need to do this?
			$statement->close();
			return $this->fetch( $result );
		}
		// if PHP <= 5.3
		else
		{
			if( $statement->store_result() === false )
			{
				throw new Exception( "Failed to store result. Error(" . $statement->errno . "): " . $statement->error );
			}
			
			$metadata	= $statement->result_metadata();

			// for non-SELECT queries. See http://php.net/manual/en/mysqli-stmt.result-metadata.php (comment 1)
			if( $metadata === false )
			{
				return true;
			}
			
			/*
			 * bind column names to statement
			 */
			$parameters	= array();
			$row		= array();
			while( $field = $metadata->fetch_field() )
			{
				$column_name	= $field->name;
				$parameters[]	= &$row[ $column_name ]; // pass by reference (because of 'bind_result')
			}
			
			call_user_func_array( array( $statement, 'bind_result' ), $parameters );
			
			$results	= array();		
			while( $statement->fetch() )
			{
				$inner_array	= array();
				foreach( $row as $k => $v )
				{
					$inner_array[$k] = $v;
				}

				// add inner array to outer one to make two-dimensional array
				$results[]	= $inner_array; // original author: "don't know why, but when I tried $inner_array[] = $row, I got the same one result in all rows"
			}
			$statement->close();
			return $results;
		}
	}


	/**
	 * Combines a mysql query fragment with the keys from the associative
	 * array parameter passed to the constructor, producing a syntactically-
	 * correct mysql query.
	 *
	 *    Example:
	 *    Given:
	 *        $query_fragment = "INSERT INTO `my_table`";
	 *        $my_table_values = array(
	 *            'Person' => 'Bob Jones',
	 *            'Company => 'BJ Manufacturing'
	 *        );
	 *        database->query( $query_fragment, $my_table_values );
	 *
	 *    "build_query_from_associative_array( $query_fragment, array_keys( $my_table_values ) )" would produce this query:
	 *        "INSERT INTO `my_table` (Person, Company) VALUES (?, ?)"
	 */
	private function build_query_from_associative_array( $query_fragment, $param_keys )
	{
		$query		= '';
		$query_type	= strtolower( substr( $query_fragment, 0, 6 ) );

		/*
		 * major mysql query types:
		 * Select
		 * Insert
		 * Update
		 * Delete
		 * Replace
		 * Do
		 * Handler
		 */

		switch( $query_type )
		{
			case 'select':

				$match_found = preg_match( '/^SELECT (\S+) FROM [`\'"]?(\w+)[`\'"]?(.*)/i', $query_fragment, $matches );
				if( $match_found !== 1 )
				{
					throw new Exception( "Incorrect " . $query_type . " query syntax." );
				}
				$select_clause	= $matches[1];
				$table_name		= $matches[2];
				$query			= "SELECT " . $select_clause . " FROM " . $table_name . " WHERE ";
				$query			= $this->add_where_clause( $query, $param_keys );

				// add rest of query
				if( count( $matches === 4 ) )
				{
					$rest_of_query	= $matches[3];
					$query .= $rest_of_query;
				}

				break;

			case 'insert':

				$match_found = preg_match( '/^INSERT INTO [`\'"]?(\w+)[`\'"]?(.*)/i', $query_fragment, $matches );
				if( $match_found !== 1 )
				{
					throw new Exception( "Incorrect " . $query_type . " query syntax." );
				}
				$table_name	= $matches[1];
				$query		= 'INSERT INTO `' . $table_name . '`';
				$query		= $this->add_columns_values_clause( $query, $param_keys );

				// add rest of query
				if( count( $matches ) === 3 )
				{
					$query .= $matches[2];
				}

				break;

			case 'update':

				// check if WHERE clause is given
				$match_found	= preg_match( '/^UPDATE [`\'"]?(\w+)[`\'"]?( WHERE .*)?$/i', $query_fragment, $matches );
				if( $match_found !== 1 )
				{
					throw new Exception( "Incorrect " . $query_type . " query syntax." );
				}
				//var_dump( 'matches:', $matches );
				$table_name	= $matches[1];
				$query		= "UPDATE `" . $table_name . "` SET ";
				$query		= $this->add_set_clause( $query, $param_keys );

				// add where clause and rest of query
				if( count( $matches ) === 3 )
				{
					$where_clause	= $matches[2];
					$query 			.= $where_clause;
				}

				break;

			case 'delete':

				$match_found	= preg_match( '/^DELETE FROM [`\'"]?(\w+)[`\'"]?(.*)/i', $query_fragment, $matches );
				if( $match_found !== 1 )
				{
					throw new Exception( "Incorrect " . $query_type . " query syntax." );
				}

				$table_name = $matches[1];
				$query		= 'DELETE FROM ' . $table_name . ' WHERE ';
				$query		= $this->add_where_clause( $query, $param_keys );

				// add rest of query
				if( count( $matches ) === 3 )
				{
					$query .= $matches[2];
				}

				break;

			default:

				throw new Exception( "Unexpected query type." );
		}
		
		return $query;
	}


	private function add_where_clause( $query, $param_keys )
	{
		for( $i = 0, $l = count( $param_keys ) - 1; $i <= $l; $i++ )
		{
			if( $i !== 0 )
			{
				$query .= ' AND ';
			}

			$key	= $param_keys[$i];
			$query .= $key . "=?";
		}
		return $query;
	}


	private function add_columns_values_clause( $query, $param_keys )
	{
		$column_str_builder	= '(';
		$values_str_builder = 'VALUES (';

		for( $i = 0, $l = ( count( $param_keys ) - 1 ); $i <= $l; $i++ )
		{
			$key = $param_keys[$i];
			if( $i < $l )
			{
				$column_str_builder .= "`$key`, ";
				$values_str_builder .= "?, ";
			}
			else
			{
				$column_str_builder .= "`$key` ) ";
				$values_str_builder .= "?)";
			}
		}

		return $query . $column_str_builder . $values_str_builder;
	}


	private function add_set_clause( $query, $param_keys )
	{
		for( $i = 0, $l = count( $param_keys ) - 1; $i <= $l; $i++ )
		{
			$key	= $param_keys[$i];

			if( $i < $l )
			{
				$query .= $key . "=?, ";
			}
			else
			{
				$query .= $key . "=?";
			}
		}
		return $query;
	}


	/*
	 * Eliminate keys that do not exist in the table
	 */
	private function reduce_params( $table_name, $assoc_params )
	{
		$information_schema	= new Database( $this->host, $this->username, $this->password, 'information_schema' );
		$column_name_query	= "SELECT `COLUMN_NAME` FROM `COLUMNS` WHERE `TABLE_SCHEMA` = '$this->name' AND `TABLE_NAME` = '$table_name'";
		$column_names		= $information_schema->query( $column_name_query );
		$reduced_params		= array();

		//var_dump( $column_names );

		// build new array with values for only those columns that exist in the table
		foreach( $column_names as $column_name )
		{
			if( isset( $assoc_params[ $column_name ] ) )
			{
				$reduced_params[ $column_name ]	= $assoc_params[ $column_name ];
			}
		}

		return $reduced_params;

	}


	/**
	 * Checks for duplicate record in database
	 *
	 * @param string [$table_name] The name of the table you want to check for duplicates
	 * @param assoc. array [$data] An associative array where each key is the name of a column in a MySQL table
	 * and the value is checked against existing values in the table
	 * @param string or array [$exception_keys] Optional. A list of keys that will not be matched against $table_name
	 * @return boolean Whether the values from $data matched the values in $table_name table
	 */
	public function is_duplicate( $table_name, $data, $exception_keys = array() )
	{
		// remove $exception keys from $data
		if( count( $exception_keys ) > 0 )
		{
			foreach( $exception_keys as $exception_key )
			{
				unset( $data[ $exception_key ] );
			}
		}

		return( count( $this->get_duplicate_records( $table_name, $data ) ) > 0 );
	}


	/**
	 * Returns duplicate record in database, if any
	 *
	 * @param string [$table_name] The name of the table you want to check for duplicates
	 * @param assoc. array [$data] An associative array where each key is the name of a column in a MySQL table
	 * and the value is checked against existing values in the table
	 * @return assoc. array The result of the "SELECT *" on $table_name table
	 */
	private function get_duplicate_records( $table_name, $data )
	{
		// get column names from table. Source: http://stackoverflow.com/questions/4165195/mysql-query-to-get-column-names
		$column_data_query		= <<<SQL
SELECT `COLUMN_NAME`,`CHARACTER_MAXIMUM_LENGTH`
FROM `INFORMATION_SCHEMA`.`COLUMNS`
WHERE `TABLE_SCHEMA`= ?
AND `TABLE_NAME`= ?
SQL;
		$column_data			= $this->query( $column_data_query, array( $this->name, $table_name ) );
		//print_r( $column_data[0][ 'COLUMN_NAME' ] );
		//var_dump( 'data: ', $data );

		/*
		 * build query for duplicate records check
		 */

		$duplicate_check_query	= "SELECT * FROM `$table_name`";
		$values					= array();
		$where_bool				= false;

		for( $i = 0, $n = count( $column_data ); $i < $n; $i++ )
		{
			$column_data_array	= $column_data[$i];
			$column_name		= $column_data_array[ 'COLUMN_NAME' ];
			$max_char_length	= $column_data_array[ 'CHARACTER_MAXIMUM_LENGTH' ];

			// if the data argument contains data for the column name from information_schema table
			if( isset( $data[ $column_name ] ) )
			{
				$value	= $data[ $column_name ];

				// impose max character limit so duplicate check data is exactly how it would be in database
				if( $max_char_length > 0 )
				{
					$value	= substr( $value, 0, $max_char_length );
				}

				array_push( $values, $value );

				$duplicate_check_query	.= "\n";

				// use 'WHERE' the first time and 'AND' every other time
				if( !$where_bool )
				{
					$duplicate_check_query	.= 'WHERE ';
					$where_bool				= true;
				} else
				{
					$duplicate_check_query	.= 'AND ';
				}
				
				$duplicate_check_query	.= "`$table_name`.`$column_name` = ?";
			}
		}

		//var_dump( $duplicate_check_query, $values );

		$duplicate_records	= $this->query( $duplicate_check_query, $values );

		//var_dump( $duplicate_records );

		return( $duplicate_records );
	}

} // end Database class


function connect_to_plasmacam_database( $config_array )
{
	if( !isset( $config_array['database'] ) )
	{
		throw new Exception( "Empty \$config_array[ 'database' ]." );
	}

	$database_login	= $config_array['database'];
	$database		= new Database(
		$database_login['host'],
		$database_login['username'],
		$database_login['password'],
		$database_login['name']
	);
	
	return $database;
}


function test_database_class( $table_name )
{
	$mysql = new Database( 'localhost', 'root', 'test', 'plasmacam_dev' );
	
	$insert_data	= array(
		'PartNum'	=> '123',
		'Descr'		=> 'This is a thing.',
		'Price'		=> '100',
		'Weight'	=> '100',
		'Detail'	=> 'Thing',
		'Photo'		=> 'NULL',
		'Category'	=> 'things'
	);

	$insert_data2	= array(
		'PartNum'	=> '1234',
		'Descr'		=> 'This is a thing.2',
		'Price'		=> '1002',
		'Weight'	=> '1002',
		'Detail'	=> 'Thing2',
		'Photo'		=> 'NULL2',
		'Category'	=> 'things2'
	);

	var_dump( $mysql->query( "INSERT INTO $table_name", $insert_data ) );
	var_dump( $mysql->query( "SELECT `PartNum` FROM $table_name", $insert_data ) );

	//var_dump( $mysql->query( 'DELETE FROM $table_name', $insert_data ) );

	var_dump( $mysql->query( "UPDATE $table_name WHERE `PartNum`='123'", $insert_data2 ) );
	var_dump( $mysql->query( "SELECT `PartNum` FROM $table_name", $insert_data2 ) );

	var_dump( $mysql->query( "DELETE FROM $table_name", $insert_data2 ) );
}

?>