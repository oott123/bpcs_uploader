#!/usr/bin/env php
<?php
	/*
		百度PCS上传php脚本 by oott123
		via http://best33.com
		说明：
			1.本脚本仅可用于命令行环境，请勿用于网页环境
			2.需要的运行环境（系统安装）：curl，php5.2或以上版本
			3.Linux only，不能用于win环境
	*/
	error_reporting(E_ALL);
	//设置项目
	define('FILES_DIR',dirname(__FIle__).'/_bpcs_files_');	//设置目录，尾部不需要/
	define('CONFIG_DIR',FILES_DIR.'/config');	//配置目录
	//fwrite(STDERR, "stderr\n");
	
	if(!is_dir(CONFIG_DIR)){
		//目录不存在，进行初始化
		
	}