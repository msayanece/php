<?php
include_once('../includes/links.php');

$name		=	$_REQUEST['name'];
$response	=	array();

$postData		=	file_get_contents("php://input");
$postDataArr	=	json_decode($postData,true);

//print_r($postDataArr);die;

foreach($postDataArr['tests'] as $key=>$value){
	$sql	=	array();
	$sql['QUERY']	=	"INSERT INTO ".DB_TEST."
							SET 
								`name`				=	?,
								`status`			=	?,
								`request`			=	?,
								`createdDate`		=	?,
								`createdIP`			=	?";
								
	$sql['PARAM'][]	=	array('FILD' => 'name',  			'DATA' => $value['name'],			'TYP' => 's');
	$sql['PARAM'][]	=	array('FILD' => 'status',  			'DATA' => 'Yes',					'TYP' => 's');
	$sql['PARAM'][]	=	array('FILD' => 'request',  		'DATA' => serialize($_REQUEST),		'TYP' => 's');
	$sql['PARAM'][]	=	array('FILD' => 'createdDate',  	'DATA' => date('Y-m-d H;i:s'),		'TYP' => 's');
	$sql['PARAM'][]	=	array('FILD' => 'createdIP',  		'DATA' => $_SERVER['REMOTE_ADDR'],	'TYP' => 's');

	$res			=	$mycms->sql_insert($sql);

	$response[$key]['id']	=	$value['id'];
}

	$data['tests']					=	$response;
	$data['result']					=	"success";

echo json_encode($data)

?>

