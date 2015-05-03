<?php

set_error_handler(ErrorHandler);


if($_GET['error']){
	trigger_error($_GET['error']);
}

function ErrorHandler($errno, $errmsg, $filename, $linenum) {     
	if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {             
		$date = date('Y-m-d H:i:s (T)');             
		$f = fopen('errors.log', 'a');                 
		if (!empty($f)) {                     
			//$err  = "\r\n";             
			$err .= $date.time."  ";             
			//$err .= "  $errno\r\n";             
			$err .= "$errmsg\r\n";             
			//$err .= "  $filename\r\n";             
			//$err .= "  $linenum\r\n";             
			//$err .= "\r\n";             
			fwrite($f, $err);            
			fclose($f);    
			echo $err;                                
		}             
	}
}


?>