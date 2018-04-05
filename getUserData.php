<?php
	$maxUsers = 6000;
	$loop = 0;
	if (!empty($idols)){
		foreach ($idols as $idol) {
			try {
			    $idolId = $idol['_id'];
			    $idolFollowers = getIdols($idolId);
			    foreach ($idolFollowers as $follower) {
			    	collectData($uid,false,$innerDebug);
			    }			
			} catch (\Exception $e) {
			    echo 'Something went wrong: '.$e->getMessage()."\n";
			}
		}
	}	

?>