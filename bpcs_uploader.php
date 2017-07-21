#!/usr/bin/php -d disable_functions -d safe_mode=Off
<?php
  /*
    百度PCS上传php脚本 by oott123
    http://best33.com
   */
error_reporting(E_ALL);
if(!isset($_SERVER)){
  die('This script cannot be executed within web browser.');
}
//设置项目
define('FILES_DIR',dirname(__FILE__).'/_bpcs_files_');	//设置目录，尾部不需要/
define('CONFIG_DIR',FILES_DIR.'/config');	//配置目录
//函数文件
include(FILES_DIR.'/common.inc.php');
include(FILES_DIR.'/core.php');
//欢迎信息


if(!is_dir(CONFIG_DIR)){
  mkdir(CONFIG_DIR);
}
if(!is_file(CONFIG_DIR.'/config.lock') || $argv[1] == 'init' || $argv[1] == 'quickinit'){
  //进行初始化
  echon('Uploader initialization will be begin. If you have already configured the uploader before, your old settings will be overwritten.');
  continueornot();
  du_init($argv[1] == 'quickinit');
  file_put_contents(CONFIG_DIR.'/config.lock',time());
  die();
}
$access_token = file_get_contents(CONFIG_DIR.'/access_token');
$refresh_token = file_get_contents(CONFIG_DIR.'/refresh_token');
if($refresh_token){
  //若存在refresh token，则刷新它。
  $token_array=do_oauth_refresh(file_get_contents(CONFIG_DIR.'/appkey') , file_get_contents(CONFIG_DIR.'/appsec') , file_get_contents(CONFIG_DIR.'/refresh_token'));
  if($token_array['access_token'] && $token_array['refresh_token']){
    //防止获取不到token而自杀的行为
    $access_token = $token_array['access_token'];
    $refresh_token = $token_array['refresh_token'];
    file_put_contents(CONFIG_DIR.'/access_token',$access_token);
    file_put_contents(CONFIG_DIR.'/refresh_token',$refresh_token);
  }
}

switch($argv[1]){
case 'quota':
  //quota - 获取空间
  $quota = get_quota($access_token);
  $u=$quota['used']/1024/1024/1024;$a=$quota['quota']/1024/1024/1024;
  echon(sprintf("Your Storage Status: %.2fG/%.2fG (%.2f%%)",$u,$a,$u/$a*100));
  break;
case 'upload':
  //upload - 上传文件
  if(count($argv)<3){
    echon("Missing parameters. Please check again.");
    die();
  }
  $res=upload_file($access_token,$argv[3],$argv[2]);
  echon(sprintf("File %s uploaded.\nSize:%.3fK MD5 Sum:%s",$res['path'],$res['size']/1024,$res['md5']));
  break;
case 'download':
  //download - 下载文件
  if(count($argv)<3){
    echon("Missing parameters. Please check again.");
    die();
  }
  if(substr($argv[2],-1)=="/"){
     $argv2=$argv[2];
  }else{
     $argv2=$srgv[2]."/";//使path-local的第一个字符为"/"
  }
  if(substr($argv[3],0,1)=="/"){
     $argv[3]=substr($argv[3],1);//如果path-remote的第一个字符为"/",删掉它
  }else{
     $argv[3]=$argv[3];
  }
  $path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$argv[3]);

  if($argv[4]==NULL){//$argv[4]是远程文件的MD5
    $cmd = 'wget -t0 -c --no-check-certificate -O "'.$argv[2].'" "https://d.pcs.baidu.com/rest/2.0/pcs/file?method=download&access_token='.$access_token.'&path='.$path.'"';     
    cmd($cmd);
  }else{
    while(md5check($argv[2],$argv[4])==false){
      $cmd = 'wget -c -t0 --no-check-certificate -O "'.$argv[2].'" "https://d.pcs.baidu.com/rest/2.0/pcs/file?method=download&access_token='.$access_token.'&path='.$path.'"';     
      cmd($cmd);
    }
  }
  break;
case 'dirdown':
  //folder - 递归下载文件
  if(count($argv)<3){
     echon("Missing parameters. Please check again.");
     die();
    }
  if(substr($argv[2],-1)=="/"){
     $argv2=$argv[2];
  }else{
     $argv2=$srgv[2]."/";//使path-local的第一个字符为"/"
  }
  if(substr($argv[3],0,1)=="/"){
     $argv[3]=substr($argv[3],1);//如果path-remote的第一个字符为"/",删掉它
  }else{
     $argv[3]=$argv[3];
  }
  decode($argv,$access_token);
  break;
case 'delete':
  //delete - 删除文件
  if(count($argv)<2){
    echon("Missing parameters. Please check again.");
    die();
  }
  delete_file($access_token,$argv[2]);
  echon('File deleted.');
  break;
