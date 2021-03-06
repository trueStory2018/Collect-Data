<?php
	$idolNames = array($username);

	if (!empty($idolNames)) { 
		try{
			include_once "getIdol";
			print_r(expression)
			$url = 'https://api.mlab.com/api/1/databases/analysis/collections/idols?apiKey=tvG8BMjzxtNwm3fRgQv4LNbcF2IIeWWc';
			$data = array( '_id' => $resultArray['data'][0]['userID'],
							'lastModified' => microtime(true),
							'collected' => false,
							'profilePicture'	=>	$resultArray['data'][0]['profilePicture'],
							'fullName'			=>  $resultArray['data'][0]['fullName'],
							'userName'			=>  $username,
							'counts'			=>	$resultArray['data'][0]['counts'],
							'selfFollow'		=>	isset($_POST['selfFollow']) ? $_POST['selfFollow'] : false
						);
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json',
			    'Connection: Keep-Alive'
		    ));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
		}
		catch (Exception $e) {
			$resultArray['result'] = 1;
			$resultArray['resultText'] = substr($e->getMessage(),strpos($e->getMessage(), ' ')+1);
		}
	}
	else {
		$resultArray['result'] = -1;
		$resultArray['resultText'] = 'Empty UID';
	}

?>