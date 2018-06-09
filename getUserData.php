<?php
	$maxUsers = 6000;
	$loop = 0;
	if (!empty($idols)){
		foreach ($idols as $idol) {
		    $idolId = $idol['_id'];
		    $idolFollowers = getIdols($idolId);
		    foreach ($idolFollowers as $follower) {
		    	try {
		    		collectData($ig, $follower,false,true);
		    	} catch (\Exception $e) {
				    echo 'Something went wrong: '.$e->getMessage()."\n";
				}
		    }			
		}
	}	

?>