<?php
	include 'all_common_functions.php';

	$method = $_SERVER['REQUEST_METHOD'];
	if ('GET' === $method) {
		$name_param = isset($_GET['name']) ? $_GET['name'] : '';
		if(!$name_param == ''){
			if (!$db_link = sql_connect()) {
				$response = array('result' => 'failed', 'error' => 'MySql Connection failed: ' . mysqli_connect_error());
			}else{
				/* grab the posts from the db */
				$query = "SELECT * FROM `test` WHERE `name` = '".$name_param."'";
				$result = mysqli_query($db_link, $query);
				if(!$result){
					$response = array('result' => 'failed', 'error' => 'Query execution failed: '. mysqli_error($db_link));
				}else{
					if(mysqli_num_rows($result) > 0){
						$rows = array();
						while($result_array = mysqli_fetch_array($result)){
							$rows[] = $result_array['name'];
						}
						$response = array('result' => 'success', 'name'=>$rows);
					}else{
						$response = array('result' => 'failed', 'error' => 'no data found');
					}
				}
				/* disconnect from the db */
				mysqli_close($db_link);
			}
		}else{
			$response = array('result' => 'failed', 'error' => "Query param 'name' is missing");
		}
	}else{
		$response = array('result' => 'failed', 'error' => "only GET method available.");
	}
	echo json_encode($response);
?>