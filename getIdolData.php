<?php
	$idol = true;
	$idols = getIdols();
	if (!empty($idols)){
		foreach ($idols as $idol) {
			try {
			    $uid = $idol['_id'];
				collectData($uid,$idol,$debug);    
				
			} catch (\Exception $e) {
			    echo 'Something went wrong: '.$e->getMessage()."\n";
			}
		}
	}
?>