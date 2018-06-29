<?php
	$resultArray['data'] = array();
	foreach ($idolNames as $idolName) {
		try{
			$idol = array();
			if (!$directUsernameSearch) 
				$urls = getURLs($idolName);
			else
				$urls  = array('https://www.instagram.com/'.$idolName);
			foreach ($urls as $url) {
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
					//We got a real user, we will push it to DB
					if (!empty($responseArray['entry_data']['ProfilePage'])) {
						$idol = array(
							'userID' 			=> $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['id'],
							'username' 			=> $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['username'],
							'fullName' 			=> $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['full_name'] != '' ? $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['full_name'] : false,
							'profilePicture' 	=> !is_null($responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['profile_pic_url']) ? $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['profile_pic_url'] : false,
							'counts' => array(
									'Followers' => $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['edge_followed_by']['count'],
									'Following' => $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['edge_follow']['count'], 
									'Media' => $responseArray['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['count']
								)
							);				
						//Check if the user exists on our DB
						$user = getIdolFromDB($idol['userID']);
						if (count($user)>0)
							$idol['timestamp'] = $user[0]['lastModified'];

						array_push($resultArray['data'],$idol);
					}
				}
			}
		}
		catch (Exception $e) {
			$resultArray[$idolName]['data'] = false;
		}
	}


	function getURLs($keyWord) {
		//In case of username, to make a correct search
		//User Name keyword
		$UNkeyWord = str_replace(' ', '+', $keyWord);
		//No Space keyword
		$keyWord = str_replace(' ', '', $keyWord);
		$baseURL = 'https://www.instagram.com/';
		$url = 'https://www.google.com/search?q=instagram+'.$UNkeyWord;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$linksArr = explode('https://www.instagram.com/', $response);
		foreach ($linksArr as $key => $link) {
			$linksArr[$key] = $baseURL.substr($link, 0, strpos($link, '/'));
		}
		//Irrelevant text prior to links - we will replace it with an extreme case fix
		$linksArr[0] = $baseURL.$keyWord;
		$linksArr = array_unique($linksArr);
		return $linksArr;
	}

	function getIdolFromDB($uid) {
		//Check if user exists on our DB - if yes - say when is the latests analysis
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols';
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
		return json_decode($response,true);
	}
?>