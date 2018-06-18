<?php
	try {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols';
		if ($uid!=null)
			$url.='?q={"_id":"'.$uid.'"}';
		$url.='&apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc';
		
		//Get all idols where no data has been collected for yet

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
	    'Connection: Keep-Alive'
	    ));

		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);
		$user = json_decode($response,true)[0];
		$resultArray['data'] = array('id' 				=> 	$user['_id'],
									'username'			=>	$user['userName'],
									'fullName'			=>	$user['fullName'],
									'profilePicture'	=>	$user['profilePicture']);
		$followerResults = array();
		foreach ($user['followers'][0] as $follower) {
			$url = 'https://api.mlab.com/api/1/databases/analysis/collections/users';
			if ($uid!=null)
				$url.='?q={"_id":"'.$follower.'"}';
			$url.='&apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc&';
			//Get all idols where no data has been collected for yet

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'Connection: Keep-Alive'
		    ));

			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
			$response = json_decode($response,true)[0];
			array_push($followerResults, array('username'			=>	$response['username'],
												'results'			=>	isset($response['trackingResults']) ? $response['trackingResults'] : array(
																		'isBot' => $response['analysisResults'] > 50 ? true : false,
																		'certainty' => $response['analysisResults']
																		)
												));
		}
		$resultArray['data']['results'] = $followerResults;
	}
	catch (Exception $e) {
		$resultArray['result'] = -1;
		$resultArray['resultText'] = $e->getMessage();
	}

?>