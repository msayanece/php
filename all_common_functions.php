<?php
	function sql_connect($runConfigs=false){	
		$db_link = mysqli_connect('localhost','root','password','hello-world');
		return $db_link;
	}
?>