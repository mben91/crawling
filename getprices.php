<?php 
	
	$finalfield = json_decode($_POST['field'], true);
	
	$pricetable = array(); 
	$prices = call('get-price-table', 'POST', $finalfield);
	
	if(strpos($prices['result'], 'PriceTable') != false) {
		$pricetable['prices'] = $prices;
		$pricetable['fields'] = $finalfield;
		
		echo json_encode($pricetable);
	} else {
		echo 'notfound';
	}
		
	function call($uri, $method, $data) {
        
		$baseurl = 'https://beta.printdeal.be/fr/sales-category/';
        
       	$headers = array();
        
        $handle = curl_init();
        
		$proxy = '54.187.225.70:8083';
		curl_setopt($handle, CURLOPT_PROXY, $proxy);
		
        curl_setopt($handle, CURLOPT_URL, $baseurl . $uri);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		
        switch($method) {
            case 'GET':
                $query = '?' . http_build_query($data);
                curl_setopt($handle, CURLOPT_URL, $baseurl . $uri . $query);
                break;
            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT': 
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		$contenttype = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        $message = '';
        
        if ($response === false) {
            $info = curl_getinfo($handle);
            curl_close($handle);
            $message = 'error occured during curl exec. Additioanl info: ' . var_export($info);
        }
        curl_close($handle);
        $decoded = json_decode($response);
        if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
            $message = 'error occured: ' . $decoded->response->errormessage;
        }
        
        return array(
            'result'       => $response,
            'code'         => $code,
            'message'      => $message,
            'contentType'  => $contenttype
        );
    }
		
?>
