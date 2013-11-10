<?php
	function echon($str,$debug=false){
		if($debug){
			//slient when the message is debug output
			//echo $str."\n";
		}else{
			echo chr(27)."[0;34m".$str.chr(27)."[m\n";
		}
	}
	function getline(){
		return trim(fgets(STDIN));
	}
	function getpath($path){
		return '/apps/'.rawurlencode(trim(file_get_contents(CONFIG_DIR.'/appname')).str_replace('/./', '/', '/'.$path));
	}
	function oaerr($arr,$exitonerror = 1){
		if(isset($arr['error'])){
			echon('OAuth error '.$arr['error'].' : '.$arr['error_description']);
			if($exitonerror){die();}
			return false;
		}
		return true;
	}
	function apierr($arr,$exitonerror = 1){
		if(isset($arr['error_code'])){
			echon('API calling error '.$arr['error_code'].' : '.$arr['error_msg']);
			if($exitonerror){die();}
			return false;
		}
		if(!isset($arr['request_id'])){
			echon('API calling faild.');
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
		echon($cfe,1);
		$cfe = $cfe;
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
		echon($res,1);
		return $res;
	}
	function do_api($url,$param,$method = 'POST'){
		if($method == 'POST'){
			$cmd = "curl -X POST -k -L --data \"$param\" \"$url\"";
		}else{
			$cmd = "curl -X $method -k -L \"$url?$param\"";
		}
		
		return cmd($cmd);
	}
	function error_handle($errno, $errstr, $errfile, $errline){
		switch ($errno) {
			case E_USER_ERROR:
				echon("Fatal ERROR : $errfile ($errline) $errstr");
				echon('Exiting with a fatal error.'."\n");
				die(9002);
			break;
			case E_USER_WARNING:
				echon("WARNING : $errfile ($errline) $errstr");
			break;
			case E_USER_NOTICE:
				echon("Notice : $errfile ($errline) $errstr");
			break;
			case 8:
			break;
			default:
				echon("err$errno : $errfile ($errline) $errstr");
			break;
		}
		return true;
	}
	set_error_handler("error_handle");
