<?php
	try {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols';
		if ($uid!=null)
			$url.='?q={"_id":"'.$uid.'"}';
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
		$user = json_decode($response,true);
		$resultArray['data'] = array('id' 				=> 	$user['_id'],
									'username'			=>	$user['username'],
									'fullName'			=>	$user['fullName'],
									'profilePicture'	=>	$user['profilePicture']);
		$followerResults = array();
		foreach ($user['followers'] as $key => $value) {
			$url = 'https://api.mlab.com/api/1/databases/analysis/collections/users';
			if ($uid!=null)
				$url.='?q={"_id":"'.$key.'"}';
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
			
			array_push($followerResults, array('username'			=>	$response['username'],
												'profilePicture'	=>	$response['profilePicture'],
												'results'			=>	isset($response['trackingResults']) ? $response['trackingResults'] : array(
																		'bot' => $response['analysisResults'] > 2 ? true : false,
																		'certainty' => $response['analysisResults']*0.2
																		)
												);
		}
		$resultArray['data']['results'] = $followerResults;
	}
	catch (Exception $e) {
		echo $e->getMessage();
	}

?>