<?php
	//error_reporting(0);
	require __DIR__.'/../vendor/autoload.php';
	include 'functions.php';
	include 'config/params.php';
	$debug = false;
	$truncatedDebug = false;
	$beginning = microtime(true);
	$deployment = true; //When this is set to false -- we are doing pre deployment and would behave differently
	//If we wish to show in browser - change to true
	\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = false;
	$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
	$counter = 0;
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
	echo PHP_EOL.'Get User Data starts';
	include_once "getUserData.php";
	echo PHP_EOL.'Total getUserData time: '.(microtime(true) - $getUserData).' Seconds';
	//Necessary params
	echo PHP_EOL.'Total execution time: '.(microtime(true) - $beginning).' Seconds';
	
?>

