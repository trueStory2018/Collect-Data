<?php
	function collectData($ig, $uid, $idol = false, $debug = false) {
		$maxRuns = 8;
		$time_start = microtime(true); 
		if ($debug)
			echo PHP_EOL.'Collecting data for '.$uid.PHP_EOL;
		$response = json_decode(json_encode($ig->people->getInfoById($uid)));
		if ($response->status=='ok'){
			$person = array(
				'_id' 				=> $uid,
				'username' 			=> $response->user->username,
				'fullName' 			=> $response->user->full_name != '' ? $response->user->full_name : false,
				'private' 			=> $response->user->is_private != '' ? true : false, 
				'verified' 			=> $response->user->is_verified != '' ? true : false,
				'profilePicture' 	=> !is_null($response->user->hd_profile_pic_url_info->url) ? $response->user->hd_profile_pic_url_info->url : false,
				'counts' 			=> array(
										'Followers' => $response->user->follower_count,
										'Following' => $response->user->following_count, 
										'UserTags' => $response->user->usertags_count, 
										'Media' => $response->user->media_count
									),
				'bio' 				=> $response->user->biography != '' ? $response->user->biography : false,
				'city' 				=> !is_null($response->user->city_name) ?  $response->user->city_name : false,
				'publicEmail' 		=> !is_null($response->user->public_email) ?  $response->user->public_email : false,
				'birthday' 			=> !is_null($response->user->birthday) ? $response->user->birthday : false
				);
		}
		else
			return;
		
		if ($debug)
			echo microtime(true) - $time_start.' seconds'.PHP_EOL;
		sleep(5);
		if ($idol) {
			$counter = 0;
			$maxId = null;
			$arr = array();
			do {
				$response = $ig->people->getFollowers($uid,$maxId);
				foreach ($response->users as $user) {
					if ($user!=null)
						if ($user->pk != $selfID)
							array_push($arr, $user->pk);
					$maxId = $response->getNextMaxId();
				}
				sleep(2);
				$counter++;
			} while ($maxId !== null && $counter<$maxRuns);

			pushData('idols',$arr,$uid,'followers');

			//Currently irrelevant
			/*$response = $ig->people->getFollowing($uid);
			$arr = array();
			foreach ($response->users as $user) {
				array_push($arr, $user->pk);
			}
			$person['following'] = $arr;*/
		}
		//initial insert
		pushData('users',$person);

		//////////////////Get MEDIA

		$maxId = null;
		$arr = array();
		$counter = 0;
		if (!$person['private']){
			do {
				if ($debug) {
					echo 'Media loop #'.$counter.': ';
					echo (microtime(true) - $time_start).' seconds'.PHP_EOL;
				}
		        // Request the page corresponding to maxId.
		        $response = $ig->timeline->getUserFeed($uid, $maxId);
		        foreach ($response->getItems() as $item) {
		        	$maxId = $response->getNextMaxId();
		        	//Currently unnecessary:
			        	/*$likersResponse = $ig->media->getLikers($item->getId());
			        	sleep(5);
			        	$commentsResponse = $ig->media->getComments($item->getId());*/
			        	//$likersArr = array();
			        	//$commentsArr = array();
		        	if ($item!=null){
				        	//Currently unnecessary:
				        		//Get all likers filtered
				        	/*foreach ($likersResponse->users as $like)
			        			array_push($likersArr,$like->pk);*/
				        	
			        	////Get all comments filtered
		        		try{
		        			//Currently unnecessary
				        		/*foreach($commentsResponse->comments as $comment)
			        			array_push($commentsArr,array('id' => $comment->user_id,'timestamp' => $comment->created_at_utc,'text'=>$comment->text));*/

			        		$itemArr = array(
			        			'timestamp' => !is_null($item->taken_at) ? $item->taken_at : false,
			        			'counts' => array(
			        							'comments' => $item->comment_count,
			        							'likes' => $item->like_count
			        							),
			        			'type' => !is_null($item->media_type) ? $item->media_type : false,
			        			'text' => isset($item->caption->text) ? $item->caption->text : false,
			        			'tags' => array()
			        		);
			        		if (isset($item->usertags->in)) {
				        		foreach ($item->usertags->in as $tag) {
				        			if ($tag->user->username!=$person['username']) {
				        				array_push($itemArr['tags'], $tag->user->username);
				        			}
				        		}
				        	}
			        		if (empty($itemArr['tags']))
			        			$itemArr['tags'] = false;
			        		//Currently unnecessary
				        		//$itemArr['likers'] = $likersArr;
				        		//$itemArr['comments'] = $commentsArr;
			        		array_push($arr,$itemArr);
			        	}
			        	catch (Exception $e){
		        			var_dump($e);
		        		}
		        	}
		    	}
		        // Sleep for 5 seconds before requesting the next page. This is just an
		        // example of an okay sleep time. It is very important that your scripts
		        // always pause between requests that may run very rapidly, otherwise
		        // Instagram will throttle you temporarily for abusing their API!
		        $counter++;
		        sleep(5);

		    } while ($maxId !== null && $counter<$maxRuns);
		}
		else {
			//Try to follow the private user
			$ig->people->follow($uid);
			//Try to send him a message, and ask him to accept our follow request
			 $recepients['users'] = array();
		    array_push($recepients['users'], $uid);
		    print_r($recepients);
		    $ig->direct->sendText($recepients,'Hello '.$person['fullName'].'! We at trueStory would love it if you accept our follow request');
		}
	    if ($debug){
	    	$time_end = microtime(true);
	    	$execution_time = ($time_end - $time_start);
			echo 'Total Execution Time: '.$execution_time.' seconds'.PHP_EOL;
	    }
	    if (!$idol)
	    	pushData('users',$arr,$uid,'media');

	}

	function getIdols($uid = null) {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols';
		if ($uid!=null)
			$url.='?q={"_id":"'.$uid.'"}';
		else
			$url.='?q={"collected":false}';
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

	function getIdolFollowers($uid) {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols?q={"_id":"'.$uid.'"}?f={"followers":"1"}&apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc';
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

	function pushData($collection, $data, $uid = false,$action=null) {
		$url = "https://api.mlab.com/api/1/databases/analysis/collections/".$collection;
		if ($uid) {
			if (strcmp($action, 'media') == 0) {
				$data = array('$push' => array('media' => $data));
				$url .= '?q={"_id":"'.$uid.'"}&';
			}
			else if (strcmp($action, 'followers') == 0) {
				$data = array('$push' => array('followers' => $data));
				$url .= '?q={"_id":"'.$uid.'"}&';
			}
			else if (strcmp($action, 'delete') == 0) {
				//We will handle this later
			}
		}
		else
			$url.='?';
		$url.='apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'Connection: Keep-Alive'
	    ));
		curl_setopt($ch, CURLOPT_POST, 1);
		if (!is_null($action))
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);

		if (strcmp($action, 'followers')==0) {
			$url = "https://api.mlab.com/api/1/databases/analysis/collections/".$collection;
			$data = array('$set' => array('collected' => true));
			$url .= '?q={"_id":"'.$uid.'"}';
			$url.='&apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc';
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json',
			    'Connection: Keep-Alive'
		    ));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
		}

		return;
	}

	/*
	*	Functions for Tracking users
	*	getTrackedUsers, getUserSnapshot
	*/

	function getTrackedUsers($uid = null) {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/trackedUsers';
		if ($uid!=null)
			$url.='?q={"_id":"'.$uid.'"}&';
		else
			$url.='?';
		$url.='apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc&';
		
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

	function getUserSnapshot($username) {
		$json = '';
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
		}
		return json_decode($json,true);
	}
?>