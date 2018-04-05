<?php
		require __DIR__.'/../vendor/autoload.php';
		/*include_once '/getDriveAccess.php';
		$file = new Google_Service_Drive_DriveFile;
		$file->setName('test');
		$file->setFileExtension('json');
		$response = $service->files->create($file,array(
		  'mimeType' => '',
		  'uploadType' => 'media'
		));
		var_dump($response);*/
		$username = 'hola.halo777';
		$password = 'lama!!123';
		$debug = false;
		$truncatedDebug = false;

		$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
		
		
		try {
		    $ig->login($username, $password);
		} catch (\Exception $e) {
		    echo 'Something went wrong: '.$e->getMessage()."\n";
		    exit(0);
		}
		try {
			$person = array();
		    // Get the UserPK ID for "natgeo" (National Geographic).
		    $rankToken = \InstagramAPI\Signatures::generateUUID();
		    /*$recepients['users'] = array();
		    array_push($recepients['users'], '4226040806');
		    print_r($recepients);
		    echo '<pre>';
		    $messageResponse = $ig->direct->sendText($recepients,'Hey Terry, its Eldad. This is just a text to see if you are a true story');
		    print_r($messageResponse);
		    echo '</pre>';*/
		    //$uid = $ig->people->getUserIdForName('wild.feather');
		    if (false == false || false > '112512')
		    	echo 'blas';
		    /*//Follow
		    //$followResponse = $ig->people->follow($uid);

		    //Send Message
		    $recepients['users'] = array();
		    array_push($recepients['users'], $uid);
		    print_r($recepients);
		    echo '<pre>';
		    $messageResponse = $ig->direct->sendText($recepients,'Hey Terry, its Eldad. This is just a text to see if you are a true story');
		    print_r($messageResponse);
		    echo '</pre>';*/
		    //include_once "getPersonalInfo.php";
			
		} catch (\Exception $e) {
		    echo 'Something went wrong: '.$e->getMessage()."\n";
		}
		/*//Follow
				    //$followResponse = $ig->people->follow($uid);

				    //Send Message
				    $recepients['users'] = array();
				    array_push($recepients['users'], $uid);
				    print_r($recepients);
				    echo '<pre>';
				    $messageResponse = $ig->direct->sendText($recepients,'Hey Terry, its Eldad. This is just a text to see if you are a true story');
				    print_r($messageResponse);
				    echo '</pre>';*/
				    //include_once "getPersonalInfo.php";
?>