<?php
	//核心操作文件
	function du_init(){
		echo <<<EOF
Now you have to enter your baidu PSC app key . You should know that it needs a manual acting.
You can request for it via http://developer.baidu.com/dev#/create .
Make sure you have the PCS app key . if you haven\'t , you can use the demo key from Baidu.
It will exprire some time. who knows ? so the best way is to request for your own key.
There are a demo key from Baidu : L6g70tBRRIXLsY0Z3HwKqlRE
So if you dont have the app secret , you have to re-init every month , for the access-token will expires every month.
* Now the script cannot work if you dont know the app secret , but it will works in the next versions.

EOF;
		echo 'App KEY [L6g70tBRRIXLsY0Z3HwKqlRE] :';
		$appkey = getline();
		$appkey = ($appkey) ? $appkey : 'L6g70tBRRIXLsY0Z3HwKqlRE';
		file_put_contents(CONFIG_DIR.'/appkey',$appkey);
		echon('App key has been setted to '.$appkey.' . ');

		echo <<<EOF
Now you have to enter your baidu PSC app secret. If you dont know the secret , keep it blank.

EOF;
		echo 'App SECRET [] :';
		$appsec = getline();
		file_put_contents(CONFIG_DIR.'/appsec',$appsec);
		$prepathfile = CONFIG_DIR.'/appname';
		echo <<<EOF
Now you have to enter your app name. You can enter it later in the file [ $prepathfile ].
* Why i have to enter app name ? see FAQs.
If your app name have Chinese characters , please swith your client to the UTF-8 mode.
Here are some chinese characters . Before you enter chinese characters , make sure you can read these characters.
如果你看到这里，说明你可以直接输入文字了。
If you cant read any chinese above , please press enter , and change it manually in the file [ $prepathfile ] .
If you have Enter the key [L6g70tBRRIXLsY0Z3HwKqlRE] (by default) , just press Enter.

EOF;
		echo 'App Name [pcstest_oauth] : ';
		$appname = getline();
		$appname = ($appname) ? $appname : 'pcstest_oauth';
		file_put_contents(CONFIG_DIR.'/appname',$appname);
		
		
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
	function get_quota($access_token){
		$quota=do_api('https://pcs.baidu.com/rest/2.0/pcs/quota',"method=info&access_token=".$access_token,'GET');
		$quota=json_decode($quota,1);
		oaerr($quota);
		return $quota;
	}
	function upload_file($access_token,$path,$localfile,$ondup='newcopy'){
		$path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$path);
		$url = "https://pcs.baidu.com/rest/2.0/pcs/file?method=upload&access_token=$access_token&path=$path&ondup=$ondup";
		$add = "--form file=@$localfile";
		$cmd = "curl -X POST -k -L $add \"$url\"";
		$cmd = cmd($cmd);
		$cmd = json_decode($cmd,1);
		oaerr($cmd);
		return $cmd;
	}