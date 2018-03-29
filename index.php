<?php
	error_reporting(0);
	require __DIR__.'/../vendor/autoload.php';
	include 'functions.php';
	$username = 'hola.halo777';
	$password = 'lama!!123';
	$debug = false;
	$truncatedDebug = false;

	$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
	
	
	try {
	    $ig->login($username, $password);
	    //$rankToken = \InstagramAPI\Signatures::generateUUID();
	} catch (\Exception $e) {
	    echo 'Something went wrong: '.$e->getMessage()."\n";
	    exit(0);
	}
	//Necessary params
	$request = $_POST['request'];
	$debug = $_POST['debug'];
	
	$resultArray = array(
					'result' => 0,
					'resultText' = 'OK'
				);

	try {
		if (strcmp($request, 'idolDataCollect')==0) {
			include_once "getIdolData.php";
		}

		else if (strcmp($request, 'userDataCollect')==0) {
			$idolId = $_POST['idolId'];
			include_once "getUserData.php";
		}

		else if (strcmp($request, 'getUser')==0) {
			include_once "getUser.php";
		}

		else if (strcmp($request, 'trackUsers')==0) {
			include_once "getUser.php";
		}

		else {
			$resultArray['result'] = '-1';
			$resultArray['resultText'] = 'Bad request';
		}
	}
	catch (Exception $e) {
		$resultArray['result'] = 1;
		$resultText['resultText'] = $e->getMessage();
	}

	echo json_encode($resultArray);
	/*include_once '/getDriveAccess.php';
		$file = new Google_Service_Drive_DriveFile;
		$file->setName('test');
		$file->setFileExtension('json');
		$response = $service->files->create($file,array(
		  'mimeType' => '',
		  'uploadType' => 'media'
		));
		var_dump($response);*/
?>

