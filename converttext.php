<?php 

	$text = $_POST['text'];
	//print $text;die;
	toCSV($text, 'save');
	die();
	
	 /**
	   * Export an array as downladable Excel CSV
	   * @param array   $header
	   * @param array   $data
	   * @param string  $filename
	   */
	  function toCSV($csv, $filename) {
	    $encoded_csv = mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');
	    header('Content-Description: File Transfer');
	    header('Content-Type: application/vnd.ms-excel');
	    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
	    header('Content-Transfer-Encoding: binary');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	    header('Pragma: public');
	    header('Content-Length: '. strlen($encoded_csv));
	    echo chr(255) . chr(254) . "sep=,\n" . mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');;
	    exit;
	  }
?>