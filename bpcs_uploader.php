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
==================Baidu PCS Uploader==================
usage : $argv[0] init
usage : $argv[0] upload|download [path_local] [path_remote]
usage : $argv[0] delete [path_remote]
usage : $argv[0] fetch [path_remote] [path_to_fetch]

EOF;
	if(!is_dir(CONFIG_DIR)){
		mkdir(CONFIG_DIR);
	}
	if(!is_file(CONFIG_DIR.'/config.lock') || $argv[1] == 'init'){
		//进行初始化
		echon('Now start the initiation. If you have configured the uploader , it will be overwirte. ');
		continueornot();
		du_init();
	}
	