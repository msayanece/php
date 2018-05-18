<?php
/*********************************************************************************
 * This PHP file defines the library classes.
 * 
 * Class MySQLManager - Defines and Wraps the different MySQL functions
 *                      
 * Class CMS - Defines and Wraps the different common site activities. 
 *
**********************************************************************************/

/***************************************************************************
*                             class.MySQLManager
*                            --------------------
*   begin                : Wednessday, Jan 10, 2007
*   upgrade              : Tuesday, Jan 10, 2012
*   copyright            : Encoders
*
*   $Id: class.common.php, v 2.1 2012/01/10 11:10:01 
*
*	Defines and Wraps the different MySQL functions
*
***************************************************************************/
class MySQLManager {		
	/**
	* Name:				__construct() [class constructor]
	* Params:			varchar sqlserver, varchar sqluser, varchar sqlpassword, varchar database, boolean persistency
	* Returns:			null
	* Description:		Create an instance of the class 'MySQLManager' and make a database connection. 
	*
	*/
	function __construct($sqlserver, $sqluser, $sqlpassword, $database, $persistency = true){
		$this->persistency = $persistency;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->server = $sqlserver;
		$this->dbname = $database;
		$this->queryCounter = 0;
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				sql_connect()
	* Params:			null
	* Returns:			connection object/false
	* Description:		Makes a database connection.
	*
	*/
	
	function sql_connect($runConfigs=false){		
		$this->db_connect_id = mysqli_connect($this->server, $this->user, $this->password, $this->dbname);
		
		if (mysqli_connect_error()) {
			$this->db_connect_errno = mysqli_connect_errno();
			$this->db_connect_err = mysqli_connect_error();
			$this->kill_sql("Database Connection Error : ".$this->db_connect_errno." <br> '<span style='font-weight:normal;'>".nl2br($this->db_connect_err)."</span>'");
		}
		
		$this->db_connect_host_info = mysqli_get_host_info ($this->db_connect_id);
		
		if($runConfigs){
			$this->sql_query("SET SQL_BIG_SELECTS=1");
			$this->sql_query("SET CHARACTER SET utf8");
			$this->sql_query("SET SESSION collation_connection ='utf8_general_ci'");
			$this->sql_query("SET character_set_results=utf8");
		}
		
		return $this->db_connect_id;
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				sql_close()
	* Params:			null
	* Returns:			null
	* Description:		Closes the database connection.
	*
	*/

	function sql_close(){
		if($this->db_connect_id){
			if($this->query_result){
				@mysqli_free_result($this->query_result);
			}
			$result = @mysqli_close($this->db_connect_id);
			return $result;
		}else{
			return false;
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				sql_query_old()
	* Params:			varchar query
	* Returns:			result set
	* Description:		Runs a SQl Query (old way).
	*/

	function sql_query_old($query = ""){
		if($query == ""){
			$this->kill_sql("Query not set.");
		}		
		$this->sql_freeresult();
		$this->query_operation_type = 'OLD';
		
		$qCounter = $this->queryCounter;
		$this->query_old_store[$qCounter] = array();

		$this->query_to_execute = $query;
		$this->query_result = @mysqli_query($this->db_connect_id, $query);	
		$this->query_old_store[$qCounter]['_RESULT_'] = $this->query_result;
		if($this->query_result){
			if($this->_startsWith(strtoupper($this->query_to_execute), 'SELECT')){
				$this->numrows =  @mysqli_num_rows($this->query_result);
				$this->query_old_store[$qCounter]['_NUMROWS_'] = $this->numrows;
				while($row = @mysqli_fetch_array($this->query_result))
				{
					$q_rows[] = $row;
				}
				$this->fetchrow = $q_rows;	
				$this->query_old_store[$qCounter]['_RECORDS_'] = $this->fetchrow;
				$this->query_old_store[$qCounter]['_RECORDS_POINTER_'] = 0;
			}
			elseif($this->_startsWith(strtoupper($this->query_to_execute), 'INSERT')){
				$this->insertedId = @mysqli_insert_id($this->db_connect_id);
				$this->query_old_store[$qCounter]['_INSERT_ID_'] = $this->insertedId;
			}
			elseif($this->_startsWith(strtoupper($this->query_to_execute), 'UPDATE')){
				$this->affectedRow = @mysqli_affected_rows($this->db_connect_id);
				$this->query_old_store[$qCounter]['_AFFECTED_ROW_'] = $this->affectedRow;
			}
			$this->queryCounter++;
			return "#QRESORSE:".$qCounter;
		}else{
			$this->kill_sql("Cannot execute query <br> '<span style='font-weight:normal;'>".nl2br($query)."</span>'");
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				sql_query()
	* Params:			varchar query
	* Returns:			result set
	* Description:		Runs a SQl Query.
	*
	*/

	function sql_query($query = ""){
		if($query == ""){
			$this->kill_sql("Query not set.");
		}		
		$this->sql_freeresult();
		$this->query_operation_type = 'REGULAR';
		$this->query_to_execute = $query;
		$this->query_result = @mysqli_query($this->db_connect_id, $query);	
		if($this->query_result){
			if($this->_startsWith(strtoupper($this->query_to_execute), 'SELECT')){
				$this->numrows =  @mysqli_num_rows($this->query_result);
				while($row = @mysqli_fetch_array($this->query_result))
				{
					$q_rows[] = $row;
				}
				$this->fetchrow = $q_rows;				
			}
			elseif($this->_startsWith(strtoupper($this->query_to_execute), 'INSERT')){
				$this->insertedId = @mysqli_insert_id($this->db_connect_id);
			}
			elseif($this->_startsWith(strtoupper($this->query_to_execute), 'UPDATE')){
				$this->affectedRow = @mysqli_affected_rows($this->db_connect_id);
			}
			return true;
		}else{
			$this->kill_sql("Cannot execute query <br> '<span style='font-weight:normal;'>".nl2br($query)."</span>'");
		}
	}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				execSQL()
	* Params:			varchar query
	* Returns:			result set
	* Description:		Prepares A SQL Query and Executes it.
	*
	*/
	
	function execSQL($query, $params){
		if($query == ""){
			$this->kill_sql("Query not set.");
		}
		
		$this->sql_freeresult();		
		$this->query_operation_type = 'PREPARED';
		$this->query_stmt = @mysqli_prepare ($this->db_connect_id, $query);	
		$this->query_to_execute = $query;
		
		if(!$this->query_stmt){
			$this->kill_sql("There is an error in statement <br> '<span style='font-weight:normal;'>".nl2br($query)."</span>'");
		} else  {	
			@call_user_func_array(array($this->query_stmt, 'bind_param'), $this->_refValues($params));
			if(@mysqli_stmt_execute($this->query_stmt)){
				if($this->_startsWith(strtoupper($this->query_to_execute), 'SELECT')){
					
					$this->query_result = mysqli_stmt_result_metadata($this->query_stmt);				   
					while ( $field = @mysqli_fetch_field($this->query_result) ) {
						$parameters[] = &$row[$field->name];
					}  
					
					@call_user_func_array(array($this->query_stmt, 'bind_result'), $this->_refValues($parameters));
				   
					while ( @mysqli_stmt_fetch($this->query_stmt) ) {  
						$x = array();  
						foreach( $row as $key => $val ) {  
							$x[$key] = $val;  
						}  
						$results[] = $x;  
					}
					$this->fetchrow = $results;
					$this->numrows =  sizeof($results);
				}
				elseif($this->_startsWith(strtoupper($this->query_to_execute), 'INSERT')){
					$this->insertedId = @mysqli_insert_id($this->db_connect_id);
					$this->affectedRow = @mysqli_stmt_affected_rows($this->query_stmt);
				}
				elseif($this->_startsWith(strtoupper($this->query_to_execute), 'UPDATE')){
					$this->affectedRow = @mysqli_stmt_affected_rows($this->query_stmt);
				}
				
				$this->insert_database_log($this->query_to_execute);
				
				@mysqli_stmt_close($this->query_stmt);				
				return true;
			} else {
				$error = $this->sql_error();
				$this->kill_sql("Cannot execute query <br> '<span style='font-weight:normal;'>".nl2br($query)."</span>'<br>".$error['message']);
			}
		}
   }


////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				insert_database_log()
	* Params:			void
	* Returns:			
	* Description:		
	*
	*/

	function insert_database_log($query_sql){
		$this->execute_query	=	$query_sql;
		
		if($this->_startsWith(strtoupper($this->execute_query), 'SELECT')){
			$this->execute_query_type	=	'SELECT';
		}
		else if($this->_startsWith(strtoupper($this->execute_query), 'INSERT')){
			$this->execute_query_type	=	'INSERT';
		}
		else if($this->_startsWith(strtoupper($this->execute_query), 'UPDATE')){
			$this->execute_query_type	=	'UPDATE';
		}
		
		$this->tableExist	=	$this->exist_database_log_table();
		
		if($this->tableExist==0){
			$this->creatQuery	=	"CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."log` (
									  `id` int(20) NOT NULL AUTO_INCREMENT,
									  `date` varchar(255) DEFAULT NULL,
									  `ipAddress` varchar(255) DEFAULT NULL,
									  `tableName` varchar(255) DEFAULT NULL,
									  `type` varchar(255) DEFAULT NULL,
									  `query` varchar(255) DEFAULT NULL,
									  `remarks` varchar(255) DEFAULT NULL,
									  `userId` varchar(255) DEFAULT NULL,
									  `status` enum('A','I','D') NOT NULL DEFAULT 'A',
									  `createdDate` datetime DEFAULT NULL,
									  `createdIp` varchar(255) DEFAULT NULL,
									  `createdSessionId` varchar(255) DEFAULT NULL,
									  `modifiedDate` datetime DEFAULT NULL,
									  `modifiedIp` varchar(255) DEFAULT NULL,
									  `modifiedSessionId` varchar(255) DEFAULT NULL,
									  PRIMARY KEY (`id`)
									) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1" ; 
									
			$this->exeQuery		=	@mysqli_query($this->db_connect_id,$this->creatQuery);
			
		}
		//echo $this->execute_query_type;die();
		if($this->execute_query_type!='SELECT'){
			$this->setQuery	=	"INSERT INTO `".TABLE_PREFIX."log`
									SET
										`date`				=	'".date('Y-m-d')."',
										`ipAddress`			=	'".$_SERVER['REMOTE_ADDR']."',
										`tableName`			=	' ',
										`type`				=	'".$this->execute_query_type."',
										`query`				=	'".$this->execute_query."',
										`remarks`			=	'QUERY EXECUTED RECORD',
										`userId`			=	'".$_SESSION['login_id']."',
										`createdSessionId`	=	'".session_id()."'";
										
			$this->runQuery	=	@mysqli_query($this->db_connect_id,$this->setQuery);
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				exist_database_log_table()
	* Params:			void
	* Returns:			string num rows
	* Description:		Return the sql table exist or not
	*
	*/

	function exist_database_log_table(){
		$this->sqlLog		=	@mysqli_query($this->db_connect_id,"SHOW TABLES LIKE '".TABLE_PREFIX."log'");
		$this->numrowlog	=	@mysqli_num_rows($this->sqlLog);
		return $this->numrowlog;
	}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				query_operation_type()
	* Params:			void
	* Returns:			string operation type
	* Description:		Returns the sql operation type.
	*
	*/

	function query_operation_type(){
		return $this->query_operation_type;
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				sql_numrows()
	* Params:			void
	* Returns:			row Count
	* Description:		Returns the number of rows.
	*
	*/

	function sql_numrows(){
		return $this->numrows;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* @Defuncted
	* Name:				sqlnumrows()
	* Params:			varchar queryId
	* Returns:			row Count
	* Description:		Returns the number of rows.
	*
	*/	
	function sqlnumrows($res){
		$this->kill_sql("Invalid method '<span style='font-weight:normal;'>sqlnumrows</span>'<br><br>Use `sql_numrows()`");
		return $this->numrows;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:				sql_fetchrow()
	* Params:			null
	* Returns:			array
	* Description:		Returns a row of the resultset.
	*
	*/
	
	function sql_fetchrow($queryId=''){
		if($queryId!='' && $this->_startsWith($queryId, '#QRESORSE'))
		{
			$exp = explode(":",$queryId);
			if(is_numeric($exp[1]))
			{
				$res = intval($exp[1]);
				$qData = $this->query_old_store[$res];
				$pointer = $qData['_RECORDS_POINTER_'];
				$data = $qData['_RECORDS_'][$pointer];
				if(!empty($data))
				{
					 $this->query_old_store[$res]['_RECORDS_POINTER_'] = $pointer+1;
					 return $data;
				}
				else
				{
					return false;
				}
			}
			else
			{
				$this->kill_sql("Invalid resource '<span style='font-weight:normal;'>".$queryId."</span>'");	
			}
		}
		else
		{	
			return $this->fetchrow;
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* @Defuncted
	* Name:				sqlfetchrow()
	* Params:			null
	* Returns:			array
	* Description:		Returns a row of the resultset.
	*
	*/		
	function sqlfetchrow($res){
		$this->kill_sql("Invalid method '<span style='font-weight:normal;'>sqlfetchrow</span>'<br><br>Use `sql_fetchrow()`");
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
		   $a_data[] =  $row;
		}
		return $a_data;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				sql_affectedrows()
	* Params:			null
	* Returns:			int row count
	* Description:		Returns the affected row count.
	*
	*/
	
	function sql_affectedrows(){
		return $this->affectedRow;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:				sql_insert_id()
	* Params:			null
	* Returns:			int row count
	* Description:		Returns the ID generated in the last query.
	*
	*/
	
	function sql_insert_id(){
		return $this->insertedId;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:				sql_freeresult()
	* Params:			null
	* Returns:			null
	* Description:		frees the result set.
	*
	*/
	
	function sql_freeresult(){
		if(isset($this->query_result) && $this->query_result != NULL)
		{
			@mysqli_free_result($this->query_result);
		}
		$this->query_result 		= NULL;
		$this->query_operation_type = NULL;
		$this->query_to_execute 	= NULL;
		$this->query_result 		= NULL;
		$this->numrows 				= NULL;
		$this->query_stmt 			= NULL;
		$this->fetchrow 			= NULL;
		$this->insertedId 			= NULL;
		$this->affectedRow 			= NULL;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				sql_error()
	* Params:			null
	* Returns:			null
	* Description:		Returns the mysql error.
	*
	*/

	function sql_error($query_id = 0){
		$result["message"] = @mysqli_error($this->db_connect_id);
		$result["code"] = @mysqli_errno($this->db_connect_id);
		return $result;
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:			kill_sql()
	* Params:		varchar
	* Returns:		void 
	* Description:	stops execution
	*/
	
	function kill_sql($message){
			$kill ="\n<div align=\"center\"  style = \"BORDER: 1px solid; FONT-WEIGHT: bold; FONT-SIZE: 10px; COLOR: #0000CC; BACKGROUND-COLOR: #ffffff; FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; padding:3px;\">\n";
			$kill.="<B>" . $message . "</B><br/>\n";
			$kill.="</div>\n";	
			die($kill);
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:			_refValues()
	* Params:		array
	* Returns:		array 
	* Description:	converts params to referencial values
	*/
		
	function _refValues($arr){
        if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		_startsWith()
	* Params:		varchar,varchar
	* Returns:		boolean
	* Description:	check whether a string starts with a certain char sequence.
	*
	*/
		
	function _startsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}		

} 

// End of class MySQLManager


/***************************************************************************
*                             	class.CMS
*                            ----------------
*   begin                : Saturday, Dec 03, 2006
*   upgrade              : Tuesday, Jan 10, 2012
*   copyright            : Encoders
*
*   $Id: class.common.php,v 2.0 2012/01/10 11:10:01  
*
*	Defines and Wraps the different common system activities. 
*
***************************************************************************/

class CMS { 

	/**
	* Name:				__construct() [class constructor]
	* Params:			null
	* Returns:			null
	* Description:		Create an instance of the class 'CMS' and make a database connection.
	* Access:			System
	*/
	function __construct($runConfigs=false){
		global $cfg;
		
		$this->memusage = memory_get_usage();
						
		$this->sqlIndex = 0;
		$this->sessionMGMT_session = '_'.$cfg['SERVICE_TAG'].'__SESS10N__';
		$this->pageTrail_session = '_'.$cfg['SERVICE_TAG'].'_PGTRAIL_';
		$this->pageTrail_recCount = 2;
		
		$this->meta_records_table = "_meta_records_";
		$this->page_trail_table = "_pagetrail_";
		
		if(trim(session_id())==''){
			session_id($this->getRandom(16,'alphanum'));
		}
		
		$this->currentDB = "__default__";
		$this->queryManager[$this->currentDB] = new MySQLManager(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, false);
		$this->defaultQueryManager = $this->queryManager[$this->currentDB];
		
		$this->dbConnection = $this->defaultQueryManager->sql_connect($runConfigs);
		
		$this->ip = $this->_ipCheck();
		$this->pgTrail = $this->_trail();
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:			__call()
	* Params:		null
	* Returns:		void 
	* Description:	calls the methods of other functions directly through this function
	* Access:		System
	*/	
	
	function __call($method, $args){
		if(method_exists($this, $method)){
			return call_user_func_array(array($this,$method), $args);
		} else if($this->defaultQueryManager != NULL && method_exists($this->defaultQueryManager, $method)){
			return call_user_func_array(array($this->defaultQueryManager,$method), $args);
		} elseif($this->thumbnailManager != NULL && method_exists($this->thumbnailManager, $method)){
			return call_user_func_array(array($this->thumbnailManager,$method), $args);
		} elseif($this->graphManager != NULL && method_exists($this->graphManager, $method)){
			return call_user_func_array(array($this->graphManager,$method), $args);
		} elseif($this->pieManager != NULL && method_exists($this->pieManager, $method)){
			return call_user_func_array(array($this->pieManager,$method), $args);
		} elseif($this->stackChartManager != NULL && method_exists($this->stackChartManager, $method)){
			return call_user_func_array(array($this->stackChartManager,$method), $args);
		} elseif($this->eMailManager != NULL && method_exists($this->eMailManager, $method)){
			return call_user_func_array(array($this->eMailManager,$method), $args);
		} elseif($this->smsManager != NULL && method_exists($this->smsManager, $method)){
			return call_user_func_array(array($this->smsManager,$method), $args);
		} else {
			$this->kill("unknown function ".$method);
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	
//		DATABASE RELATED FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:				addDatabase()
	* Params:			varchar index, varchar sqlserver, varchar sqluser, varchar sqlpassword, varchar database
	* Returns:			void 
	* Description:		Create a new instance of the class 'MySQLManager' and make a database connection.
	* Access:			Public 
	*/	
		
	function addDatabase($index, $sqlserver, $sqluser, $sqlpassword, $database){
		$this->queryManager[$index] = new MySQLManager($sqlserver, $sqluser, $sqlpassword, $database, false);
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:				clearDBConnections()
	* Params:			null
	* Returns:			void 
	* Description:		closes all DB Connections
	* Access:			Public 
	*/
	
	function clearDBConnections(){
		foreach($this->queryManager as $index=>$conOb){
			$conOb->sql_close();
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:				setDatabase()
	* Params:			varchar index
	* Returns:			void 
	* Description:		sets the working Database. 
	* Access:			Public 
	*/
	
	function setDatabase($index){
		$this->clearDBConnections();
		$this->currentDB = $index;
		$this->defaultQueryManager = $this->queryManager[$this->currentDB];
		$this->dbConnection = $this->defaultQueryManager->sql_connect();
		if(!$this->defaultQueryManager->db_connect_id){
			$this->kill("Could not connect to the database ".$this->queryManager[$index]->dbname);		
		}		
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:				resetDatabase()
	* Params:			varchar index
	* Returns:			void 
	* Description:		resets the working Database to the default. 
	* Access:			Public 
	*/
		
	function resetDatabase(){
		$this->clearDBConnections();
		$this->currentDB = "__default__";
		$this->defaultQueryManager = $this->queryManager[$this->currentDB];
		$this->dbConnection = $this->defaultQueryManager->sql_connect();
		if(!$this->defaultQueryManager->db_connect_id){
			$this->kill("Could not connect to the database ".$this->queryManager["__default__"]->dbname);		
		}		
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:				revertDatabase()
	* Params:			varchar index
	* Returns:			void 
	* Description:		sets the Database to the previously working Database. 
	* Access:			Public 
	*/
		
	function revertDatabase(){
		$this->clearDBConnections();		
		$this->currentDB = $this->previousDB;
		$this->previousDB = "__default__";
		$this->defaultQueryManager = $this->queryManager[$this->currentDB];
		$this->dbConnection = $this->defaultQueryManager->sql_connect();
		if(!$this->defaultQueryManager->db_connect_id){
			$this->kill("Could not connect to the database ".$this->queryManager["__default__"]->dbname);		
		}		
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				sql_query()
	* Params:			varchar query, boolean meta
	* Returns:			result set
	* Description:		Wrapper function to MySQLManager::sql_query
	* Access:			Public
	*/
	
	function sql_query($query = "", $meta=true) {
		$testQ = strtoupper(trim($query));
		if($this->_startsWith($testQ, 'SELECT')){			
			//$this->kill("Using improper function sql_query for <br><br><i style='font-weight:normal;'>".$query."</i><br><br>use sql_select instead");
			return $this->defaultQueryManager->sql_query_old($query);
		} else if($this->_startsWith($testQ, 'INSERT')){			
			//$this->kill("Using improper function sql_query for <br><br><i style='font-weight:normal;'>".$query."</i><br><br>use sql_insert instead");
			return $this->defaultQueryManager->sql_query_old($query);
		} else if($this->_startsWith($testQ, 'UPDATE')) {
			//$this->kill("Using improper function sql_query for <br><br><i style='font-weight:normal;'>".$query."</i><br><br>use sql_update instead");
			return $this->defaultQueryManager->sql_query_old($query);
		} else if($this->_startsWith($testQ, 'DELETE')) {
			//$this->kill("Using improper function sql_query for <br><br><i style='font-weight:normal;'>".$query."</i><br><br>use sql_delete instead");
			return $this->defaultQueryManager->sql_query($query);
		}else {
			$this->_sql_query($query, $meta);
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				_sql_query()
	* Params:			varchar query, boolean meta
	* Returns:			result set
	* Description:		Wrapper function to MySQLManager::sql_query
	* Access:			Private
	*/

	function _sql_query($query = "", $meta=false) {
		$query  = trim($query);				
		if($meta){
			if($this->_startsWith(strtoupper($query), 'INSERT')){
				$this->_meta_entry('INSERT', $query);
			} else if($this->_startsWith(strtoupper($query), 'UPDATE')) {
				$this->_meta_entry('UPDATE', $query);
			} else if($this->_startsWith(strtoupper($query), 'DELETE')) {
				$this->_meta_entry('DELETE', $query);
			}	
		}
		
		$processStartTime = $this->microtime_float();
		
		$result = $this->defaultQueryManager->sql_query($query);
		
		$processEndTime = $this->microtime_float();
		$executionTime = floatval($processEndTime) - floatval($processStartTime);
		
		//Audit Codes
		$this->sqlIndex++;
		$this->queryAudit[$this->sqlIndex]['host'] = $this->defaultQueryManager->db_connect_host_info;
		$this->queryAudit[$this->sqlIndex]['db'] = $this->defaultQueryManager->dbname;
		$queryType = explode(' ',$query,2);
		$this->queryAudit[$this->sqlIndex]['query_type'] = strtoupper(trim($queryType[0]));	
		$this->queryAudit[$this->sqlIndex]['query'] = nl2br($query);
		$this->queryAudit[$this->sqlIndex]['execution_time'] = $executionTime;		
		$this->queryAudit[$this->sqlIndex][(($queryType[0]=='SELECT')?'records_found':'affected_rows')] = ($queryType[0]=='SELECT')?$this->defaultQueryManager->sql_numrows():$this->defaultQueryManager->sql_affectedrows();
		
		return $result;
	}
		
////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		sql_audit()
	* Params:		varchar, varchar, varchar
	* Returns:		null
	* Description:	gets all the available tables in the Database.
	* Access:		Private	
	*/
	
	function sql_audit() {
		$auditDivStart = "\n<div align=\"left\"  style = \"BORDER: 1px solid; FONT-WEIGHT: normal; FONT-SIZE: 10px; COLOR: #cc3333; BACKGROUND-COLOR: #ffffff; FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; padding:3px;\">\n";
		$auditDivEnd = "</div>\n";
		$audit = "";
		$totalQueryTime = 0;
		foreach($this->queryAudit as $ind=>$auditLogs){
			$audit .= "\n<br>\n";
			$audit .= $auditDivStart;
			$audit.="<B>index</B>: " . $ind . "<br/>\n";
			foreach($auditLogs as $key=>$val){
				if($key==="result"){					
				}elseif($key==="query"){
					$audit.="<B>" . $key . "</B>: ";
					$audit .= $auditDivStart.$val.$auditDivEnd;	
				}elseif($key==="execution_time"){
					$audit.="<B>" . $key . "</B>: " . $val . " sec<br/>\n";
					$totalQueryTime += $val;
				}else{
					$audit.="<B>" . $key . "</B>: " . $val . "<br/>\n";
				}
			}
			$audit.= $auditDivEnd;
		}
		$audit .= '<br/>'.$auditDivStart."<B>total_query_time</B>: " . $totalQueryTime . " sec<br/>\n".$auditDivEnd;	
		return $audit;
	}
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				sql_insert()
	* Params:			varchar query, boolean meta
	* Returns:			newly inserted Id
	* Description:		Takes a insert query OR an predefined associated array to insert data into database table.
	* Access:			Public
	*/
	
	function sql_insert($queryContent = "", $meta=false){
		if(is_array($queryContent)){
			if(array_key_exists ( 'QUERY' , $queryContent ) && $this->_startsWith(strtoupper(trim($queryContent['QUERY'])), 'INSERT')){
				$query = $queryContent['QUERY'];
				$param = array();
				if(array_key_exists ( 'PARAM' , $queryContent )){
					$param[0] = '';
					$paramCount = 0;
					foreach($queryContent['PARAM'] as $k=>$dta)
					{
						$param[0] .= $dta['TYP'];
						$param[]   = $dta['DATA'];
						$paramCount++;
					}
					if($paramCount != strlen($param[0]) || substr_count($query, '?') != strlen($param[0]))
					{
						$this->kill("Parameter DataType Mismatch for `sql_insert` for <br><br><i style='font-weight:normal;'>".$query."</i><br><br><span style='font-weight:normal;'>".$this->_getValueOf($param)."</span><br><br>Please refer to the user manual");
					}
				}
				$this->defaultQueryManager->execSQL($query,$param);
			} else {
				$this->kill("Improper array structure for `sql_insert` for <br><br><i style='font-weight:normal;'><pre>".$this->_getValueOf($queryContent)."</pre></i><br><br>Please refer to the user manual");
			}		
		} else if($this->_startsWith(strtoupper(trim($queryContent)), 'INSERT')){
			$this->kill("Not supposed to implement standard query for `sql_insert` in <br><br><i style='font-weight:normal;'><pre>".$queryContent."</pre></i><br><br>use prepared statement.");
			$this->_sql_query($queryContent, $meta);
		} else {
			$this->kill("Improper function call `sql_insert` for <br><br><i style='font-weight:normal;'>".$queryContent."</i><br><br>Please refer to the user manual");
		}
		$affectedRow = $this->defaultQueryManager->sql_insert_id();
		return $affectedRow;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				sql_update()
	* Params:			varchar query, boolean meta
	* Returns:			no. of affected rows
	* Description:		Takes a update query OR an predefined associated array to insert data into database table.
	* Access:			Public
	*/
	
	function sql_update($queryContent = "", $meta=false){
		if(is_array($queryContent)){			
			if(array_key_exists ( 'QUERY' , $queryContent ) && $this->_startsWith(strtoupper(trim($queryContent['QUERY'])), 'UPDATE')){
				$query = $queryContent['QUERY'];
				$param = array();
				if(array_key_exists ( 'PARAM' , $queryContent )){
					$param[0] = '';
					$paramCount = 0;
					foreach($queryContent['PARAM'] as $k=>$dta)
					{
						$param[0] .= $dta['TYP'];
						$param[]   = $dta['DATA'];
						$paramCount++;
					}
					if($paramCount != strlen($param[0]) || substr_count($query, '?') != strlen($param[0]))
					{
						$this->kill("Parameter DataType Mismatch for `sql_insert` for <br><br><i style='font-weight:normal;'>".$query."</i><br><br><span style='font-weight:normal;'>".$this->_getValueOf($param)."</span><br><br>Please refer to the user manual");
					}
				}
				$this->defaultQueryManager->execSQL($query,$param);
			} else {
				$this->kill("Improper array structure for `sql_update` for <br><br><i style='font-weight:normal;'><pre>".$this->_getValueOf($queryContent)."</pre></i><br><br>Please refer to the user manual");
			}			
		} else if($this->_startsWith(strtoupper(trim($queryContent)), 'UPDATE')){
			$this->kill("Not supposed to implement standard query for `sql_update` in <br><br><i style='font-weight:normal;'><pre>".$queryContent."</pre></i><br><br>use prepared statement.");
			$this->_sql_query($queryContent, $meta);
		} else {
			$this->kill("Improper function call `sql_update` for <br><br><i style='font-weight:normal;'>".$queryContent."</i><br><br>Please refer to the user manual");
		}
		$affectedRow = $this->defaultQueryManager->sql_affectedrows();
		return $affectedRow;
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				sql_select()
	* Params:			varchar query, boolean meta
	* Returns:			result array
	* Description:		Wrapper Class to MySQLManager::sql_query
	* Access:			Public
	*/
	
	function sql_select($queryContent = "", $meta=false, $format=true){		
		if(is_array($queryContent)){
			if(array_key_exists ( 'QUERY' , $queryContent ) && $this->_startsWith(strtoupper(trim($queryContent['QUERY'])), 'SELECT')){
				$query = $queryContent['QUERY'];
				$param = array();
				if(array_key_exists ( 'PARAM' , $queryContent )){
					$param[0] = '';
					$paramCount = 0;
					foreach($queryContent['PARAM'] as $k=>$dta)
					{
						$param[0] .= $dta['TYP'];
						$param[]   = $dta['DATA'];
						$paramCount++;
					}
					if($paramCount != strlen($param[0]) || substr_count($query, '?') != strlen($param[0]))
					{
						$this->kill("Parameter DataType Mismatch for `sql_select` for <br><br><i style='font-weight:normal;'>".$query."</i><br><br><span style='font-weight:normal;'>".$this->_getValueOf($param)."</span><br><br>Please refer to the user manual");
					}
				}
				$this->defaultQueryManager->execSQL($query,$param);
				if($format) {
					$dataArray = $this->_format_select();
				} else {
					$dataArray = $this->defaultQueryManager->sql_fetchrow();
				}
			} else {
				$this->kill("Improper array structure for `sql_select` for <br><br><i style='font-weight:normal;'><pre>".$this->_getValueOf($queryContent)."</pre></i><br><br>Please refer to the user manual");
			}
		} else if($this->_startsWith(strtoupper(trim($queryContent)), 'SELECT')){
			$this->kill("Not supposed to implement standard query for `sql_select` in <br><br><i style='font-weight:normal;'><pre>".$queryContent."</pre></i><br><br>use prepared statement.");
			$dataArray = false;
			$this->_sql_query($queryContent, $meta);
			$dataArray = $this->_format_select();
		} else {
			$this->kill("Improper function call `sql_select` for <br><br><i style='font-weight:normal;'>".$queryContent."</i><br><br>Please refer to the user manual");
		}
		return $dataArray;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	* Name:				_format_select()
	* Params:			object resource
	* Returns:			result array
	* Description:		Wrapper Class to MySQLManager::sql_query
	* Access:			Private
	*/
	
	function _format_select($offset=-1){	
		$dataArray = false;
		$maxRow = $this->defaultQueryManager->sql_numrows();
		if($maxRow>0){				
			$dataArray = array();
			$cnt = 0;
			foreach($this->defaultQueryManager->sql_fetchrow() as $k => $row)
			{
				$dataArray[$cnt] = array();
				if($offset>=0) $dataArray[$cnt]['__SRL__'] = $offset+$cnt+1;
				foreach($row as $key=>$value){
					$dataArray[$cnt][$key] = stripslashes($value);
				}
				$cnt++;
			}
		}
		return $dataArray;
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				sql_nextid()
	* Params:			void
	* Returns:			result mixed
	* Description:		Wrapper Class to MySQLManager::sql_insert_id
	* Access:			Public
	*/	
	
	function sql_nextid(){
		return $this->defaultQueryManager->sql_insert_id();
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				sql_numrows()
	* Params:			varchar resultset
	* Returns:			int
	* Description:		returns the number of rows in a resultset. 
	* Access:			Public
	*/

	function sql_numrows($result=false){ 
		return $this->defaultQueryManager->sql_numrows();
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:				sql_fetchrow()
	* Params:			null
	* Returns:			array
	* Description:		Returns a row of the resultset.
	*
	*/
	function sql_fetchrow($queryId=""){
		if($queryId!='')
		{
			return $this->defaultQueryManager->sql_fetchrow($queryId);
		}
		else
		{
			$this->kill("No need to use sql_fetchrow() separately for<br><br><i style='font-weight:normal;'>".$this->defaultQueryManager->query_to_execute."</i><br><br>The sql_select() method will give the result in 2D array.");
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		_getAvailableDBTables()
	* Params:		null
	* Returns:		array
	* Description:	gets all the available tables in the Database.
	* Access:		Private
	*/	
	
	function _getAvailableDBTables($reset=false){		
		$sql = "SHOW TABLES";
		$res=$this->_sql_query($sql);
		$ret = array();
		$ret['DB']=$this->currentDB;
		while($rows=$this->defaultQueryManager->sql_fetchrow($res)){
			$ret[]=$rows[0];
		}
		return $ret;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		_getDBTableColumns()
	* Params:		varchar
	* Returns:		null
	* Description:	gets all the available tables in the Database.
	* Access:		Private
	*/	
	
	function _getDBTableColumns($tableName){
		$tableColumns[$tableName] = array();
		$sql = "DESCRIBE ".$tableName;
		$res=$this->_sql_query($sql);
		while($rows=$this->defaultQueryManager->sql_fetchrow($res)){
			$field = strtolower($rows[0]);
			$tableColumns[$tableName][$field]['type']=strtolower($rows[1]);
			$tableColumns[$tableName][$field]['isPRI']=(strtolower($rows[3])=='pri')?true:false;
			if($tableColumns[$tableName][$field]['isPRI'])
				$tableColumns[$tableName]['PK'] =  ($tableColumns[$tableName]['PK']=='')?$field:($tableColumns[$tableName]['PK'].','.$field);
		}
		return $tableColumns[$tableName];
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:    		del_cache()
	* Params:		null
	* Returns:		void
	* Description:	stops file from being cached .
	*/

	function del_cache(){
		// Date in the past
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		// always modified
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		// HTTP/1.0
		header("Pragma: no-cache");
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////	
	/**
	* Name:				_trail()
	* Params:			null
	* Returns:			array 
	* Description:		Returns the last 50+1 pages traversed
	* Access:			Private 
	*/
	
	function _trail($record=false){		
		$pagerec = $this->getSession($this->pageTrail_session);		
		if(sizeof($pagerec)>$this->pageTrail_recCount){
			for($i=(sizeof($pagerec)-$this->pageTrail_recCount); $i < sizeof($pagerec); $i++){
				$secPgRec[] = $pagerec[$i];
			} 
			$pagerec = $secPgRec;
		}
		
		$ind = sizeof($pagerec);
		$pagerec[$ind]["IP"] = $this->ip;
		$pagerec[$ind]["SESSIONID"] = session_id();
		$pagerec[$ind]["REQUEST_METHOD"] = $_SERVER['REQUEST_METHOD'];
		$pagerec[$ind]["REQUEST_TIME"] = $_SERVER['REQUEST_TIME'];
		$pagerec[$ind]["REQUEST_URI"] = $_SERVER['REQUEST_URI'];
		$pagerec[$ind]["QUERY_STRING"] = $_SERVER['QUERY_STRING'];
		$pagerec[$ind]["HTTP_REFERER"] = $_SERVER['HTTP_REFERER'];
		$pagerec[$ind]["HTTP_USER_AGENT"] = $_SERVER['HTTP_USER_AGENT'];
		$pagerec[$ind]["REMOTE_HOST"] = $_SERVER['REMOTE_HOST'];
		$pagerec[$ind]["FILENAME"] = basename($_SERVER['PHP_SELF']);
		
		if(strpos($pagerec[$ind]["FILENAME"],".process.")){
			$pagerec[$ind]["FILETYPE"] = "Process";
		} else {
			$pagerec[$ind]["FILETYPE"] = "Show";
		}
		
		$pagerec[$ind]["REQUESTS"] = $_GET;
		$pagerec[$ind]["POSTS"] = $_POST;
		$pagerec[$ind]["GETS"] = $_GET;
		
		$sessionData = array();
		foreach($_SESSION as $key=>$val){
			if($key!=$this->pageTrail_session)
				$sessionData[$key]=$val;
		}
		
		$pagerec[$ind]["SESSIONS"] = $sessionData;
						
		$this->setSession($this->pageTrail_session,$pagerec);
		
		if($record)
		{
			$sql = "CREATE TABLE IF NOT EXISTS `".$this->page_trail_table."` ( 	`id` bigint(255) NOT NULL AUTO_INCREMENT,																	
																				`ip` varchar(255) NOT NULL,
																				`sessionId` varchar(255) NOT NULL,
																				`servertime` DATETIME  NOT NULL DEFAULT '0000-00-00 00:00:00',
																				`request_method` varchar(50) NOT NULL,
																				`request_time` varchar(255) NOT NULL,
																				`request_uri` text NOT NULL,
																				`query_string` text,
																				`http_referer` varchar(255),
																				`http_user_agent` varchar(255),
																				`remote_host` varchar(255),
																				`fileName` varchar(50) NOT NULL,
																				`fileType` varchar(50) NOT NULL,
																				`requestparams` text,
																				`sessionparams` text,
																				PRIMARY KEY (`id`)
																			 ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";																	  
			$this->_sql_query($sql);
			
			$ip =  $pagerec[$ind]["IP"];
			$sessionId = $pagerec[$ind]["SESSIONID"];
			$servertime = $this->cDate('Y-m-d H:i:s');
			$request_method = $pagerec[$ind]["REQUEST_METHOD"];
			$request_time = $pagerec[$ind]["REQUEST_TIME"];
			$request_uri = $pagerec[$ind]["REQUEST_URI"];
			$query_string = $pagerec[$ind]["QUERY_STRING"];
			$http_referer =  $pagerec[$ind]["HTTP_REFERER"];
			$http_user_agent = $pagerec[$ind]["HTTP_USER_AGENT"];
			$remote_host = $pagerec[$ind]["REMOTE_HOST"];	
			$fileName = $pagerec[$ind]["FILENAME"];	
			$fileType = $pagerec[$ind]["FILETYPE"];
			$requestparams = $this->_getValueOf($pagerec[$ind]["REQUESTS"]);
			$sessionparams = $this->_getValueOf($pagerec[$ind]["SESSIONS"]);		
			
			$sql = "INSERT INTO `".$this->meta_records_table."` SET `ip` = '".$ip."',
																	`sessionId` = '".$sessionId."',
																	`userId` = '".$userId."',
																	`servertime` = '".$servertime."',
																	`request_method` = '".$request_method."',
																	`request_time` = '".$request_time."',
																	`request_uri` = '".$request_uri."',
																	`query_string` = '".addslashes($query_string)."',
																	`http_referer` = '".addslashes($http_referer)."',
																	`http_user_agent`  = '".addslashes($http_user_agent)."',
																	`remote_host` = '".$remote_host."',
																	`fileName` = '".$fileName."',
																	`fileType` = '".$fileType."',
																	`requestparams` = '".addslashes($requestparams)."',
																	`sessionparams` = '".addslashes($sessionparams)."'";	
			$this->defaultQueryManager->sql_query($sql);
		}
			
		return $pagerec;
	}
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:    		_ipCheck()
	* Params:		null
	* Returns:		varchar ip
	* Description:	This function will try to find out if user is coming behind proxy server. 
	*               Why is this important?
	*               If you have high traffic web site, it might happen that you receive lot 
	*               of traffic from the same proxy server (like AOL). In that case, the script 
	*               would count them all as 1 user.
	*               This function tryes to get real IP address.
	*               Note that getenv() function doesn't work when PHP is running as ISAPI module
	* Access:		Private
	*/
	
	function _ipCheck() {
		if (getenv('HTTP_CLIENT_IP')) {
			$ip = getenv('HTTP_CLIENT_IP');
		}
		elseif (getenv('HTTP_X_FORWARDED_FOR')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		}
		elseif (getenv('HTTP_X_FORWARDED')) {
			$ip = getenv('HTTP_X_FORWARDED');
		}
		elseif (getenv('HTTP_FORWARDED_FOR')) {
			$ip = getenv('HTTP_FORWARDED_FOR');
		}
		elseif (getenv('HTTP_FORWARDED')) {
			$ip = getenv('HTTP_FORWARDED');
		}
		else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
	
	
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			getSession()
	* Params:		varchar sessionVariable
	* Returns:		varchar
	* Description:	returns the sesseion variable.
	*
	*/
	
	function getSession($sessionVariable){
		return $_SESSION[$sessionVariable];					
	}	
////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	* Name:			isSession()
	* Params:		varchar sessionVariable
	* Returns:		boolean
	* Description:	returns whether the session is set or not.
	*
	*/
	
	function isSession($sessionVariable){
		return ($_SESSION[$sessionVariable]&&isset($_SESSION[$sessionVariable]));					
	} 	
	
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			setSession()
	* Params:		varchar sessionVariable, varchar value
	* Returns:		void
	* Description:	Sets the sesseion variable.
	*/
	
	function setSession($sessionVariable, $value){
		if($this->isSession($this->sessionMGMT_session)){
			$sessVars = $this->getSession($this->sessionMGMT_session);
		} else {
			$sessVars = array();
		}
		
		$_SESSION[$sessionVariable] = $value;	
		
		if(!in_array($sessionVariable,$sessVars)) 
			$sessVars[] = $sessionVariable;		
		$_SESSION[$this->sessionMGMT_session] = $sessVars;			
	} 	
		
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:			kill()
	* Params:		varchar
	* Returns:		void 
	* Description:	stops execution
	*/
	
	function kill($message){
			$kill ="\n<div align=\"center\"  style = \"BORDER: 1px solid; FONT-WEIGHT: bold; FONT-SIZE: 10px; COLOR: #cc3333; BACKGROUND-COLOR: #ffffff; FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; padding:3px;\">\n";
			$kill.="<B>" . $message . "</B><br/>\n";
			$kill.="</div>\n";	
			die($kill);
	}
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:   		microtime_float()
	* Params:		null
	* Returns:		void
	* Description:	calculate microtime.
	*/

	function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	
////////////////////////////////////////////////////////////////////////////////////////////////////
//  DATE FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:			cDate()
	* Params:		varchar(optional), mixed(optional), varchar(optional)
	* Returns:		formated Date String
	* Description:	Converts date to Date object and returns the formated date string
	*
	*/

	function cDate($format='d-m-Y H:i:s', $date='', $interval=''){
		if(gettype($date)==='object' && get_class($date)==='DateTime'){
			$iDate = $date;
		} else {
			$iDate=date_create($date);
		}	
		
		if(trim($interval)!==''){
			$components = explode(" ",trim($interval));
			$quantity = intval($components[0]);
			$unit = $components[sizeof($components)-1];
			if($quantity<0){
				$interval = ((-1)*$quantity).' '.$unit;
				$iDate=$this->__cDateSub($date, $interval);
			} else if($quantity>0){
				$interval = $quantity.' '.$unit;
				$iDate=$this->__cDateAdd($date, $interval);
			}
		}	
		return date_format($iDate,$format);
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:			__cDateAdd()
	* Params:		mixed,  varchar
	* Returns:		Date Time Object
	* Description:	Adds a interval to a Date
	*
	*/

	function __cDateAdd($date, $interval){
			
		$components = explode(" ",trim($interval));
		$quantity = $components[0];
		$unit = $components[sizeof($components)-1];
		
		$year = $this->cDate('Y',$date);
		$month = $this->cDate('m',$date);
		$day = $this->cDate('d',$date);
		$hour = $this->cDate('H',$date);
		$minute = $this->cDate('i',$date);
		$seconds = $this->cDate('s',$date);
		
		switch($unit){
			case 'second':
			case 'seconds':
				$sec = $seconds;
				$nsec = intval($sec)+intval($quantity);
				if($nsec<10) $nsec = '0'.$nsec;
				if($nsec>59){
					$sec = $nsec%60;
					$amin =  floor($nsec/60);	
					if($sec<10) $sec = '0'.$sec;					
					return $this->__cDateAdd(date_create($year.$month.$day.$hour.$minute.$sec), $amin." minutes");
				} else {
					return date_create($year.$month.$day.$hour.$minute.$nsec);	
				}
				break;
			case 'minute':
			case 'minutes':
				$min = $minute;
				$nmin = intval($min)+intval($quantity);
				if($nmin<10) $nmin = '0'.$nmin;
				if($nmin>59){
					$min = $nmin%60;
					$ahr =  floor($nmin/60);	
					if($min<10) $min = '0'.$min;					
					return $this->__cDateAdd(date_create($year.$month.$day.$hour.$min.$seconds), $ahr." hours");
				} else {
					return date_create($year.$month.$day.$hour.$nmin.$seconds);	
				}
				break;
			case 'hour':
			case 'hours':
				$hr = $hour;
				$nhr = intval($hr)+intval($quantity);
				if($nhr<10) $nhr = '0'.$nhr;
				if($nhr>23){
					$hr = $nhr%24;
					$aday =  floor($nhr/24);	
					if($hr<10) $hr = '0'.$hr;					
					return $this->__cDateAdd(date_create($year.$month.$day.$hr.$minute.$seconds), $aday." days");
				} else {
					return date_create($year.$month.$day.$nhr.$minute.$seconds);	
				}
				break;		
			case 'day':
			case 'days':
				$dy = $day;
				$ndy = intval($dy)+intval($quantity);			
				if($ndy<10) $ndy = '0'.$ndy;
				$daysInMonth = $this->__daysInMonth($date);
				if($ndy>$daysInMonth){
					$extraDays = $ndy-$daysInMonth;				
					$nDate = $this->__cDateAdd(date_create($year.$month.'01'.$hour.$minute.$seconds), "1 month");
					return $this->__cDateAdd($nDate, ($extraDays-1)." days");
				} else {
					return date_create($year.$month.$ndy.$hour.$minute.$seconds);	
				}
				break;
			case 'month':
			case 'months':
				$daysInMonth = $this->__daysInMonth($date);
				$mn = $month;
				$nmn = intval($mn)+intval($quantity);	
				if($nmn<10) $nmn = '0'.$nmn;	
				if($nmn>12){
					$mn = $nmn%12;
					$ayear =  floor($nmn/12);	
					if($mn<10) $mn = '0'.$mn;	
					if($daysInMonth==$day){
						$day = $this->__daysInMonth($year.$mn.'01');
					}
					return $this->__cDateAdd(date_create($year.$mn.$day.$hour.$minute.$seconds), $ayear." years");
				} else {
					if($daysInMonth==$day){
						$day = $this->__daysInMonth($year.$nmn.'01');
					}
					return date_create($year.$nmn.$day.$hour.$minute.$seconds);	
				}	
				break;
			case 'year':
			case 'years':
				$yr = $year;
				$nyr = intval($yr)+intval($quantity);	
				return date_create($nyr.$month.$day.$hour.$minute.$seconds);	
				break;
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:			__cDateSub()
	* Params:		mixed,  varchar
	* Returns:		Date Time Object
	* Description:	Sbstracts a interval from a Date
	*
	*/	

	function __cDateSub($date, $interval){
			
		$components = explode(" ",trim($interval));
		$quantity = $components[0];
		$unit = $components[sizeof($components)-1];
		
		$year = $this->cDate('Y',$date);
		$month = $this->cDate('m',$date);
		$day = $this->cDate('d',$date);
		$hour = $this->cDate('H',$date);
		$minute = $this->cDate('i',$date);
		$seconds = $this->cDate('s',$date);
		
		switch($unit){
			case 'second':
			case 'seconds':
				$sec = $seconds;
				$nsec = intval($sec)-intval($quantity);
				if($nsec<10&&$nsec>0) $nsec = '0'.$nsec;
				if($nsec<0){
					$lessSec = ((-1)*$nsec)-1;
					$nDate = $this->__cDateSub(date_create($year.$month.$day.$hour.$minute.'59'), "1 minute");
					return $this->__cDateSub($nDate, ($lessSec)." seconds");
				} else {
					return date_create($year.$month.$day.$hour.$minute.$nsec);	
				}
				break;
			case 'minute':
			case 'minutes':
				$min = $minute;
				$nmin = intval($min)-intval($quantity);
				if($nmin<10&&$nmin>0) $nmin = '0'.$nmin;
				if($nmin<0){
					$lessMin = ((-1)*$nmin)-1;
					$nDate = $this->__cDateSub(date_create($year.$month.$day.$hour.'59'.$seconds), "1 hour");
					return $this->__cDateSub($nDate, ($lessMin)." minutes");
				} else {
					return date_create($year.$month.$day.$hour.$nmin.$seconds);	
				}
				break;
			case 'hour':
			case 'hours':
				$hr = $hour;
				$nhr = intval($hr)-intval($quantity);
				if($nhr<10&&$nhr>0) $nhr = '0'.$nhr;
				if($nhr<0){
					$lessHr = ((-1)*$nhr)-1;
					$nDate = $this->__cDateSub(date_create($year.$month.$day.'23'.$minute.$seconds), "1 day");
					return $this->__cDateSub($nDate, ($lessHr)." hours");
				} else {
					return date_create($year.$month.$day.$nhr.$minute.$seconds);	
				}
				break;		
			case 'day':
			case 'days':
				$dy = $day;
				$ndy = intval($dy)-intval($quantity);			
				if($ndy<10&&$ndy>0) $ndy = '0'.$ndy;			
				if($ndy<1){
					$lessDay = (-1)*$ndy;
					$nDate = $this->__cDateSub(date_create($year.$month.$this->__daysInMonth($date).$hour.$minute.$seconds), "1 month");
					return $this->__cDateSub($nDate, ($lessDay)." days");	
				} else {
					return date_create($year.$month.$ndy.$hour.$minute.$seconds);	
				}
				break;
			case 'month':
			case 'months':
				$daysInMonth = $this->__daysInMonth($date);
				$mn = $month;
				$nmn = intval($mn)-intval($quantity);	
				if($nmn<10&&$nmn>0) $nmn = '0'.$nmn;	
				if($nmn<1){
					$lessMonth = (-1)*$nmn;				
					if($daysInMonth==$day){
						$day = '31';
					}				
					$nDate = $this->__cDateSub(date_create($year.'12'.$day.$hour.$minute.$seconds), "1 year");	
					return $this->__cDateSub($nDate, ($lessMonth)." months");					
				} else {
					if($daysInMonth==$day){
						$day = $this->__daysInMonth($year.$nmn.'01');
					}
					return date_create($year.$nmn.$day.$hour.$minute.$seconds);	
				}	
				break;
			case 'year':
			case 'years':
				$yr = $year;
				$nyr = intval($yr)-intval($quantity);	
				return date_create($nyr.$month.$day.$hour.$minute.$seconds);	
				break;
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:			__daysInMonth()
	* Params:		mixed
	* Returns:		no of days in the month for the date
	* Description:	returns no of days in the month for the date
	*
	*/	

	function __daysInMonth($date){
		$month =  $this->cDate('m',$date);
		$ret = 0;
		switch($month){
			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
				$ret = 31;
				break;
			case 4:
			case 6:
			case 9:
			case 11:
				$ret = 30;
				break;
			case 2:
				$year = $this->cDate('Y',$date);
				if($year%4==0) $ret = 29;
				else $ret = 28;
				break;
		}
		return $ret;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////////////////////////
//  PRIVATE UTILITY FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////		
	/**
	* Name:				_getValueOf()
	* Params:			varchar
	* Returns:			varchar 
	* Description:		returns a value in string form
	* Access:			Private 
	*/
		
	function _getValueOf($value){
		if(is_array($value)){
			$valueString = '';
			foreach($value as $key => $val){
				$valueString .= (($valueString=='')?'':', ').'['.$key.']='.$this->_getValueOf($val);
			}
			return ' Array('.$valueString.')';
		} else {
			return $value;
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		_startsWith()
	* Params:		varchar,varchar
	* Returns:		boolean
	* Description:	check whether a string starts with a certain char sequence.
	*
	*/
		
	function _startsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}		

////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		_endsWith()
	* Params:		varchar,varchar
	* Returns:		boolean
	* Description:	check whether a string starts with a certain char sequence.
	*
	*/
		
	function _endsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, -$length) === $needle);
	}
} 

// End of class CMS

/***************************************************************************
*                             	class.CommonCMS
*                            ----------------
*   begin                : Saturday, Dec 03, 2006
*   upgrade              : Tuesday, Oct 31, 2017
*   copyright            : Encoders
*
*   $Id: class.common.php,v 2.0 2012/01/10 11:10:01  
*
*	Defines and Wraps the different common system activities. 
*
***************************************************************************/
class CommonCMS { 

	/**
	* Name:				__construct() [class constructor]
	* Params:			null
	* Returns:			null
	* Description:		Create an instance of the class 'CMS' and make a database connection.
	* Access:			System
	*/
	function __construct($runConfigs=false){
		global $cfg;
		
		$this->memusage = memory_get_usage();
						
		$this->sqlIndex = 0;
		$this->sessionMGMT_session = '_'.$cfg['SERVICE_TAG'].'__SESS10N__';
		$this->pageTrail_session = '_'.$cfg['SERVICE_TAG'].'_PGTRAIL_';
		$this->pageTrail_recCount = 2;
		
		$this->meta_records_table = "_meta_records_";
		$this->page_trail_table = "_pagetrail_";
		
		if(trim(session_id())==''){
			session_id($this->getRandom(16,'alphanum'));
		}
		
		$this->currentDB = "__default__";
		$this->queryManager[$this->currentDB] = new MySQLManager(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, false);
		$this->defaultQueryManager = $this->queryManager[$this->currentDB];
		
		$this->dbConnection = $this->defaultQueryManager->sql_connect($runConfigs);
		
		$this->ip = $this->_ipCheck();
		$this->pgTrail = $this->_trail();
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:			__call()
	* Params:		null
	* Returns:		void 
	* Description:	calls the methods of other functions directly through this function
	* Access:		System
	*/	
	
	function __call($method, $args){
		if(method_exists($this, $method)){
			return call_user_func_array(array($this,$method), $args);
		} else if($this->defaultQueryManager != NULL && method_exists($this->defaultQueryManager, $method)){
			return call_user_func_array(array($this->defaultQueryManager,$method), $args);
		} elseif($this->thumbnailManager != NULL && method_exists($this->thumbnailManager, $method)){
			return call_user_func_array(array($this->thumbnailManager,$method), $args);
		} elseif($this->graphManager != NULL && method_exists($this->graphManager, $method)){
			return call_user_func_array(array($this->graphManager,$method), $args);
		} elseif($this->pieManager != NULL && method_exists($this->pieManager, $method)){
			return call_user_func_array(array($this->pieManager,$method), $args);
		} elseif($this->stackChartManager != NULL && method_exists($this->stackChartManager, $method)){
			return call_user_func_array(array($this->stackChartManager,$method), $args);
		} elseif($this->eMailManager != NULL && method_exists($this->eMailManager, $method)){
			return call_user_func_array(array($this->eMailManager,$method), $args);
		} elseif($this->smsManager != NULL && method_exists($this->smsManager, $method)){
			return call_user_func_array(array($this->smsManager,$method), $args);
		} else {
			$this->kill("unknown function ".$method);
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:    		_ipCheck()
	* Params:		null
	* Returns:		varchar ip
	* Description:	This function will try to find out if user is coming behind proxy server. 
	*               Why is this important?
	*               If you have high traffic web site, it might happen that you receive lot 
	*               of traffic from the same proxy server (like AOL). In that case, the script 
	*               would count them all as 1 user.
	*               This function tryes to get real IP address.
	*               Note that getenv() function doesn't work when PHP is running as ISAPI module
	* Access:		Private
	*/
	
	function _ipCheck() {
		if (getenv('HTTP_CLIENT_IP')) {
			$ip = getenv('HTTP_CLIENT_IP');
		}
		elseif (getenv('HTTP_X_FORWARDED_FOR')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		}
		elseif (getenv('HTTP_X_FORWARDED')) {
			$ip = getenv('HTTP_X_FORWARDED');
		}
		elseif (getenv('HTTP_FORWARDED_FOR')) {
			$ip = getenv('HTTP_FORWARDED_FOR');
		}
		elseif (getenv('HTTP_FORWARDED')) {
			$ip = getenv('HTTP_FORWARDED');
		}
		else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
////////////////////////////////////////////////////////////////////////////////////////////////////	
	/**
	* Name:				_trail()
	* Params:			null
	* Returns:			array 
	* Description:		Returns the last 50+1 pages traversed
	* Access:			Private 
	*/
	
	function _trail($record=false){		
		$pagerec = $this->getSession($this->pageTrail_session);		
		if(sizeof($pagerec)>$this->pageTrail_recCount){
			for($i=(sizeof($pagerec)-$this->pageTrail_recCount); $i < sizeof($pagerec); $i++){
				$secPgRec[] = $pagerec[$i];
			} 
			$pagerec = $secPgRec;
		}
		
		$ind = sizeof($pagerec);
		$pagerec[$ind]["IP"] = $this->ip;
		$pagerec[$ind]["SESSIONID"] = session_id();
		$pagerec[$ind]["REQUEST_METHOD"] = $_SERVER['REQUEST_METHOD'];
		$pagerec[$ind]["REQUEST_TIME"] = $_SERVER['REQUEST_TIME'];
		$pagerec[$ind]["REQUEST_URI"] = $_SERVER['REQUEST_URI'];
		$pagerec[$ind]["QUERY_STRING"] = $_SERVER['QUERY_STRING'];
		$pagerec[$ind]["HTTP_REFERER"] = $_SERVER['HTTP_REFERER'];
		$pagerec[$ind]["HTTP_USER_AGENT"] = $_SERVER['HTTP_USER_AGENT'];
		$pagerec[$ind]["REMOTE_HOST"] = $_SERVER['REMOTE_HOST'];
		$pagerec[$ind]["FILENAME"] = basename($_SERVER['PHP_SELF']);
		
		if(strpos($pagerec[$ind]["FILENAME"],".process.")){
			$pagerec[$ind]["FILETYPE"] = "Process";
		} else {
			$pagerec[$ind]["FILETYPE"] = "Show";
		}
		
		$pagerec[$ind]["REQUESTS"] = $_GET;
		$pagerec[$ind]["POSTS"] = $_POST;
		$pagerec[$ind]["GETS"] = $_GET;
		
		$sessionData = array();
		foreach($_SESSION as $key=>$val){
			if($key!=$this->pageTrail_session)
				$sessionData[$key]=$val;
		}
		
		$pagerec[$ind]["SESSIONS"] = $sessionData;
						
		$this->setSession($this->pageTrail_session,$pagerec);
		
		if($record)
		{
			$sql = "CREATE TABLE IF NOT EXISTS `".$this->page_trail_table."` ( 	`id` bigint(255) NOT NULL AUTO_INCREMENT,																	
																				`ip` varchar(255) NOT NULL,
																				`sessionId` varchar(255) NOT NULL,
																				`servertime` DATETIME  NOT NULL DEFAULT '0000-00-00 00:00:00',
																				`request_method` varchar(50) NOT NULL,
																				`request_time` varchar(255) NOT NULL,
																				`request_uri` text NOT NULL,
																				`query_string` text,
																				`http_referer` varchar(255),
																				`http_user_agent` varchar(255),
																				`remote_host` varchar(255),
																				`fileName` varchar(50) NOT NULL,
																				`fileType` varchar(50) NOT NULL,
																				`requestparams` text,
																				`sessionparams` text,
																				PRIMARY KEY (`id`)
																			 ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";																	  
			$this->_sql_query($sql);
			
			$ip =  $pagerec[$ind]["IP"];
			$sessionId = $pagerec[$ind]["SESSIONID"];
			$servertime = $this->cDate('Y-m-d H:i:s');
			$request_method = $pagerec[$ind]["REQUEST_METHOD"];
			$request_time = $pagerec[$ind]["REQUEST_TIME"];
			$request_uri = $pagerec[$ind]["REQUEST_URI"];
			$query_string = $pagerec[$ind]["QUERY_STRING"];
			$http_referer =  $pagerec[$ind]["HTTP_REFERER"];
			$http_user_agent = $pagerec[$ind]["HTTP_USER_AGENT"];
			$remote_host = $pagerec[$ind]["REMOTE_HOST"];	
			$fileName = $pagerec[$ind]["FILENAME"];	
			$fileType = $pagerec[$ind]["FILETYPE"];
			$requestparams = $this->_getValueOf($pagerec[$ind]["REQUESTS"]);
			$sessionparams = $this->_getValueOf($pagerec[$ind]["SESSIONS"]);		
			
			$sql = "INSERT INTO `".$this->meta_records_table."` SET `ip` = '".$ip."',
																	`sessionId` = '".$sessionId."',
																	`userId` = '".$userId."',
																	`servertime` = '".$servertime."',
																	`request_method` = '".$request_method."',
																	`request_time` = '".$request_time."',
																	`request_uri` = '".$request_uri."',
																	`query_string` = '".addslashes($query_string)."',
																	`http_referer` = '".addslashes($http_referer)."',
																	`http_user_agent`  = '".addslashes($http_user_agent)."',
																	`remote_host` = '".$remote_host."',
																	`fileName` = '".$fileName."',
																	`fileType` = '".$fileType."',
																	`requestparams` = '".addslashes($requestparams)."',
																	`sessionparams` = '".addslashes($sessionparams)."'";	
			$this->defaultQueryManager->sql_query($sql);
		}
			
		return $pagerec;
	}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:				_meta_entry()
	* Params:			varchar query
	* Returns:			result set
	* Description:		Makes an entry a meta table
	* Access:			Private
	*/

	function _meta_entry($action, $query){
		$sql = "CREATE TABLE IF NOT EXISTS `".$this->meta_records_table."` ( 	`id` bigint(255) NOT NULL AUTO_INCREMENT,
																				`dbservertime` DATETIME  NOT NULL DEFAULT '1970-01-01 00:00:00',
																				`appservertime` DATETIME  NOT NULL DEFAULT '1970-01-01 00:00:00',
																				`userId` varchar(255) DEFAULT NULL,
																				`userType` varchar(255) DEFAULT NULL,
																				`userName` varchar(255) DEFAULT NULL,
																				`sessionId` varchar(255) NOT NULL,
																				`ip` varchar(255) NOT NULL,
																				`action` varchar(20) NOT NULL,
																				`query` text NOT NULL,
																				`page` text NOT NULL,
																				`requestparams` text DEFAULT NULL,
																				`sessionparams` text DEFAULT NULL,
																				PRIMARY KEY (`id`)
																			 ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";																	  
		$this->_sql_query($sql);
		
		$appservertime =  date('Y-m-d H:i:s');
		$userId = addslashes($this->getLoggedUserId());
		$userType = addslashes($this->getLoggedUserType());
		$userName = addslashes($this->getLoggedUserId());
		$ip = addslashes($this->ip);
		$page = addslashes($this->getPageName());
		$requestparams = addslashes($this->getRequestParams());	
		$sessionparams = addslashes($this->getSessionParams());	
		
		$sql = "INSERT INTO `".$this->meta_records_table."` SET `dbservertime` = NOW(),
																`appservertime` = '".$appservertime."',
																`userId` = '".$userId."',
																`userType` = '".$userType."',
																`userName` = '".$userName."',
																`sessionId` = '".session_id()."',
																`ip` = '".$ip."',
																`action` = '".addslashes($action)."',
																`query` = '".addslashes($query)."',
																`page`  = '".$page."',
																`requestparams` = '".$requestparams."',
																`sessionparams` = '".$sessionparams."'";	
		$this->defaultQueryManager->sql_query($sql);
	}

////////////////////////////////////////////////////////////////////////////////////////////////////	
//  	PAGE TRAIL RELATED FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////	
	

////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				getTrail()
	* Params:			int the nth page before this page
	* Returns:			array 
	* Description:		returns the details of the nth page before this page 
	* Access:			Public 
	*/
	
	function getTrail($prev=0){
		if($prev<=sizeof($this->pgTrail)){
			$lstInd = sizeof($this->pgTrail)-1;
			return $this->pgTrail[$lstInd-$prev];
		} else {
			return false;
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				getPageName()
	* Params:			void
	* Returns:			varchar
	* Description:		returns the present Page Name.
	* Access:			Public 
	*/
	
	function getPageName(){
		$thispage=$this->getTrail();
		return $thispage["FILENAME"];	
	}	

////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				getProcessPageName()
	* Params:			void
	* Returns:			varchar
	* Description:		returns the probable process page for present Page Name.
	* Access:			Public 
	*/
	
	function getProcessPageName(){
		$pageName = $this->getPageName();
		$components = explode('.',$pageName);
		$ext = $components[sizeof($components)-1];
		array_pop($components);
		$processPage = implode('.',$components).'.process.'.$ext;
		return $processPage;	
	}	


////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				getRequestUri()
	* Params:			void
	* Returns:			varchar
	* Description:		returns the REQUEST_URI.
	* Access:			Public 
	*/
	
	function getRequestUri(){
		$thispage=$this->getTrail();
		return $thispage["REQUEST_URI"];	
	}

////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				getHttpReferer()
	* Params:			void
	* Returns:			varchar
	* Description:		returns the HTTP_REFERER.
	* Access:			Public 
	*/
	
	function getHttpReferer(){
		$thispage=$this->getTrail();
		return $thispage["HTTP_REFERER"];	
	}

////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				getRequestParams()
	* Params:			void
	* Returns:			varchar
	* Description:		returns the REQUESTS.
	* Access:			Public 
	*/
	
	function getRequestParams($format=true){
		$thispage=$this->getTrail();
		if(!$format){
			return $thispage["REQUESTS"];	
		} else {
			return $this->_getValueOf($thispage["REQUESTS"]);
		}
	}	

////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:				getSessionParams()
	* Params:			void
	* Returns:			varchar
	* Description:		returns the SESSIONS.
	* Access:			Public 
	*/
	
	function getSessionParams($format=true){
		$thispage=$this->getTrail();
		if(!$format){
			return $thispage["SESSIONS"];	
		} else {
			return $this->_getValueOf($thispage["SESSIONS"]);
		}
	}	


////////////////////////////////////////////////////////////////////////////////////////////////////	
		
	/**
	* Name:				isProcessPageOf()
	* Params:			varchar processPage, varchar showPage
	* Returns:			boolean 
	* Description:		checks whether the page is a processpage
	* Access:			Public 
	*/
	
	function isProcessPageOf($processPage, $showPage){
		if(strpos($processPage,".process.")){
			$pr = explode(".",$processPage);
			$sh = explode(".",$showPage);			
			if($pr[sizeof($pr)-1]!="php") return false;
			if($sh[sizeof($sh)-1]!="php") return false;
			$prs = implode(".",array_slice($pr,0,(sizeof($pr)-2)));
			$shs = implode(".",array_slice($sh,0,(sizeof($sh)-1)));
			if($prs==$shs){
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}


////////////////////////////////////////////////////////////////////////////////////////////////////	
//  	PAGINATION RELATED FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:   		pagination()
	* Params:		varchar index, varchar sql, int row limit
	* Returns:		void
	* Description:	returns a paginated result set
	*/

	function pagination($ind, $sql, $limit=50, $reset=false){
		if(trim($ind)=='') {
			$this->kill("Pagination Index not set for <br> ".nl2br($sql));
		}
		$this->isPaginate[$ind] = true;
		$this->paginateSQL[$ind] = $sql;
		$this->pgLimit[$ind] = ($limit)?$limit:10;
							
		$dispPgNos = $this->getSession("_PGNO_");	
		$setPGno = $dispPgNos[$thisPgNam][$ind];
		
		$thisPgNam = $this->getPageName();
		$prevPgDet = $this->getTrail(1);
		$prevPgNam = $prevPgDet['FILENAME'];
		$prevPgReq = $prevPgDet['REQUESTS'];
		
		if($this->isProcessPageOf($prevPgNam,$thisPgNam)||$prevPgReq['show']=='add'||$prevPgReq['show']=='edit'||$prevPgReq['show']=='view'){
			$this->pageno[$ind] = ($setPGno)?($setPGno):0; 
		} else if($pageName!=$previousPage["FILENAME"]){
			$this->pageno[$ind] = 0;			
		} else {
			$this->pageno[$ind] = (isset($_GET['_pgn'.$ind.'_']) && $_GET['_pgn'.$ind.'_']!=0)?$_GET['_pgn'.$ind.'_']:"0";
		}		
		$dispPgNos[$pageName][$ind] = $this->pageno[$ind];		
		$this->setSession("_PGNO_",$dispPgNos);
		
		$offset = $this->pageno[$ind]*$this->pgLimit[$ind];
		if($reset) {
			$offset = 0;
		}
		
		//full query
		$res=$this->sql_select($sql, false, false);				
		$this->pgRecCnt[$ind] = $this->sql_numrows($res);
		
		if($this->pgRecCnt[$ind] > $this->pgLimit[$ind]){
			//query with LIMIT tag
			$res=NULL;
			$limitTxt = " LIMIT ".$offset.", ".$this->pgLimit[$ind];
			if(is_array($sql)) {
				$sql['QUERY'] = $sql['QUERY'] . $limitTxt ;
			} else {
				$sql = $sql . $limitTxt ;
			}
			$res=$this->sql_select($sql);
		}		
		return $res;
	}
		
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:   		paginate()
	* Params:		varchar index, varchar style class name
	* Returns:		void
	* Description:	returns a paginate sequence
	*/
	
	function paginate($ind, $class='', $ext1=0, $ext2="pageno", $ext3="topTags"){
		if(is_numeric($ind) && is_numeric($class))
		{
			return $this->paginate_old($ind, $class, $ext1, $ext2, $ext3);
		}
		else
		{
			if(trim($ind)=='') {
				$this->kill("Paginate Index not set");
			}
			if($this->isPaginate[$ind]){
				$currentPage = $this->getPageName();
				// develop query string minus page vars			
				if (!empty($_GET)){
					$params = $_GET;
					foreach ($params as $key => $value){
						if(trim($key) != "_pgn".$ind."_" && trim($key)!=$this->request_param){
							$queryString .= "&" . htmlentities($key."=".$value);
						}
					}
				}
							
				$totalPages = ceil($this->pgRecCnt[$ind]/$this->pgLimit[$ind]);	
				$pageNum = $this->pageno[$ind];
				
				// build page navigation
				if($totalPages> 1){
					$navigation  = "<div class='".$class."'>";
					$navigation .= "<a>".($pageNum+1)." of ".$totalPages." Pages</a>"; 					
					
					$upper_limit = $pageNum + 3;
					$lower_limit = $pageNum - 3;
		
					if ($pageNum > 0){ 
						// Show if not first page			
						if(($pageNum - 2)>0){	
							$first       = $this->getComposedURL($currentPage,"_pgn".$ind."_=0".$queryString,true,false);
							$navigation .= "<a href='".$first."'>[First]</a> ";
						}						
						$prev            = $this->getComposedURL($currentPage,"_pgn".$ind."_=".max(0, $pageNum-1).$queryString,true,false);
						$navigation     .= "<a href='".$prev."'>[<<]</a> ";
					} // Show if not first page
				
					// get in between pages
					for($i = 0; $i < $totalPages; $i++){				
						$pageNo = $i+1;					
						if($i==$pageNum){
							$navigation .= "<a class='selected'>".$pageNo."</a>";
						}elseif($i!==$pageNum && $i<$upper_limit && $i>$lower_limit){
							$noLink      = $this->getComposedURL($currentPage,"_pgn".$ind."_=".$i.$queryString,true,false);
							$navigation .= "<a href='".$noLink."'>".$pageNo."</a>";
						}elseif(($i - $lower_limit)==0){
							$navigation .=  "";
						} 
					}
				  
					if (($pageNum+1) < $totalPages){ 
						// Show if not last page
						$next = $this->getComposedURL($currentPage,"_pgn".$ind."_=".min($totalPages, $pageNum + 1).$queryString,true,false);
						$navigation .= "<a href='".$next."'>[>>]</a> ";
						if(($pageNum + 3)<$totalPages){
							$last = $this->getComposedURL($currentPage,"_pgn".$ind."_=".($totalPages-1).$queryString,true,false);
							$navigation .= "<a href='".$last."'>[Last]</a>";
						}
					} // Show if not last page 
					$navigation  .= "</div>";
			
				} // end if total pages is greater than one
				
				return $navigation;
			}
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:   		paginate_old()
	* Params:		null
	* Returns:		void
	* Description:	Pagination routine, generates, page number sequence.
	* $pagina = paginate_old($numRows, $maxRows, $pageNum=0, $pageVar="pageno", $class="txtLink");
	* print $pagina;
	*/
	function paginate_old($numRows, $maxRows, $pageNum=0, $pageVar="pageno", $class="topTags"){
	$navigation = "";

	// get total pages
	$totalPages = ceil($numRows/$maxRows);

	// develop query string minus page vars
	$queryString = "";
		if (!empty($_SERVER['QUERY_STRING'])) {
			$params = explode("&", $_SERVER['QUERY_STRING']);
			$newParams = array();
				foreach ($params as $param) {
					if (stristr($param, $pageVar) == false) {
						array_push($newParams, $param);
					}
				}
			if (count($newParams) != 0) {
				$queryString = "&" . htmlentities(implode("&", $newParams));
			}
		}

	// get current page
	$currentPage = $_SERVER['PHP_SELF'];

	// build page navigation
	if($totalPages> 1){
	$navigation = "<SPAN class='total_page'>".$totalPages." Pages&nbsp;&nbsp;</span>";

	$upper_limit = $pageNum + 3;
	$lower_limit = $pageNum - 3;

		if ($pageNum > 0) { // Show if not first page

			if(($pageNum - 2)>0){
			$first = sprintf("%s?".$pageVar."=%d%s", $currentPage, 0, $queryString);
			$navigation .= "<SPAN class='".$class."'><a href='".$first."'>First</a></span>";}

			$prev = sprintf("%s?".$pageVar."=%d%s", $currentPage, max(0, $pageNum - 1), $queryString);
			$navigation .= "<SPAN class='".$class."'><a href='".$prev."'>Previous</a></span>";
		} // Show if not first page

		// get in between pages
		for($i = 0; $i < $totalPages; $i++){

			$pageNo = $i+1;

			if($i==$pageNum){
				$navigation .= "<span class='select'>".$pageNo."</span>";
			} elseif($i!==$pageNum && $i<$upper_limit && $i>$lower_limit){
				$noLink = sprintf("%s?".$pageVar."=%d%s", $currentPage, $i, $queryString);
				$navigation .= "<a href='".$noLink."'><span class='".$class."'>".$pageNo."</span></a>";
			} elseif(($i - $lower_limit)==0){
				//$navigation .=  "&hellip;";
				$navigation .=  "";
			}
		}

		if (($pageNum+1) < $totalPages) { // Show if not last page
			$next = sprintf("%s?".$pageVar."=%d%s", $currentPage, min($totalPages, $pageNum + 1), $queryString);
			$navigation .= "<a href='".$next."'><SPAN class='".$class."'>Next</span></a>";
			if(($pageNum + 3)<$totalPages){
			$last = sprintf("%s?".$pageVar."=%d%s", $currentPage, $totalPages-1, $queryString);
			$navigation .= "<a href='".$last."'><SPAN class='".$class."'>Last</span></a>";}
		} // Show if not last page

		} // end if total pages is greater than one

		return $navigation;

	}

////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:   		paginateRecInfo()
	* Params:		varchar index, varchar style class name
	* Returns:		void
	* Description:	returns a paginate message
	*/
	function paginateRecInfo($ind, $msg = 'Showing {lowerLimit} to {upperLimit} of {totalCount} entries'){
		$totalRecordCounts = $this->pgRecCnt[$ind];
		
		if($totalRecordCounts > 0){
		
			$limits = $this->pgLimit[$ind];
			$pageNum = $this->pageno[$ind]+1;
			
			
			$lowerLimit = (($pageNum-1)*$limits)+1;
			$upperLimit = $pageNum*$limits;
			
			if($upperLimit > $totalRecordCounts){
				$upperLimit = $totalRecordCounts;
			}
		
			return str_replace ( '{lowerLimit}', $lowerLimit , str_replace ( '{upperLimit}', $upperLimit , str_replace ( '{totalCount}', $totalRecordCounts , $msg ) ) );
		}
		
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////
// 		SESSION RELATED FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:			getSession()
	* Params:		varchar sessionVariable
	* Returns:		varchar
	* Description:	returns the sesseion variable.
	*
	*/
	
	function getSession($sessionVariable){
		return $_SESSION[$sessionVariable];					
	}	
////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	* Name:			isSession()
	* Params:		varchar sessionVariable
	* Returns:		boolean
	* Description:	returns whether the session is set or not.
	*
	*/
	
	function isSession($sessionVariable){
		return ($_SESSION[$sessionVariable]&&isset($_SESSION[$sessionVariable]));					
	} 	
	
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			setSession()
	* Params:		varchar sessionVariable, varchar value
	* Returns:		void
	* Description:	Sets the sesseion variable.
	*/
	
	function setSession($sessionVariable, $value){
		if($this->isSession($this->sessionMGMT_session)){
			$sessVars = $this->getSession($this->sessionMGMT_session);
		} else {
			$sessVars = array();
		}
		
		$_SESSION[$sessionVariable] = $value;	
		
		if(!in_array($sessionVariable,$sessVars)) 
			$sessVars[] = $sessionVariable;		
		$_SESSION[$this->sessionMGMT_session] = $sessVars;			
	} 


////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			removeSession()
	* Params:		varchar sessionVariable
	* Returns:		void
	* Description:	Removes the sesseion variable.
	*
	*/
	
	function removeSession($sessionVariable){
		unset($_SESSION[$sessionVariable]);					
	} 	

////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			removeAllSession()
	* Params:		null
	* Returns:		null
	* Description:	Removes all the sesseion variable.
	*
	*/
	
	function removeAllSession(){
		if($this->isSession($this->sessionMGMT_session)){
			$sessVars = $this->getSession($this->sessionMGMT_session);
			foreach($sessVars as $sessName => $value){
				$this->removeSession($sessName);
			}
			$this->removeSession($this->sessionMGMT_session);
		}
	}
	

////////////////////////////////////////////////////////////////////////////////////////////////////
//		DOCUMENT HEADER GENERATOR AND RELATED FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:    		generateXMLHeader()
	* Params:		void
	* Returns:		null
	* Description:	generates the XML header.
	*
	*/
	
	function generateXMLHeader(){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		header("Content-type: text/xml");
	}		

////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:    		generateHTMLHeader()
	* Params:		void
	* Returns:		null
	* Description:	generates the HTML header.
	*
	*/
	
	function generateHTMLHeader(){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		header("Content-type: text/html");
	}

///////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:    		generateJSONHeader()
	* Params:		void
	* Returns:		null
	* Description:	generates the JSON header.
	*
	*/
	
	function generateJSONHeader(){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		header("Content-type: application/json");
	}
	

////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:			Redirect()
	* Params:		varchar request url , optional (varchar message , int time)
	* Returns:		void
	* Description:	Work's just like PHP header function. But difference is that it's print an
	*				table with meta tag due to an error occure redirect url.
	*/

	function redirect($url,$message='',$time='1'){
		$new_dir = @(@dirname(@$_SERVER['PHP_SELF'])=="/")?"":@dirname(@$_SERVER['PHP_SELF']);
		
		$http = "http";
		if (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") $http = "https";
		
		if($this->_startsWith(strtolower(trim($url)), 'http://') || $this->_startsWith(strtolower(trim($url)), 'https://')){
			$newURl = $url;  
		} else {
		 	$newURl = @trim(@str_replace("\/","/", $http."://" . @$_SERVER['HTTP_HOST'] .@$new_dir . "/" . @$url));
		}
		
		if (!headers_sent()) {
			header('Location:'.$newURl);
		} else {			
			 $message = ($message == '') ? 'Redirecting....' : $message;
             $re_err = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
            $re_err.="<html>\n";
            $re_err.="<head>\n";
            $re_err.="<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">\n";
            $re_err.="<meta http-equiv='Refresh' content='1;url=" . $newURl . "'>\n";
            $re_err.="<title>Redirect</title>\n";
			$re_err.="</head>\n<body>\n"; 			          
            $re_err.="<br/>\n<div align=\"center\">\n";
            $re_err.="<font face=\"Verdana\" size=\"4\" style=\"color: #000000;\"><B>" . $message . "</B></font>\n<br/>\n";
            $re_err.="<font face=\"Verdana\" size=\"2\" style=\"color: #000000;\">If page does not refresh automatically in 5 seconds please click on this link </font>&nbsp;";
            $re_err.="<a href='" . $newURl . "' style=\"color: #174A81;\"><font face=\"Verdana\" size=\"2\">" . $newURl . "</font></a>\n";
            $re_err.="</div>\n";
            $re_err.="</body>";
            $re_err.="</html>";		
			die($re_err);
		}
	}	

////////////////////////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////////////////////////
//  FILE FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////	

	/**
	* Name:    		uploadFile()
	* Params:		fileobject, varchar, varchar, boolean(optional)
	* Returns:		null
	* Description:	uploads a file.
	*
	*/
	
	function uploadFile($tempFile, $uploadFile, $path, $name, $removeExisting=false){
		if($tempFile!=''){			
			 $components = explode(".",$uploadFile);
		     $fileExt 	= strtolower($file_ext[count($file_ext)-1]);
					
			$filename = $name.".".$fileExt;
			
			if(!$this->_endsWith($path, '/')) $path .= '/';
			
			$file = $path.$filename;
			
			chmod($file,0777);
			if(removeExisting){
				@unlink($file);
			}
			if(move_uploaded_file($fileobject['tmp_name'], $file)){
				copy($file,0777);
				return $filename;
			} else {
				return false;
			}
		}
	}		

////////////////////////////////////////////////////////////////////////////////////////////////////
//  MISC UTILITY FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:    		getRandom()
	* Params:		int(optional), varchar(optional)
	* Returns:		null
	* Description:	generates a random no. or string based on choice of characters.
	*				choices are : alpha - only small case alphabets
	*							: alphacaps - only capital case alphabets
	*							: num - only numeric
	*							: alphanum - numeric and  small case alphabets
	*							: alphanumcaps - numeric, small case alphabets and capital case alphabets
	*							: all - all available characters
	*
	*/
	
	function getRandom($length = 6, $seeds = 'alphanum'){
	  
	   // Possible seeds
	   $seedings['alpha'] = 'abcdefghijklmnopqrstuvwxyz';
	   $seedings['alphacaps'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	   $seedings['num'] = '0123456789';
	   $seedings['alphanum'] = 'abcdefghijklmnopqrstuvwxyz0123456789';
	   $seedings['alphanumcaps'] = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	   $seedings['all'] = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%&*(){}[]|:<>?';
	   
	   // Choose seed
	   if (isset($seedings[$seeds])){
		   $seeds = $seedings[$seeds];
	   }
	   
	   // Seed generator
	   list($usec, $sec) = explode(' ', microtime());
	   $seed = (float) $sec + ((float) $usec * 100000);
	   mt_srand($seed);
	   
	   // Generate
	   $str = '';
	   $seeds_count = strlen($seeds);
	   
	   for ($i = 0;  $i < $length; $i++){
		   $str .= $seeds{mt_rand(0, $seeds_count - 1)};
	   }
	   return $str;
	}
			
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:			flush()
	* Params:		null
	* Returns:		void 
	* Description:	flushes the buffer and stops execution
	*/
	
	function flushAll(){
		global $obst;
		if($obst){
			@ob_flush();
			@flush();
			@ob_end_flush();
			exit();
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	/**
	* Name:			kill()
	* Params:		varchar
	* Returns:		void 
	* Description:	stops execution
	*/
	
	function kill($message){
			$kill ="\n<div align=\"center\"  style = \"BORDER: 1px solid; FONT-WEIGHT: bold; FONT-SIZE: 10px; COLOR: #cc3333; BACKGROUND-COLOR: #ffffff; FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; padding:3px;\">\n";
			$kill.="<B>" . $message . "</B><br/>\n";
			$kill.="</div>\n";	
			die($kill);
	}

////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		echos()
	* Params:		varchar, varchar, varchar
	* Returns:		null
	* Description:	echos test messages.
	* Access:		public	
	*/
	
	function echos($message){
		$echos ="\n<div align=\"left\"  style = \"BORDER: 1px solid; FONT-WEIGHT: normal; FONT-SIZE: 10px; COLOR: #cc3333; BACKGROUND-COLOR: #ffffff; FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; padding:3px;\">\n";
		$echos.="<B>" . $message . "</B><br/>\n";
		$echos.="</div>\n";	
		echo $echos;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:   		microtime_float()
	* Params:		null
	* Returns:		void
	* Description:	calculate microtime.
	*/

	function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}


////////////////////////////////////////////////////////////////////////////////////////////////////
		
	/**
	* Name:			encoded()
	* Params:		varchar str
	* Returns:		void
	* Description:	It's return an encode form of a statement
	*
	*/

	function encoded($str){
		return str_replace(array('=','+','/'),'',base64_encode(base64_encode($str)));
	}

////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:			decoded()
	* Params:		varchar str
	* Returns:		void
	* Description:	Returns decode form of an encoded statement.
	*
	*/

	function decoded($str){
		return base64_decode(base64_decode($str));
	}	
	
////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Name:			getMemorySize()
	* Params:		void
	* Returns:		void
	* Description:	Returns the memory usage.
	*
	*/
	
	function getMemoryUsage(){
		global $cfg;
		if(!isset($_MEM_SIZE_AT_BGN_)) $_MEM_SIZE_AT_BGN_ = 0;
		$mem_usage =  memory_get_usage()-$_MEM_SIZE_AT_BGN_;
		
		$mem = "\n<div align=\"left\"  style = \"BORDER: 1px solid; FONT-WEIGHT: normal; FONT-SIZE: 10px; COLOR: #cc3333; BACKGROUND-COLOR: #ffffff; FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; padding:3px;\">\n";
		$mem .= "<b>Memory Used =></b>";
		if ($mem_usage < 1024) 
            $mem .= $mem_usage." bytes"; 
        elseif ($mem_usage < 1048576) 
            $mem .= round($mem_usage/1024,2)." kilobytes"; 
        else 
            $mem .= round($mem_usage/1048576,2)." megabytes"; 		
		$mem .= "</div>\n";
		
		return $mem;
	}
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			getContentValue()
	* Params:		varchar value
	* Returns:		varchar value
	* Description:	It's return the formated values.
	*
	*/
	function getContentValue($value)
	{
		$output    	=   trim($value);
		$output    	=   addslashes($output);
		return $output;
	}
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			showStripslashesValue()
	* Params:		varchar value
	* Returns:		varchar value
	* Description:	It's return the formated values.
	*
	*/
	function showStripslashesValue($value)
	{
		$output    	=   trim($value);
		$output    	=   stripslashes($output);
		return $output;
	}

////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Name:			getFieldValue()
	* Params:		varchar value
	* Returns:		varchar value
	* Description:	It's return the formated values.
	*
	*/
	function getFieldValue($value)
	{
		/*$value1	=	trim($value);
		$value2	=	addslashes($value1);
		$value3	=	htmlspecialchars($value2);
		$value4	=	strip_tags($value2);
		$value5	=	mysqli_real_escape_string($this->dbConnection,$value4);
		return $value4;*/
		
		$output    	=   trim($value);
		$output    	=   addslashes($output);
		$output 	=   $this->stripTags($output);
		$output 	=   $this->strip_encoded_entities( $output );
				
		return $output;
		
	}
	
	
	function strip_encoded_entities( $input ) {
		// Fix &entity\n;
		$input = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $input);
		$input = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $input);
		$input = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $input);
		$input = html_entity_decode($input, ENT_COMPAT, 'UTF-8');
		// Remove any attribute starting with "on" or xmlns
		$input = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+[>\b]?#iu', '$1>', $input);
		// Remove javascript: and vbscript: protocols
		$input = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $input);
		$input = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $input);
		$input = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $input);
		// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
		$input = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $input);
		$input = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $input);
		$input = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $input);
		return $input;
	}
	/*
	 * Focuses on stripping unencoded HTML tags & namespaces
	 *
	 * @param   string  $input  Content to be cleaned. It MAY be modified in output
	 * @return  string  $input  Modified $input string
	 */
	 function stripTags( $input ) {
		// Remove tags
		$input = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $input);
		// Remove namespaced elements
		$input = preg_replace('#</*\w+:\w[^>]*+>#i', '', $input);
		return $input;
	}
	/*
	 * Focuses on stripping entities from Base64 encoded strings
	 *
	 * NOT ENABLED by default!
	 * To enable 2nd param of clean_input() can be set to anything other than 0 or '0':
	 * ie: xssClean->clean_input( $input_string, 1 )
	 *
	 * @param   string  $input      Maybe Base64 encoded string
	 * @return  string  $output     Modified & re-encoded $input string
	 */
	 function strip_base64( $input ) {
		$decoded = base64_decode( $input );
		$decoded = $this->strip_tags( $decoded );
		$decoded = $this->strip_encoded_entities( $decoded );
		$output = base64_encode( $decoded );
		return $output;
	}
////////////////////////////////////////////////////////////////////////////////////////////////////	

	

////////////////////////////////////////////////////////////////////////////////////////////////////
//  PRIVATE UTILITY FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////////////		
	/**
	* Name:				_getValueOf()
	* Params:			varchar
	* Returns:			varchar 
	* Description:		returns a value in string form
	* Access:			Private 
	*/
		
	function _getValueOf($value){
		if(is_array($value)){
			$valueString = '';
			foreach($value as $key => $val){
				$valueString .= (($valueString=='')?'':', ').'['.$key.']='.$this->_getValueOf($val);
			}
			return ' Array('.$valueString.')';
		} else {
			return $value;
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		_startsWith()
	* Params:		varchar,varchar
	* Returns:		boolean
	* Description:	check whether a string starts with a certain char sequence.
	*
	*/
		
	function _startsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}		

////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		_endsWith()
	* Params:		varchar,varchar
	* Returns:		boolean
	* Description:	check whether a string starts with a certain char sequence.
	*
	*/
		
	function _endsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, -$length) === $needle);
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
	* Name:    		logout()
	* Params:		varchar,varchar
	* Returns:		redirect to home page
	* Description:	logout from admin.
	*
	*/		
	function logout($gopage){
		// Log out status ...
		session_unset("admin_log_in");			// Boolen value true or false
		session_unset("admin_login_uid");		// Login member id
		session_unset("admin_typeId");		// Login member type id
		session_unset("admin_user_name");		// Login member id
		session_unset("LT");		// Login member id
		session_unset("lastActivityTime");
		if(session_destroy())
			return 	$this->redirect($gopage);
		else{
			$_SESSION['log_in']=false;
			return 	$this->redirect($gopage);
		}
	}
}
// End of class CommonCMS
?>