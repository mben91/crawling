<meta charset="UTF-8" >
<style> body {font-family: Arial, sans-serif; font-weight: normal; color: #333 } </style>
<script src="./jquery.js"></script>
<center>
	<div> All Possible Cases   : <span id="possiblecases">0</span></div>
	<div> Current Case         : <span id="currentcase">0</span></div>
	<div> Not Compatible Cases : <span id="notcompatible">0</span></div>
	<div> Found Cases          : <span id="compatible">0</span></div>
</center>
<?php
	
	error_reporting(1);
	
	$fields = array(
		'categoryId'  => $_GET['product']
	);
	
	$test = call('get-selector-dependencies',  'POST', $fields);
	$dependencies = $test['result'];
	
	if($test['code'] != '200') die('not found');
	
	$category = json_decode($test['result'], true);
	
	$blocks  =  array_keys($category['blocks']);
	
	$data = array();
	foreach ($category['blocks'] as $b => $block) {
		$data[] = array_keys($category['blocks'][$b]['bricks']);
	}

	$possibilities = allPossibleCases($data);
	
	$finalfields = array();
	foreach ($possibilities as $p => $possibility) {
		$val = explode('|', $possibility);
		$row = array();
		$row['categoryId'] = $fields['categoryId'];
		foreach ($val as $c => $cc) {
			 $row['bricks[' .$blocks[$c]. ']'] = $cc;
		}
		$finalfields[] = $row;
	}
	//print_r($finalfields);die;
		
	$count = 1;
	$pricetable = array(); 
	
	?>
	
	<script>
	var alldata = {};
	var compatiblecount = 0;
	var notcompatiblecount = 0;
	
	var compatiblefields = [];
	var xhr;
	
	$('#possiblecases').text(<?php echo count($possibilities) ?>);
	$(document).ready(function() {
		
		var finalfields = <?php echo json_encode($finalfields) ?>;
		$.each(finalfields, function(ff, finalfield) {
		
			<?php if(isset($_GET['min']) && !empty($_GET['min']) && is_numeric($_GET['min'])) : ?>
			if( ff < <?php echo $_GET['min'] ?> )
			return true; 
			<?php endif; ?>
			
			<?php if(isset($_GET['max']) && !empty($_GET['max']) && is_numeric($_GET['max'])) : ?>
			if( ff >= <?php echo $_GET['max'] ?> )
			return true; 
			<?php endif; ?>
			
			var prices;
			var fields;
			var deps;
			var specs = {};
			
			$('#currentcase').text(ff + 1);
			
			xhr = $.ajax({
				url: 'getprices.php',
				method: 'post',
				data: { field : JSON.stringify( finalfield ) },
				async: false,
				success: function(resp) {
					if(resp == 'notfound' || resp.indexOf('notfound') != -1) {
						notcompatiblecount++;
						$('#notcompatible').text(notcompatiblecount);
						return false;
					} else {
						compatiblefields.push(finalfield);
						compatiblecount++;
					}
					
					resp = JSON.parse(resp);
					
					prices    = JSON.parse(resp.prices.result);
					fields    = resp.fields;
					deps      = <?php echo $dependencies ?>;
					
					var options = [];
					$.each($(prices.data).find('.js-price-table tr'), function(i, tr) {
						if(isUndefined(options, i, 0 )) options[i] = [];
						$.each($(tr).find('td'), function(j, td) {
							options[i].push($(td).text().replace(/(\r\n|\n|\r)/gm,"").trim().replace(/\s+/g,' '));
						})
					});
					
					$.each(deps.blocks, function(d, dep) {
						$.each(fields, function(f, field) {
							if(f.replace('bricks[', '').replace(']', '') == d && f != 'categoryId') {
								$.each(dep.bricks, function(a, b) {
									if(a == field) {
										if(typeof dep.messages.description !== 'undefined')
										specs[dep.messages.description.value] = b.messages.description.value;
										else 
										specs[dep.messages.title.value] = b.messages.description.value;
									}						
								});	
							}
						});
					});
					
					var theads = [];
					$.each($(prices.data).find('.Panel[id^=js-price-table]'), function(t, th) {
						$.each($(th).find('.h.h4'), function() {
							theads.push($(this).text());	
						});
					});
					
					theads = $.grep(theads, function(v, k){
					    return $.inArray(v ,theads) === k;
					});
					
					theads = [ "Tirage", "Livraison standard", "Livraison sous 24h", "Livraison rapide" ];
					
					var priceoptions = {};
					var count = 0;
					
					$.each(options, function(o, option) {
						if(option.length != 0 && option[1] != '') {
							
							var priceoption = {};
							if(option.length > 3) {
								priceoption[theads[0]] = option[0];
								priceoption[theads[1]] = option[1];
								priceoption[theads[3]] = (typeof option[2] !== 'undefined' ? option[2] : '' );
								priceoption[theads[2]] = (typeof option[3] !== 'undefined' ? option[3] : '' );
							} else {
								$.each(theads, function(t, thead) {
									priceoption[thead] = (typeof option[t] !== 'undefined' ? option[t] : '' );
								});
							}
							priceoptions[count] = priceoption;
							count++;
						}
						
						
					});
					
					specs.prices = priceoptions;
					alldata[ff] = specs;
					
					$('#compatible').text(compatiblecount);
				}
					
			});
		});
	});		
				
	function isUndefined(arr, idx1, idx2) {
	    try { return arr[idx1][idx2] == undefined; } catch(e) { return true; }
	}

	function createCsv() {
		var csv = '', types = '', prices = '', head = '', headtypes = '', headprices = '';
		
		theads = [ "Tirage", "Livraison standard", "Livraison standard TVA comprise", "Livraison sous 24h", "Livraison sous 24h TVA Comprise", "Livraison rapide", "Livraison rapide TVA Comprise" ];
		
		$.each(alldata, function(ir, item) {
			$.each(item, function(ir2, item2) {
				if(ir2 != 'prices')
				headtypes += ir2 + ';';
			});
			return false;
		});
		
		head = theads.join(';') + ';' + headtypes.slice(0, -1) + '\n';
		
		$.each(alldata, function(ir, item) {
			types = '';
			$.each(item, function(pr, prod) {
				if(pr != 'prices') {
					types += prod + ';';
				} else if(pr == 'prices') {
					$.each(prod, function(p, price) {
						prices = '';
						var first = 0;
						$.each(price, function(e, elem) {
							if(first == 0) {
								prices += elem + ';';
								first = 1;
							} else {
								var pp = elem.split(" ");
								var p1 = (typeof pp[1] !== 'undefined' ? pp[1] : '' );
								var p2 = (typeof pp[5] !== 'undefined' ? pp[5] : '' );;
								
								prices += p1 + ';' + p2 + ';';
							}
						});
						csv += prices + types.slice(0, -1) + '\n';
					});
				}
			});
		});
		
		csv = "data:text/csv;charset=utf-8," + head + csv;
		
		var encodedUri = encodeURI(csv);
		var link = document.createElement("a");
		link.setAttribute("href", encodedUri);
		link.setAttribute("download", "my_data.csv");
		document.body.appendChild(link);
		
		link.click();
		
	}
	
	</script>

<?
	function allPossibleCases($arr) {
	  if (count($arr) == 1) {
	    return $arr[0];
	  } else {
	    $result = array();
	    $allCasesOfRest = allPossibleCases(array_slice($arr, 1));
	    for ($i = 0; $i < count($allCasesOfRest); $i++) {
	    	
	      for ($j = 0; $j < count($arr[0]); $j++) {
	        $result[] = $arr[0][$j] . "|" . $allCasesOfRest[$i];
	      }
		  
	    }
	    return $result;
	  }
	}
	
	function call($uri, $method, $data) {
        
		$baseurl = 'https://beta.printdeal.be/fr/sales-category/';
        
       	$headers = array();
        
        $handle = curl_init();
		
		$proxy = '66.76.178.125:8080';
		//curl_setopt($handle, CURLOPT_PROXY, $proxy);
        
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

<?php
/*
$.ajax({
	url: 'converttext.php',
	method: 'post',
	data: { text : head + csv },
	success: function(response, status, xhr) {
        // check for a filename
        var filename = "";
        var disposition = xhr.getResponseHeader('Content-Disposition');
        if (disposition && disposition.indexOf('attachment') !== -1) {
            var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            var matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
        }

        var type = xhr.getResponseHeader('Content-Type');
        var blob = new Blob([response], { type: type });

        if (typeof window.navigator.msSaveBlob !== 'undefined') {
            // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
            window.navigator.msSaveBlob(blob, filename);
        } else {
            var URL = window.URL || window.webkitURL;
            var downloadUrl = URL.createObjectURL(blob);

            if (filename) {
                // use HTML5 a[download] attribute to specify filename
                var a = document.createElement("a");
                // safari doesn't support this yet
                if (typeof a.download === 'undefined') {
                    window.location = downloadUrl;
                } else {
                    a.href = downloadUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                }
            } else {
                window.location = downloadUrl;
            }

            setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
        }
    },
	error: function() {
		
	}
})

return false;
*/ 
?>