case 'fetch':
  //fetch - 离线下载
  //好像需要一定的权限，无法使用。
  if(count($argv)<3){
    echon("Missing parameters. Please check again.");
    die();
  }
  fetch_file($access_token,$argv[2],$argv[3]);
  break;
case 'uploadbig':
  //uploadbig - 大文件上传
  switch(count($argv)){
  case 0:
  case 1:
  case 2:
  case 3:	//参数数目不够
    echon('Missing parameters. Please check again.');
    die(9099);
  case 4:	//设置默认值（单个文件大小->1G）
    $argv[4] = 1073741824;
    //因为需要继续下面的操作所以这里没有break
  case 5:	//设置默认值（临时文件目录->/tmp/）
    $argv[5] = '/tmp/';
    //因为需要继续下面的操作所以这里没有break
  default:	//开始上传操作
    super_file($access_token,$argv[3],$argv[2],'newcopy',$argv[4],$argv[5]);
  }
default:
echo <<<EOF
===========================Baidu PCS Uploader===========================
Usage: $argv[0] init|quickinit|quota
Usage: $argv[0] upload|download path_local path_remote <md5>(optional)
		NOTE:
		1.Do not inter "/app/<appname>". e.g:if path_remote is
                "/app/bpcs_uploader/1.txt",just use "/1.txt".
		2.If you know remote file's MD5(e.g
		"/app/bpcs_uploader/1.txt"'s MD5 ),you can inter it after
		the path_remote phrase,the app will check while
		downloading.(only use in downoading)
Usage: $argv[0] dirdown dir_loacl dir_remote
		NOTE:
		the app will copy all the file in remote to local.
		e.g:If there are
		/app/bpcs_uploader/a/1.txt,/app/bpcs_uploader/a/b/2.txt,
		/app/bpcs_uploader/a/c/3.txt
		and you use "folderdown /home /a",you will find /home/a/1.txt
		/home/a/b/2.txt,/home/a/c/3.txt.(in another word,the structure
		of the directory and all the files will be copied.) 
Usage: $argv[0] delete path_remote
Usage: $argv[0] uploadbig path_local path_remote [slice_size(default:1073741824)] [temp_dir(def:/tmp/)]
Usage: $argv[0] fetch path_remote path_to_fetch
========================================================================

EOF;
break;
}

function decode($argv,$access_token){
  echon("--".date("Y-m-d")." ".date("h:i:sa")."-- start downloading dir \n",false,0);//显示开始下载
  //获取网盘文件列表
  $path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$argv[3]);
  $url= "https://pcs.baidu.com/rest/2.0/pcs/file";
  $para="method=list&access_token=".$access_token."&path=".$path;
  $output = do_api($url,$para,GET);
  $decode_result=json_decode($output); //解码json
  foreach ($decode_result->list as $i){
    if ($i->isdir==0/*如果是文件的话*/){
      $repeat=str_replace('/apps/'.file_get_contents(CONFIG_DIR.'/appname').'/',"",$i->path);//挑出网盘文件地址中去掉“/app/bpcs_uploader/”的部分
      echon("--".date("Y-m-d")." ".date("h:i:sa")."-- start downloading: ".$argv[2].$repeat."\n",false,0);//显示开始下载
      $cmd=$argv[0].' download "'.$argv[2].$repeat.'" "'.$repeat.'" '.$i->md5; //递归调用此脚本的下载方法
      cmd($cmd,true);
      echon("--".date("Y-m-d")." ".date("h:i:sa")."-- downloading finished: ".$argv[2].$repeat."\n",false,0);
    }else/*是目录的话*/{
      $repeat=str_replace('/apps/'.file_get_contents(CONFIG_DIR.'/appname').'/',"",$i->path);//挑出网盘文件地址中去掉“/app/bpcs_uploader/”的部分
      cmd('mkdir -p '.'"'.$argv[2].$repeat.'"');
      $argv3=str_replace('/apps/'.file_get_contents(CONFIG_DIR.'/appname').'/',"",$i->path);
      decode(Array($argv[0],$argv[1],$argv[2],$argv3),$access_token);//递归获取下一层目录
    }
  }
}

function md5check($argv2,$argv4){
  if(is_file($argv2)){
    $a='md5sum "'.$argv2.'"';
    cmd($a,true);
    if($r==$argv4){
      echon("--".date("Y-m-d")." ".date("h:i:sa")."-- local file's MD5 is ".$r.",the remote flie's MD5 is ".$argv4.",they are equal,so no need to download.",false,32);
      return TRUE;
    }else{
      echon(chr(34)."--".date("Y-m-d")." ".date("h:i:sa")."-- local file's MD5 is ".$r.",the remote flie's MD5 is ".$argv4.",they are NOT equal.Need to download.",false,31);
      return FALSE;
    }
  }else{
    echon("--".date("Y-m-d")." ".date("h:i:sa")."-- local file does not exist.Need to download.",false,31);
    return FALSE;
  }
}
