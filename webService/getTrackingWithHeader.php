<?php
	$now = new DateTime();
	try {
		header('Content-type: text/csv');
		header('Content-Disposition: attachment; filename="TrackingResults '.$now->format('Y-m-d').'.csv"');
		// do not cache the file
		header('Pragma: no-cache');
		header('Expires: 0');
		$fp = fopen('php://output', 'w');
		$url = 'https://api.mlab.com/api/1/databases/analysis/collections/users?q={"trackingResults":{"$exists":true}}&apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc';
		
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
		$data = json_decode($response,true);
		if (!empty($data)) {
			fputcsv($fp, array_keys($data[0]['trackingResults']));
			foreach ($data as $row) {
				fputcsv($fp, $row['trackingResults']);
			}
		}
		fclose($fp);
	}
	catch (Exception $e) {
		$resultArray['result'] = -1;
		$resultArray['resultText'] = $error;
	}

?>