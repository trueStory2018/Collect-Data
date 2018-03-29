<?php
	$maxUsers = 6000;
	$loop = 0;
	$idols = getIdols($idolId);
	if (!empty($users)){
		foreach ($idols['followers'] as $user) {
			try {
			    $uid = $idol['_id'];
				collectData($uid,false,$debug);    
				
			} catch (\Exception $e) {
			    echo 'Something went wrong: '.$e->getMessage()."\n";
			}
		}
	}	

?>