<?php
	//核心操作文件
	function du_init($quickinit = false){
		define('BPCSU_KEY','uFBSHEwWE6DD94SQx9z77vgG');
		define('BPCSU_SEC','7w6wdSFsTk6Vv586r1W1ozHLoDGhXogD');
		define('BPCSU_FNAME','bpcs_uploader');
		if($quickinit){
			//快速初始化
			$appkey = BPCSU_KEY;
			file_put_contents(CONFIG_DIR.'/appkey',$appkey);
			$appsec = BPCSU_SEC;
			file_put_contents(CONFIG_DIR.'/appsec',$appsec);
			$appname = BPCSU_FNAME;
			file_put_contents(CONFIG_DIR.'/appname',$appname);
		}else{
			//正常初始化
			echo <<<EOF
Now you have to enter your baidu PSC app key . You should know that it needs a manual acting.
You can request for it via http://developer.baidu.com/dev#/create .
Make sure you have the PCS app key . if you haven\'t , you can use the demo key by just hit Enter.
So if you dont have the app secret , you have to re-init every month , for the access-token will expires every month.

EOF;
			echo 'App KEY ['.BPCSU_KEY.'] :';
			$appkey = getline();
			$appkey = ($appkey) ? $appkey : BPCSU_KEY;
			file_put_contents(CONFIG_DIR.'/appkey',$appkey);
			echon('App key has been setted to '.$appkey.' . ');
			if($appkey == BPCSU_KEY){
				echon('App secret have been setted by default.');
				$appsec=BPCSU_SEC;
			}else{
				echo <<<EOF
	Now you have to enter your baidu PSC app secret. If you dont know the secret , keep it blank.

	EOF;
				echo 'App SECRET [] :';
				$appsec = getline();
			}
			file_put_contents(CONFIG_DIR.'/appsec',$appsec);
			$prepathfile = CONFIG_DIR.'/appname';
			if($appkey == BPCSU_KEY){
				echon('App name has been setted by default.');
				$appname = 'bpcs_uploader';
			}else{
				echo <<<EOF
Now you have to enter your app floder name. You can enter it later in the file [ $prepathfile ].
* Why i have to enter app floder name ? see FAQs.
If your app name have Chinese characters , please swith your client to the UTF-8 mode.
Here are some chinese characters . Before you enter chinese characters , make sure you can read these characters.
这里是一些中文字符。
If you cant read any chinese above , please press enter , and change it manually in the file [ $prepathfile ] .

EOF;
				echo 'App Floder Name [] : ';
				$appname = getline();
			}
			file_put_contents(CONFIG_DIR.'/appname',$appname);
		}//end of 初始化配置
		
		if($appsec){
			$tokens=du_oauth_device($appkey,$appsec);
			$access_token = $tokens['access_token'];
			$refresh_token = $tokens['refresh_token'];
		}else{
			$access_token = do_oauth_token($appkey);
			$refresh_token = '';
		}
		file_put_contents(CONFIG_DIR.'/access_token',$access_token);
		file_put_contents(CONFIG_DIR.'/refresh_token',$refresh_token);
		
		$quota = get_quota($access_token);
		$u=$quota['used']/1024/1024/1024;$a=$quota['quota']/1024/1024/1024;
		echon(sprintf("Success . Your Storage Status : %.2fG/%.2fG (%.2f%%)",$u,$a,$u/$a*100));
		echon('Have fun !');
	}
	function du_oauth_device($appkey,$appsec){
		$device_para = 'client_id='.$appkey.'&response_type=device_code&scope=basic,netdisk';
		$device_json = do_api('https://openapi.baidu.com/oauth/2.0/device/code',$device_para);
		$device_array = json_decode($device_json,1);
		oaerr($device_array);
		
		echo <<<EOF
Now open your broswer and visit $device_array[verification_url] . 
Copy or input $device_array[user_code] when it been asks.
After granted the access to the application , be back and press Enter key .

EOF;
		getline();
		for(;;){
			//一个死循环
			$token_para='grant_type=device_token&code=' . $device_array['device_code'] . '&client_id=' . $appkey . '&client_secret=' . $appsec;
			$token_json = do_api('https://openapi.baidu.com/oauth/2.0/token',$token_para);
			$token_array = json_decode($token_json,1);
			if(oaerr($token_array,0)){
				break;
			}else{
				echon('Auth failed. please check the error message and try agian.');
				echo <<<EOF
Now open your broswer and visit $device_array[verification_url] . 
Copy or input $device_array[user_code] when it been asks.
After granted the access to the application , be back and press Y .

EOF;
				continueornot();
				continue;
			}
			break;
		}
		$access_token = $token_array['access_token'];
		$refresh_token = $token_array['refresh_token'];
		return array(
			'access_token' => $access_token,
			'refresh_token' => $refresh_token,
		);
	}
	function do_oauth_token($appkey){
		echo <<<EOF
Now you have to get your oauth access_token by your own .
Here is a reference document .
http://developer.baidu.com/wiki/index.php?title=docs/pcs/guide/usage_example

A simple guide : 
1.visit https://openapi.baidu.com/oauth/2.0/authorize?response_type=token&client_id=$appkey&redirect_uri=oob&scope=netdisk
in your broswer.
2.when it redirected to a html page , copy the url to the notepad.
3.get the access_token from it , paste it and press Enter.

EOF;
		echo 'access_token[] : ';
		$access_token = getline();
		return $access_token;
	}
	function do_oauth_refresh($appkey,$appsec,$refresh_token){
		$para = 'grant_type=refresh_token&refresh_token='.$refresh_token.'&client_id='.$appkey.'&client_secret='.$appsec;
		$token_json = do_api('https://openapi.baidu.com/oauth/2.0/token',$para);
		$token_array = json_decode($token_json,1);
		$access_token = $token_array['access_token'];
		$refresh_token = $token_array['refresh_token'];
		return array(
			'access_token' => $access_token,
			'refresh_token' => $refresh_token,
		);
	}
	function get_quota($access_token){
		$quota=do_api('https://pcs.baidu.com/rest/2.0/pcs/quota',"method=info&access_token=".$access_token,'GET');
		$quota=json_decode($quota,1);
		apierr($quota);
		return $quota;
	}
	function upload_file($access_token,$path,$localfile,$ondup='newcopy'){
		$path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$path);
		$url = "https://pcs.baidu.com/rest/2.0/pcs/file?method=upload&access_token=$access_token&path=$path&ondup=$ondup";
		$add = "--form file=@$localfile";
		$cmd = "curl -X POST -k -L $add \"$url\"";
		$cmd = cmd($cmd);
		$cmd = json_decode($cmd,1);
		apierr($cmd);
		return $cmd;
	}
	function delete_file($access_token,$path){
		$path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$path);
		$dele=do_api('https://pcs.baidu.com/rest/2.0/pcs/file',"method=delete&access_token=".$access_token.'&path='.$path,'GET');
		$dele=json_decode($dele,1);
		apierr($dele);
		return $dele;
	}
	function fetch_file($access_token,$path,$url){
		$path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$path);
		$fetch=do_api('https://pcs.baidu.com/rest/2.0/pcs/services/cloud_dl',"method=add_task&access_token=".$access_token.'&save_path='.$path.'&source_url='.$url,'GET');
		$fetch=json_decode($fetch,1);
		apierr($fetch);
		return $fetch;
	}