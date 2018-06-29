<?php
	require __DIR__.'/../vendor/autoload.php';
	include 'functions.php';
	include_once '/config/params.php';
	$debug = true;
	$IGdebug = false;
	$truncatedDebug = false;
	$beginning = microtime(true);
	$ig = new \InstagramAPI\Instagram($IGdebug, $truncatedDebug);
	
	$now = microtime(true);
	$limit = strtotime('-2 day',$now);
	
	try {
	    $ig->login($username, $password);
	    //$rankToken = \InstagramAPI\Signatures::generateUUID();
	} catch (\Exception $e) {
	    echo 'Something went wrong: '.$e->getMessage().PHP_EOL;
	    exit(0);
	}
	//get inbox for private users
	try {
		$inbox = $ig->direct->getInbox();
		$threads = $inbox->getInbox()->getThreads();
	}
	catch (\Exception $e) {
		echo "Can't access inbox: ".$e->getMessage();
	}
	$usersToTrack = getTrackedUsers();
	echo 'Starting '.$now.PHP_EOL;
	
	foreach ($usersToTrack as $key => $user) {

		echo 'Checking results for '.$user['username'].PHP_EOL;
		echo (microtime(true) - $now).' seconds'.PHP_EOL;

		$userSnapshot = getUserSnapshot($user['username'],$ig,$user['_id'],$user['private']);
		if (empty($userSnapshot)) {
			echo 'Error: Empty snapshot'.PHP_EOL;
			continue;
		}
		try{
			if ($user['private']){
				//If user is private it means we have sent him a private message and a follow request
				foreach ($threads as $thread) {
					if (strcmp($user['username'],$thread->getUsers()[0]->getUsername())==0) {
						if (strcmp($thread->getLastPermanentItem()->getUserId(),$user['_id'])==0) {
							$usersToTrack[$key]['messageResponse'] = true;
							$timestamp = $thread->getLastPermanentItem()->getTimestamp();
							$text = $thread->getLastPermanentItem()->getText();
							//We might use external library to analyze the text - or use analyze data
						}
						else
							$usersToTrack[$key]['messageResponse'] = false;
					}
				}
				//See if the follow request was approved
				$usersToTrack[$key]['followApproved'] = count($userSnapshot['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges']) > 0 ? true : false;
			}
		}
		catch (exception $e){
			echo "Exception thrown after ".(microtime(true)-$now).PHP_EOL.'Message: '.$e->getMessage().PHP_EOL;
		}
		//Changes in number of followers, following or Media
		$difference = intval(0);
		foreach ($user['counts'] as $innerKey => $value) {
			if (strcmp($innerKey,'Followers')==0) {
				$usersToTrack[$key]['counts'][$innerKey] = $userSnapshot['entry_data']['ProfilePage'][0]['graphql']['user']['edge_followed_by']['count'];
			}
			//If it's following or media, we are more interested since it's a user's action
			else if (strcmp($innerKey,'Following')==0){
					$difference += (float)abs($value - $userSnapshot['entry_data']['ProfilePage'][0]['graphql']['user']['edge_follow']['count']);
					$usersToTrack[$key]['counts'][$innerKey] = $userSnapshot['entry_data']['ProfilePage'][0]['graphql']['user']['edge_follow']['count'];
			}
			else if (strcmp($innerKey,'Media')==0){
				$difference += (float)abs($value - $userSnapshot['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['count']);
				$usersToTrack[$key]['counts'][$innerKey] = $userSnapshot['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['count'];
			}
		}
		array_push($usersToTrack[$key]['results'],$difference);
		sleep(5);
	}
	//Now we will see the updates from all of the users we follow and try to estimate their action rate
	$maxId = null;
	$tempResults = array();
	//Get up-to-date user status
	echo 'Colleting timeline results'.PHP_EOL;
	echo (microtime(true) - $now).' seconds'.PHP_EOL;
	do {
        // Request the page corresponding to maxId.
        $response = $ig->people->getFollowingRecentActivity($maxId);
        //Adding info to tracking results
        foreach ($response->getStories() as $item) {
        	if (!isset($tempResults[$item->getArgs()->getLinks()[0]->getId()])) {
	    		$tempResults[$item->getArgs()->getLinks()[0]->getId()] = array();
        	}
        	//number of users in action
        	$actionAmount = count($item->getArgs()->getLinks());
        	switch ($item->getStoryType()) {
	    		case '13':
	    			# User likes a comment - Intenal "weight" - 1
	    			switch ($item->getType()) {
	    				case '14':
	    					# One comment
	    					$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval(1);
	    					break;
	    				
	    				default:
	    					# multiple comments
	    					$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval($actionAmount>1 ? $actionAmount-1 : $actionAmount);
	    					break;
	    			}
	    			break;
	    		
	    		case '60':
	    			# liked posts - Internal "weight" - 3-5
	    			switch ($item->getType()) {
	    				case '1':
	    					# liked 1 post
	    					$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval(5);
	    					break;
	    				
	    				default:
	    					# liked multiple posts
		    				if (($actionAmount>1 ? $actionAmount-1 : $actionAmount) < 10) {
		    					$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval($actionAmount>1 ? $actionAmount-1 : $actionAmount)*3;
		    				}
		    				else{
		    					$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval($actionAmount>1 ? $actionAmount-1 : $actionAmount)*5;
		    				}
	    					break;
	    			}
	    			break;

	    		case '101':
	    			# Follow users - internal "weight" - 7-10
	    			switch ($item->getType()) {
	    				case '4':
	    					# Follow one user
	    					$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval(10);
	    					break;
	    				
	    				default:
	    					# Follow multiple users
	    					if (($actionAmount>1 ? $actionAmount-1 : $actionAmount) < 10) {
	    						$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval($actionAmount>1 ? $actionAmount-1 : $actionAmount)*7;
	    					}
	    					else {
								$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval($actionAmount>1 ? $actionAmount-1 : $actionAmount)*10;
	    					}
	    					break;
	    			}
	    			break;

	    		default:
	    			//No internal weight
	    			$tempResults[$item->getArgs()->getLinks()[0]->getId()][$item->getArgs()->getTimestamp()] = intval($actionAmount>1 ? $actionAmount-1 : $actionAmount);
	    			break;
	    	}
	    	$earliestTimestamp = $item->getArgs()->getTimestamp();

	    	//If it's been more than 24 hours ago - we will stop the current loop
        	if ($earliestTimestamp <= $limit)
        		break;
    	}
        sleep(5);
        $maxId = $response->getNextMaxId();
    } while ($earliestTimestamp > $limit);
    unset($key);
    foreach ($tempResults as $userId => $array) {
    	//Find the relevant user
    	$key = -1;
       	foreach ($usersToTrack as $innerKey => $value) {
       		if ($value['_id'] == $userId) {
       			$key = $innerKey;
       			break;
       		}
       	}
       	if ($key == -1) 
       		//Error
       		continue;
    	$overallActions = 0.00;
    	//We will pop the last result to get the most recent amount of actions (0 is default)
       	if (!empty($usersToTrack[$key]['results']))
       		$overallActions += array_pop($usersToTrack[$key]['results']);

    	foreach ($array as $actions ) {
    		//We will calculate amount of actions according to amount of time
    		$overallActions+=$actions;
       	}
       	//Since the values are descending - the first is the latest and the last is the earlies
       	if (count($array) == 1) {
       		reset($array);
       		$earliestTimestamp = key($array);
       		$latestTimestamp = $now;
       	}
       	//Get the latest timestamp
       	else {
       		reset($array);
       		$latestTimestamp = key($array);
       		end($array);
       		$earliestTimestamp = key($array);
       	}
       	$actionRate = round($overallActions / abs(($latestTimestamp - $earliestTimestamp)/3600),2);
       	array_push($usersToTrack[$key]['results'],$actionRate);
    }

	//Now finally we go over all the up-to-date results after checking snapshots, messages and activity and push the final results to our users table
	 if ($debug) {
		$filename = 'logs/log '.intval(microtime(true)).'.json';
		$fp = fopen($filename, 'w+');
		fwrite($fp, "[");
		fclose($fp);
	}
	foreach ($usersToTrack as $key => $trackedUser) {
		try{
			if ($key>0 && $debug){
				$fp = fopen($filename, 'a+');
				fwrite($fp, ",");
				fclose($fp);
			}
			if (count($trackedUser['results']) >= 3 || $trackedUser['timestamp'] < strtotime('-3 day',$now)){
				$user = calculateResults($trackedUser,true);
				//Push final results to users table and delete the user from trackedUsers so we will not track him again
				pushData('trainingResults',$user);
				pushData('trackedUsers',array(),$user['_id'],'delete');
				try {
					$ig->people->unfollow($user['_id']);
				}
				catch (Exception $e) {
					echo "Can't unfollow user " . $trackedUser['username'] . ': ' .$e->getMessage() . "\n";
				}
			}
			else {
				//FailSafe for first run
		       	if (empty($usersToTrack[$key]['results']))
		       		array_push($usersToTrack[$key]['results'],0.00);
		       	$user = $trackedUser;
		       	$user['certainty'] = calculateResults($trackedUser);
		       	//Push results to DB
	       		pushData('trackedUsers', $user);
				pushData('analysisResults',calculateResults($trackedUser)['certainty'],$user['_id'],'analysisResults');
				unset($user);
			}
			if ($debug) {
				$fp = fopen($filename, 'a+');
				fwrite($fp, json_encode($trackedUser));
				fclose($fp);
			}
		}
		catch (Exception $e) {
			echo 'Something went wrong: ' . $e->getMessage();
		}
	}
	if ($debug) {
		$fp = fopen($filename, 'a+');
		fwrite($fp, ']');
		fclose($fp);
	}

	echo 'Overall: '.(microtime(true) - $now);

		#if lastRun set to true - this function will return an array of the user to push to DB
		#if set to false - will return calculation only
	function calculateResults($user,$lastRun = false) {
		//We will take results from analysis and compare with the tracking
		$calculateResults = array('bot'	=> false, 'certainty' => 0.00);
		$analysedUser = getUsers($user['_id'])[0];
		//If w have not put this variable yet, we will add the first analysis
		$analysisResults = isset($analysedUser['analysisResults']) ? $analysedUser['analysisResults'] : makeFirstAnalysis($analysedUser);
		//Combine all of the actions together
		$user['actionRate'] = 0;
		foreach ($user['results'] as $actions)
			$user['actionRate'] += $actions;
		if (count($user['results'] > 2)) {
			if ($user['private'] && $user['followApproved'])
				$user['actionRate'] -= 5;
			if ($user['private'] && $user['messageResponse'])
				$user['actionRate'] -= 5;
			if ($user['actionRate'] < 0)
				$user['actionRate'] = 0;
		}
		//If no action then the analysis results will determine
		if ($user['actionRate'] == 0) {
			$user['actionRate'] += $analysisResults;
		}
		//If still no actions
		if ($analysedUser['isSuspicious'] && $user['actionRate'] == 0){
			$certainty = 100;
		}
		else{
			//Prevent division by zero and keep normalization
			if ($user['counts']['Media'] == 0)
				$user['counts']['Media']++;
			if ($user['counts']['Followers'] == 0)
				$user['counts']['Followers']++;
			if ($user['counts']['Following'] == 0)
				$user['counts']['Following']++;
			if ($user['actionRate'] == 0)
				$user['actionRate']++;

			if ($user['counts']['Followers'] <= 1000) {
				if ($user['actionRate'] < 75) {
					//Ratio: how many actions * (media / Followers) or (Followers / media) * (Followers / Following) or (Following / Followers)
					$certainty = $user['actionRate'] * ($user['counts']['Media'] > $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Followers']));
					$certainty += $user['counts']['Following'] > $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Following']) : ($user['counts']['Following'] / $user['counts']['Followers']);
					$certainty *= $user['counts']['Following'] > $user['counts']['Media'] ? ($user['counts']['Following']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Following']);
				}
				else {
					//Ratio: We will give more emphassis to Followers
					$certainty = $user['actionRate'] * ($user['counts']['Media'] < $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Followers']));
					$certainty += $user['counts']['Following'] < $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Following']) : ($user['counts']['Following'] / $user['counts']['Followers']);
					$certainty *= $user['counts']['Following'] < $user['counts']['Media'] ? ($user['counts']['Following']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Following']);
				}
			}
			else if ($user['counts']['Followers'] > 1000 && $user['counts']['Followers'] <= 10000) {
				if ($user['actionRate'] < 350) {
					//Ratio: how many actions * (media / Followers) or (Followers / media) * (Followers / Following) or (Following / Followers)
					$certainty = $user['actionRate'] * ($user['counts']['Media'] > $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Followers']));
					$certainty += $user['counts']['Following'] > $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Following']) : ($user['counts']['Following'] / $user['counts']['Followers']);
					$certainty *= $user['counts']['Following'] > $user['counts']['Media'] ? ($user['counts']['Following']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Following']);
				}
				else {
					//Ratio: We will give more emphassis to Followers
					$certainty = $user['actionRate'] * ($user['counts']['Media'] < $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Followers']));
					$certainty += $user['counts']['Following'] < $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Following']) : ($user['counts']['Following'] / $user['counts']['Followers']);
					$certainty *= $user['counts']['Following'] < $user['counts']['Media'] ? ($user['counts']['Following']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Following']);
				}
			}
			else if ($user['counts']['Followers'] > 10000) {
				if ($user['actionRate'] < 650) {
					//Ratio: how many actions * (media / Followers) or (Followers / media) * (Followers / Following) or (Following / Followers)
					$certainty = $user['actionRate'] * ($user['counts']['Media'] > $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Followers']));
					$certainty += $user['counts']['Following'] > $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Following']) : ($user['counts']['Following'] / $user['counts']['Followers']);
					$certainty *= $user['counts']['Following'] > $user['counts']['Media'] ? ($user['counts']['Following']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Following']);
				}
				else {
					//Ratio: We will give more emphassis to Followers
					$certainty = $user['actionRate'] * ($user['counts']['Media'] < $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Followers']));
					$certainty += $user['counts']['Following'] < $user['counts']['Followers'] ? ($user['counts']['Followers']/$user['counts']['Following']) : ($user['counts']['Following'] / $user['counts']['Followers']);
					$certainty *= $user['counts']['Following'] < $user['counts']['Media'] ? ($user['counts']['Following']/$user['counts']['Media']) : ($user['counts']['Media'] / $user['counts']['Following']);
				}
			}
		}
		$certainty -= ($certainty * number_format($analysisResults,2,'.',''));
		if ($certainty > 50) 
			$calculateResults['bot'] = true;
		else
			$certainty = 100 - $certainty;
		if ($certainty > 100) 
			$certainty = 100;
		$calculateResults['certainty'] = $certainty;
		if ($lastRun) {
			return array(
					'_id' 						=>	$user['_id'],
					'username' 					=>	$user['username'],
					'trackingResults'			=>	array(
							'isPrivate'					=>	$user['private'] ? 1 : 0,
							'isSuspicious'				=>	$analysedUser['isSuspicious'][0] ? 1 : 0,
							'isBot'						=>	$calculateResults['bot'] ? 1 : 0,
							'postVSfollowers'			=>	$analysedUser['postVSfollowers'][0],
							'followingVSfollowers'		=>	$analysedUser['followingVSfollowers'][0],
							'suspiciousName'			=>	$analysedUser['suspiciousName'][0],
							'emptyProfile'				=>	$analysedUser['emptyProfile'][0],
							'googleSRnumOfLinks'		=>	$analysedUser['googleSRnumOfLinks'][0],
							'googleSRnumOfPages'		=>	$analysedUser['googleSRnumOfPages'][0],
							'numOfUserTags'				=>	$analysedUser['numOfUserTags'][0],
							'numOfMedia'				=>	$user['counts']['Media'],
							'numOfFollowing'			=>	$user['counts']['Following'],
							'numOfFollowers'			=>	$user['counts']['Followers'],
							'certainty'					=>	$calculateResults['certainty'],
							'userId'					=>	$user['_id']
							)
				);
		}
		return $calculateResults;
	}

	function makeFirstAnalysis($analysedUser) {
		return $analysedUser['isSuspicious'][0] + $analysedUser['emptyProfile'][0] - $analysedUser['suspiciousName'][0] + (($analysedUser['googleSRnumOfLinks'][0] + $analysedUser['googleSRnumOfPages'][0])/2);

	}

?>