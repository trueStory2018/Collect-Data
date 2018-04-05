<?php
	$idol = true;
	$idols = getIdols();
	if (!empty($idols)){
		foreach ($idols as $idol) {
			try {
			    $uid = $idol['_id'];
				
				collectData($ig,$uid,$idol,$innerDebug); 
				
			} catch (\Exception $e) {
			    echo 'Something went wrong: '.$e->getMessage()."\n";
			}
		}
	}
?>