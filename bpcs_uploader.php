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
echo <<<EOF
===========================Baidu PCS Uploader===========================
Usage: $argv[0] init|quickinit|quota
Usage: $argv[0] upload|download path_local path_remote
Usage: $argv[0] delete path_remote
Usage: $argv[0] uploadbig path_local path_remote [slice_size(default:1073741824)] [temp_dir(def:/tmp/)]
Usage: $argv[0] fetch path_remote path_to_fetch
========================================================================

EOF;
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
  $path='/apps/'.urlencode(file_get_contents(CONFIG_DIR.'/appname').'/'.$argv[3]);
  $cmd = 'wget -c --no-check-certificate -O "'.$argv[2].'" "https://d.pcs.baidu.com/rest/2.0/pcs/file?method=download&access_token='.$access_token.'&path='.$path.'"';
  cmd($cmd);
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
}
