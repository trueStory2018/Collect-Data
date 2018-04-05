<?php
	//Necessary params
	$request = $_POST['request'];
	$debug = isset($_POST['debug']) ? $_POST['debug'] : 0;
	
	$resultArray = array(
					'result' => 0,
					'resultText' => 'OK'
				);

	
	if (strcmp($request, 'getIdol')==0) {
		$idolName = $_POST['idolName'];
		include_once "getIdol.php";
	}

	else if (strcmp($request, 'insertIdol')==0) {
		$uid = $_POST['uid'];
		include_once "insertIdol.php";
	}

	else if (strcmp($request, 'getResults')==0) {
		$uid = $_POST['uid'];
		include_once "getResults.php";
	}

	else {
		$resultArray['result'] = '-1';
		$resultArray['resultText'] = 'Bad request';
	}
	

	echo json_encode($resultArray);
?>

