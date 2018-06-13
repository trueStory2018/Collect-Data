<?php
	header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
	//Necessary params
	$request = $_POST['request'];
	$debug = isset($_POST['debug']) ? $_POST['debug'] : 0;
	
	$resultArray = array(
					'result' => 0,
					'resultText' => 'OK'
				);

	
	if (strcmp($request, 'getIdol')==0) {
		$idolNames = $_POST['idolNames'];
		include_once "getIdol.php";
	}

	else if (strcmp($request, 'insertIdol')==0) {
		$username = $_POST['username'];
		include_once "insertIdol.php";
	}

	else if (strcmp($request, 'getResults')==0) {
		$uid = $_POST['uid'];
		include_once "getResults.php";
	}

	else if (strcmp($request, 'getTracking')){
		include_once "getTracking";
	}

	else {
		$resultArray['result'] = '-1';
		$resultArray['resultText'] = 'Bad request';
	}

	echo json_encode($resultArray);
?>

