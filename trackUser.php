<?php
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
	//get inbox for private users
	$inbox = $ig->direct->getInbox();
	$threads = $inbox->inbox->threads;
	$usersToTrack = getTrackedUsers();
	foreach ($usersToTrack as $user) {
		$checkResult = $user;
		if ($user['trackingResults'])
			continue;
		$userSnapshot = getUserSnapshot($user['username']);
		if ($user['private']){
			//If user is private it means we have sent him a private message and a follow request
			foreach ($threads as $key => $value) {
				if (strcmp($user['username'],$value->users[0]->username)==0) {
					if (strcmp($value->users[0]->last_permanent_item->user_id,$user['_id'])==0) {
						$checkResults['messageResponse'] = true;
						$timestamp = $value->users[0]->last_permanent_item->timestamp;
						$text = $value->users[0]->last_permanent_item->text;
						//We might use external library to analyze the text - or use analyze data
					}
					else
						$checkResults['messageResponse'] = true;
				}
			}
			//See if the follow request was approved
			$checkResults['followApproved'] = count($userSnapshot['edge_media_collections']) > 0 ? true : false;
		}
		//Changes in number of followers, following or Media
		foreach ($user['counts'] as $key => $value) {
			if (strcmp($key,'Followers')==0) {
				$difference = abs($value - $userSnapshot['graphql']['user']['edge_followed_by']['count']);
				$checkResult['counts'][$key] = $userSnapshot['graphql']['user']['edge_followed_by']['count']);
			}
			if (strcmp($key,'Following')==0){
				$difference = abs($value - $userSnapshot['graphql']['user']['edge_follow']['count']);
				$checkResult['counts'][$key] = $userSnapshot['graphql']['user']['edge_follow']['count']);
			}
			if (strcmp($key,'Media')==0){
				$difference = abs($value - $userSnapshot['graphql']['user']['edge_owner_to_timeline_media']['count']);
				$checkResult['counts'][$key] = $userSnapshot['graphql']['user']['edge_owner_to_timeline_media']['count']);
			}
			//Now we rate the difference
			//We check if it's not the first run
			$checkResult['results'][$key] = isset($user['results']) ? $user['results'][$key] : 0;
			$checkResult += $difference / 100;
		}
		//Now we go over an amount of followers' timeline and see whether the user has commented on them
		$earliestTimestamp = time();
		$latestTimestamp = 0;
		$overallActions = 0;
		$maxRuns = 5;
		$counter = 0;
		$maxId = null;
		$followers = array();
		do {
			$response = $ig->people->getFollowers($user['_id'],$maxId);
			foreach ($response->users as $user) {
				if ($user!=null)
					array_push($arr, $user->pk);
				$maxId = $response->getNextMaxId();
			}
			sleep(2);
			$counter++;
		} while ($maxId !== null && $counter<$maxRuns);

		foreach ($followers as $uid) {
			$maxid = null;
			$response = $ig->timeline->getUserFeed($uid, $maxId);
	        foreach ($response->getItems() as $item) {	
	        	$likersResponse = $ig->media->getLikers($item->getId());
	        	if ($item!=null){
		        	foreach ($likersResponse->users as $like){
		        		if ($like->pk == $user['_id']){
		        			$overallActions++;
		        			break;
		        		}
		        	}
	        		sleep(1);
	        		$commentsResponse = $ig->media->getComments($item->getId());
		        	foreach($commentsResponse->comments as $comment){
		        		if ($comment->user_id == $user['_id']){
		        			$overallActions++;
		        			//If we haven't set an earliest timestamp or this one is earlier than the other one
		        			if (!$earliestTimestamp || $earliestTimestamp > $comment->created_at_utc)
		        				$earliestTimestamp = $comment->created_at_utc;
		        			if ($latestTimestamp < $comment->created_at_utc)
		        				$latestTimestamp = $comment->created_at_utc;
		        		}
		        	}
		        }
				sleep(2);
			}
		}
		//Calculate amount in time
		$actionRate = $overallActions / abs(($latestTimestamp - $earliestTimestamp)*60);
		if (!isset($checkResult['actionRate']))
			$checkResult['actionRate'] = array();
		array_push($checkResult['actionRate'],$actionRate);
		if (count($checkResult['actionRate'] == 3))
			$checkResult['trackingResults'] = calculateResults($checkResult['actionRate']);
		pushData('trackedUsers', $checkResult);
	}

	function calculateResults($results) {
		//We will enter an algorithm to calculate the results
	}

?>