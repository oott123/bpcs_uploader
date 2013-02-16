<?php
	//核心操作文件
	function du_init(){
		echo <<<EOF
Now you have to enter your baidu PSC app key . You should know that it needs a manual acting.
You can request for it via http://developer.baidu.com/dev#/create .
Make sure you have the PCS app key . if you haven\'t , you can use the demo key from Baidu.
It will exprire some time. who knows ? so the best way is to request for your own key.
There are some demo keys from Baidu : BbekPBG0sgL4CDUWfBrF0mFv
So if you dont have the app secret , you have to re-init every month , for the access-token will expires every month.
* Now the script cannot work if you dont know the app secret , but it will works in the next versions.

EOF;
		echo 'App KEY [BbekPBG0sgL4CDUWfBrF0mFv] :';
		$appkey = getline();
		$appkey = ($appkey) ? $appkey : 'BbekPBG0sgL4CDUWfBrF0mFv';
		file_put_contents(CONFIG_DIR.'/appkey',$appkey);
		echon('App key has been setted to '.$appkey.' . ');

		echo <<<EOF
Now you have to enter your baidu PSC app secret. If you dont know the secret , keep it blank.

EOF;
		echo 'App SECRET [] :';
		$appsec = getline();
		file_put_contents(CONFIG_DIR.'/appsec',$appsec);
		
		if($appsec){
			$tokens=du_oauth_device($appkey,$appsec);
			$access_token = $tokens['access_token'];
			$refresh_token = $tokens['refresh_token'];
			file_put_contents(CONFIG_DIR.'/access_token',$access_token);
			file_put_contents(CONFIG_DIR.'/refresh_token',$refresh_token);
			
			echol('Token successfully granted. your access token is '.substr($access_token,0,8).'********* and your refresh token is '.substr($refresh_token,0,8).'********');
			echol('Have fun !');
			die();
		}else{
			echon('The script wont works if you dont know the app secret in this version.');die();
		}
		
	}
	function du_oauth_device($appkey,$appsec){
		$device_para = 'client_id='.$appkey.'&response_type=device_code&scope=basic,netdisk';
		$device_json = `curl -k -L --data "$device_para" 'https://openapi.baidu.com/oauth/2.0/device/code'`;
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
			$token_json = `curl -k -L --data "$token_para" 'https://openapi.baidu.com/oauth/2.0/token'`;
			$token_array = json_decode($token_json,1);
			if(oaerr($token_array,1)){
				break;
			}else{
				echon('Auth failed. please check the error message and try agian.');
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