<?php
	include 'all_common_functions.php';
	$method = $_SERVER['REQUEST_METHOD'];
	if('GET' == $method){
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
	}else if('POST' == $method){
		$post_data		=	file_get_contents("php://input");
		
		if($post_data != ''){
			$post_data_array = json_decode($post_data, true);
			if($post_data_array != null){
				if(isset($post_data_array['result'])){
					$result_string = $post_data_array['result'];
					if(is_string($result_string)){
						if(strcasecmp($result_string, "success") == 0){
							if( isset( $post_data_array['name'] ) ){
								$items = $post_data_array['name'];
								if(is_array($items)){
									if(!empty($items)){
										$message_string = '';
										foreach($items as $item){
											$message_string = $message_string.$item;
										}
										$response['result'] = 'success';
										$response['name'][] = $message_string;
									}else{
										$response = array('result' => 'failed', 'error' => "'name' array is empty");
									}
								}else{
									$response = array('result' => 'failed', 'error' => "'name' should be a JSON array");
								}
							}else{
								$response = array('result' => 'failed', 'error' => "JSON array with key 'name' does not exist");
							}
						}else{
							$response = array('result' => 'failed', 'error' => "'result: success' required in JSON body");
						}
					}else{
						$response = array('result' => 'failed', 'error' => "'result'should be a String");
					}
				}else{
					$response = array('result' => 'failed', 'error' => "'result: success' required in JSON body");
				}
			}else{
				$response = array('result' => 'failed', 'error' => 'Invalid JSON');
			}
		}else{
			$response = array('result' => 'failed', 'error' => 'POST method contains Empty body!');
		}
	}else{
		$response = array('result' => 'failed', 'error' => "Use HTTP 'GET' or 'POST' method to get currect output.");
	}
	echo json_encode($response);
?>