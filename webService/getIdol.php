<?php
	try{
		$url = 'http://www.instagram.com/'.$idolName.'/?__a=1';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$response = curl_exec($ch);
		echo curl_error($ch);
		curl_close($ch);
		$responseArray = json_decode($response,true);
		$resultArray['data'] = array(
									'userID' 			=> $responseArray['graphql']['user']['id'],
									'username' 			=> $responseArray['graphql']['user']['username'],
									'fullName' 			=> $responseArray['graphql']['user']['full_name'] != '' ? $responseArray['graphql']['user']['full_name'] : false,
									'profilePicture' 	=> !is_null($responseArray['graphql']['user']['profile_pic_url_hd']) ? $responseArray['graphql']['user']['profile_pic_url_hd'] : false,
									'counts' => array(
											'Followers' => $responseArray['graphql']['user']['edge_followed_by']['count'],
											'Following' => $responseArray['graphql']['user']['edge_follow']['count'], 
											'Media' => $responseArray['graphql']['user']['edge_owner_to_timeline_media']['count']
										)
									);
	}
	catch (Exception $e) {
		$resultArray['result'] = 1;
		$resultArray['resultText'] = substr($e->getMessage(),strpos($e->getMessage(), ' ')+1);
	}
?>