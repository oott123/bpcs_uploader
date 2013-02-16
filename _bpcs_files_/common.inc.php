<?php
	function echon($str){
		echo $str."\n";
	}
	function getline(){
		return trim(fgets(STDIN));
	}
	function oaerr($arr,$exitonerror = 1){
		if(isset($arr['error'])){
			echon('OAuth error '.$arr['error'].' : '.$arr['error_description']);
			if($exitonerror){die();}
			return false;
		}
		return true;
	}
	function continueornot(){
		echo 'Continue? [y/N] ';
		switch (getline()){
			case 'y':case 'Y':
			break;
			default:
				echon('Exiting ... ');
				die();
		}
	}
	function cmd($cfe) {
		$res = '';
		if ($cfe) {
			if(function_exists('exec')) {
				@exec($cfe,$res);
				$res = join("\n",$res);
			} elseif(function_exists('shell_exec')) {
				$res = @shell_exec($cfe);
			} elseif(function_exists('system')) {
				@ob_start();
				@system($cfe);
				$res = @ob_get_contents();
				@ob_end_clean();
			} elseif(function_exists('passthru')) {
				@ob_start();
				@passthru($cfe);
				$res = @ob_get_contents();
				@ob_end_clean();
			} elseif(@is_resource($f = @popen($cfe,"r"))) {
				$res = '';
				while(!@feof($f)) {
					$res .= @fread($f,1024); 
				}
				@pclose($f);
			}
		}
		return $res;
	}
	function error_handle($errno, $errstr, $errfile, $errline){
		switch ($errno) {
			case E_USER_ERROR:
				echon("Fatal ERROR : $errfile ($errline) $errstr");die('Exiting with a fatal error.'."\n");
			break;
			case E_USER_WARNING:
				echon("WARNING : $errfile ($errline) $errstr");
			break;
			case E_USER_NOTICE:
				echon("Notice : $errfile ($errline) $errstr");
			break;
			default:
				echon("err$errno : $errfile ($errline) $errstr");
			break;
		}
		return true;
	}
	set_error_handler("error_handle");