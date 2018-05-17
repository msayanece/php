<?php
	if (!$db_link = mysqli_connect('localhost','root','password')) {
		$arr = array('result' => 'failed', 'error' => 'MySql Connection failed: ' . mysqli_connect_error());
	}else{
		$db = 'hello-world';
		$db_selected = mysqli_select_db($db_link, $db);
		if(!$db_selected){
			$arr = array('result' => 'failed', 'error' => 'Cannot connect to the Database '.$db);
		}else{
			/* grab the posts from the db */
			$query = 'SELECT * FROM test';
			$result = mysqli_query($db_link, $query);
			if(!$result){
				$arr = array('result' => 'failed', 'error' => 'Query execution failed: '. mysqli_error($db_link));
			}else{
				if(mysqli_num_rows($result) > 0){
					$rows = array();
					while($result_array = mysqli_fetch_array($result)){
						$rows[] = $result_array['name'];
					}
					$arr = array('result' => 'success', 'name'=>$rows);
				}else{
					$arr = array('result' => 'failed');
				}
			}
		}
		/* disconnect from the db */
		mysqli_close($db_link);
	}
	echo json_encode($arr);
?>