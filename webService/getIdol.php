<?php
	$resultArray['data'] = array();
	foreach ($idolNames as $idolName) {
		try{
			$url = 'http://www.instagram.com/'.$idolName.'/';
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER,0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$response = curl_exec($ch);
			echo curl_error($ch);
			$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if($http=="200") {
				$doc = new DOMDocument();
				$doc->loadHTML($response);
				$xpath = new DOMXPath($doc);
				//Find JS to remove it
				$js = $xpath->query('//body/script[@type="text/javascript"]')->item(0)->nodeValue;
				$start = strpos($js, '{');
				$end = strrpos($js, ';');
				$json = substr($js, $start, $end - $start);
				$responseArray = json_decode($json, true);
				array_push($resultArray['data'],array(
										'userID' 			=> $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['id'],
										'username' 			=> $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['username'],
										'fullName' 			=> $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['full_name'] != '' ? $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['full_name'] : false,
										'profilePicture' 	=> !is_null($responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['profile_pic_url']) ? $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['profile_pic_url'] : false,
										'counts' => array(
												'Followers' => $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['edge_followed_by']['count'],
												'Following' => $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['edge_follow']['count'], 
												'Media' => $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['count']
											)
										));
			}
			//Check if user exists on our DB - if yes - say when is the latests analysis
			$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols';
			$url.='?q={"_id":"'.$responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['id'].'"}';
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
			if (count($user)>0)
				$resultArray['data']['timestamp'] = $user['lastModified'];
		}
		catch (Exception $e) {
			$resultArray[$idolName]['data'] = false;
		}
	}
?>