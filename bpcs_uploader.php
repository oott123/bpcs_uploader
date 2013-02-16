#!/usr/bin/env php
<?php
	/*
		百度PCS上传php脚本 by oott123
		http://best33.com
	*/
	error_reporting(E_ALL);
	if(!isset($_SERVER)){
		die('this script can\'t run from the broswer.');
	}
	//设置项目
	define('FILES_DIR',dirname(__FIle__).'/_bpcs_files_');	//设置目录，尾部不需要/
	define('CONFIG_DIR',FILES_DIR.'/config');	//配置目录
	//函数文件
	include(FILES_DIR.'/common.inc.php');
	include(FILES_DIR.'/core.php');
	//欢迎信息
	echo <<<EOF
===========================Baidu PCS Uploader===========================
usage : $argv[0] init|quota
usage : $argv[0] upload|download [path_local] [path_remote]
usage : $argv[0] delete [path_remote]
usage : $argv[0] fetch [path_remote] [path_to_fetch]
========================================================================

EOF;
	if(!is_dir(CONFIG_DIR)){
		mkdir(CONFIG_DIR);
	}
	if(!is_file(CONFIG_DIR.'/config.lock') || $argv[1] == 'init'){
		//进行初始化
		echon('Now start the initiation. If you have configured the uploader , it will be overwirte. ');
		continueornot();
		du_init();
		file_put_contents(CONFIG_DIR.'/config.lock',time());
		die();
	}
	
	$access_token = file_get_contents(CONFIG_DIR.'/access_token');
	$refresh_token = file_get_contents(CONFIG_DIR.'/refresh_token');
	
	switch($argv[1]){
		case 'quota':
			//quota - 获取空间
			$quota = get_quota($access_token);
			$u=$quota['used']/1024/1024/1024;$a=$quota['quota']/1024/1024/1024;
			echon(sprintf("Your Storage Status : %.2fG/%.2fG (%.2f%%)",$u,$a,$u/$a*100));
		break;
		case 'upload':
			//upload - 上传文件
			if(count($argv)<3){
				echon("Parameters unmatched.");
				die();
			}
			$res=upload_file($access_token,$argv[3],$argv[2]);
			echon(sprintf("File %s uploaded.\nSize:%.3fK MD5 Sum:%s",$res['path'],$res['size']/1024,$res['md5']));
		break;
		case 'download':
			//download - 下载文件
			if(count($argv)<3){
				echon("Parameters unmatched.");
				die();
			}
			$path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$argv[3]);
			$cmd = 'wget --no-check-certificate -O "'.$argv[2].' " "https://pcs.baidu.com/rest/2.0/pcs/file?method=download&access_token='.$access_token.'&path='.$path.'"';
			cmd($cmd);
		break;
		case 'delete':
			//delete - 删除文件
			if(count($argv)<2){
				echon("Parameters unmatched.");
				die();
			}
			delete_file($access_token,$argv[2]);
			echon('Deleted.');
		break;
		case 'fetch':
			//fetch - 离线下载
			
	}