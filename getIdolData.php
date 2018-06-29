<?php
	$idols = getIdols();
	if (!empty($idols)){
		foreach ($idols as $idolToCollect) {
			$idol = true;
			try {
			    $uid = $idolToCollect['_id'];
			    //If we are not analyzing this idol as an idol but as a user
				if ($idolToCollect['selfFollow']) {
					$idol = false;
					pushData('idols',array($uid),$uid,'followers');
				}
				collectData($ig,$uid,$idol,$innerDebug);
				pushData('idols',true,$uid,'collected');
				
			} catch (\Exception $e) {
			    echo 'Something went wrong: '.$e->getMessage()."\n";
			}
		}
	}
?>