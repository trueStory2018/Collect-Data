<?php
	//error_reporting(0);
	require __DIR__.'/../vendor/autoload.php';
	include 'functions.php';
	include_once '/config/params.php';
	$debug = false;
	$truncatedDebug = false;
	$beginning = microtime(true);
	$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
	$innerDebug = isset($_POST['debug']) ? $_POST['debug'] : false;
	
	try {
	    $ig->login($username, $password);
	    //$rankToken = \InstagramAPI\Signatures::generateUUID();
	} catch (\Exception $e) {
	    echo 'Something went wrong: '.$e->getMessage()."\n";
	    exit(0);
	}
	$getIdolData = microtime(true);
	echo 'Get Idol Data starts';
	include_once "getIdolData.php";
	echo PHP_EOL.'getIdolData done';
	echo PHP_EOL.'Total getIdolData time: '.(microtime(true) - $getIdolData).' Seconds';
	
	$getUserData = microtime(true);
	echo 'Get Idol Data starts';
	include_once "getUserData.php";
	echo PHP_EOL.'Total getUserData time: '.(microtime(true) - $getUserData).' Seconds';
	//Necessary params
	echo PHP_EOL.'Total execution time: '.(microtime(true) - $beginning).' Seconds';
	
	$resultArray = array(
					'result' => 0,
					'resultText' = 'OK'
				);
?>

