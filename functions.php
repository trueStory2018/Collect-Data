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
				'city' 				=> false,
				'publicEmail' 		=> false,
				'birthday' 			=> false,
				'media'				=> array()
				);
		}
		else
			return;
		
		if ($debug)
			echo microtime(true) - $time_start.' seconds'.PHP_EOL;
		sleep(5);
		try {
			if (!$idol)
				$ig->people->follow($uid);
		}
		catch (Exception $e) {
			echo "Can't follow user " . $person['username'] . ': ' .$e->getMessage() . "\n";
		}
		if ($idol) {
			$counter = 0;
			$maxId = null;
			$arr = array();
			try {
				do {
					$response = $ig->people->getFollowers($uid,$maxId);
					foreach ($response->users as $user) {
						if ($user!=null)
							if ($user->pk != $selfID)
								array_push($arr, $user->pk);
						$maxId = $response->getNextMaxId();
					}
					sleep(5);
					$counter++;
				} while ($maxId !== null && $counter<$maxRuns);

				pushData('idols',$arr,$uid,'followers');
			}
			catch (Exception $e) {
				echo "Can't Collect followers for " . $person['username'] . ': ' .$e->getMessage() . "\n";
			}
		}
		//initial insert
		//pushData('users',$person);
		//Push also to trackedUsers - since we will be tracking all users
		$trackingInfo = array(
			'_id' 		=> $person['_id'],
			'username' 	=> $person['username'],
			'private' 	=> $person['private'], 
			'verified' 	=> $person['verified'],
			'counts' 	=> $person['counts'],
			'timestamp' => microtime(true),
			'results'	=> array()
			);
	    pushData('trackedUsers',$trackingInfo);
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
		        try {
			        $response = $ig->timeline->getUserFeed($uid, $maxId);
			        foreach ($response->getItems() as $item) {
			        	$maxId = $response->getNextMaxId();

			        	if ($item!=null){
			        		try{
				        		$itemArr = array(
				        			'timestamp' => !is_null($item->getTakenAt()) ? $item->getTakenAt() : false,
				        			'counts' => array(
				        							'comments' => $item->getCommentCount(),
				        							'likes' => $item->getLikeCount()
				        							),
				        			'type' => !is_null($item->getMediaType()) ? $item->getMediaType() : false,
				        			'text' => !is_null($item->getCaption()) ? $item->getCaption()->getText() : false,
				        			'tags' => array()
				        		);
				        		if (!is_null($item->getUsertags())) {
					        		foreach ($item->getUsertags()->getIn() as $tag) {
					        			if ($tag->getUser()->getUsername()!=$person['username']) {
					        				array_push($itemArr['tags'], $tag->getUser()->getUsername());
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
			        			echo 'Error in collecting media item: '.$e->getMessage();
			        		}
			        	}
			    	}
			        // Sleep for 5 seconds before requesting the next page. This is just an
			        // example of an okay sleep time. It is very important that your scripts
			        // always pause between requests that may run very rapidly, otherwise
			        // Instagram will throttle you temporarily for abusing their API!
			        $counter++;
			        sleep(5);
			    }
			    catch (Exception $e) {
			    	echo "Can't collect media for user " . $person['username'] . ': ' .$e->getMessage() . "\n";
			    }
		    } while ($maxId !== null && $counter<$maxRuns);
		}
		else {
			//Try to send him/her a message, and ask him/her to accept our follow request
			 $recepients['users'] = array();
		    array_push($recepients['users'], $uid);
		   $ig->direct->sendText($recepients,'Hello '.$person['fullName'].'! We at trueStory would love it if you accept our follow request');
		}
	    if ($debug){
	    	$time_end = microtime(true);
	    	$execution_time = ($time_end - $time_start);
			echo 'Total Execution Time: '.$execution_time.' seconds'.PHP_EOL;
	    }
	    if (!$idol){
	    	pushData('users',$arr,$uid,'media');
	    }

	}

	function getIdols($uid = null) {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols';
		if ($uid!=null)
			$url.='?q={"_id":"'.$uid.'"}';
		else
			$url.='?q={"collected":false}';
		$url.='&apiKey='.$GLOBALS['mlabApi'].'&';
		
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
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols?q={"_id":"'.$uid.'"}?f={"followers":"1"}&apiKey='.$GLOBALS['mlabApi'];
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
			if (strcmp($action, 'delete') != 0) {
				if (strcmp($action, 'collected')==0 || strcmp($action, 'analysisResults')==0)
					$data = array('$set' => array($action => $data));
				else
					$data = array('$push' => array($action => $data));
				$url .= '?q={"_id":"'.$uid.'"}&';
			}
			else {
				$url .= '/'.$uid.'?';
			}
		}
		else
			$url.='?';
		$url.='apiKey='.$GLOBALS['mlabApi'];
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'Connection: Keep-Alive'
	    ));
		curl_setopt($ch, CURLOPT_POST, 1);
		if (!is_null($action)) {
			if (strcmp($action,'delete')!=0)
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			else 
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
		//If we delete - we don't send any data
		if (strcmp($action,'delete')!=0)
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);

		if (strcmp($action, 'followers')==0) {
			$url = "https://api.mlab.com/api/1/databases/analysis/collections/".$collection;
			$data = array('$set' => array('collected' => true));
			$url .= '?q={"_id":"'.$uid.'"}';
			$url.='&apiKey='.$GLOBALS['mlabApi'];
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
	*	getTrackedUsers, getUserSnapshot, getUsers
	*/

	function getTrackedUsers($uid = null) {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/trackedUsers';
		if ($uid!=null)
			$url.='?q={"_id":"'.$uid.'"}&';
		else
			$url.='?';
		$url.='apiKey='.$GLOBALS['mlabApi'].'&';
		
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

	function getUsers($uid = null) {
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/users';
		if ($uid!=null)
			$url.='?q={"_id":"'.$uid.'"}&';
		else
			$url.='?';
		$url.='apiKey='.$GLOBALS['mlabApi'].'&';
		
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

	function getUserSnapshot($username,$ig = null, $uid = false) {
		try {
			$json = '';
			$url = 'http://www.instagram.com/'.$username.'/';
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
			if (strcmp('',$json)==0)
				throw new Exception ('Empty json');
		}
		catch (Exception $e) {
			echo PHP_EOL.$e->getMessage().PHP_EOL;
			sleep(5);
			//Backup in case we can't get snapshot
			if ($uid && !is_null($ig)) {
				$response = json_decode(json_encode($ig->people->getInfoById($uid)));
				sleep(5);
				try {
					$mediaResponse = $ig->timeline->getUserFeed($uid, null);
					sleep(5);
				}
				//If we are not authorized to see user, we were not yet approved
				catch (\Exception $e) {
					$mediaResponse = null;
				}
				$json = array(
					'entry_data' => array(
							'ProfilePage'	=>	
								array(
									array('graphql'	=>	
										array('user'	=>	array(
											'edge_follow'	=>	array('count'	=>	$response->user->following_count),
											'edge_owner_to_timeline_media'	=>	array(
												'count'	=>	$response->user->media_count,
												//Empty array - still can't see photos. Not empty array -> follow approved
												'edges'	=> is_null($mediaResponse) ? array() : array(1)
												),
											'edge_followed_by'	=>	array('count'	=>	$response->user->follower_count)
										)
									)
								)
							)
						)
					);
			}
		}
		if (is_array($json))
			return $json;
		return json_decode($json,true);		
	}
?>