bpcs_uploader
=============

百度pcs上传脚本

## 系统要求

Linux (or cygwin) with php & curl installed.

## 使用方法

`$ chmod +x bpcs_uploader.php  
 $ .bpcs_uploader.php`  

由于VPS上安装的php可能存在于各种地方，因此运行很可能不正常。请以使用`which php`得到你的php绝对路径，修改bpcs_uploader.php的头一句#!后的路径。  
如果你的php是为了网站环境安装的，那么很有可能你会得到下面这条错误消息：  

> xxx() has been disabled for security reasons  

那么说明你的环境由于安全原因禁止了部分函数的执行。请看FAQs的1。请使用那条长长的命令代替./bpcs_uploader.php。eg：`php -d disable_functions -d safe_mode=Off -f bpcs_uploader.php quota`  

### 初始化
`./bpcs_uploader.php init`  

敲下命令之后会进入初始化流程，这里分段详述设置方法。

> Now start the initiation. If you have configured the uploader , it will be overwirte.   
> Continue? [y/N] y

确认初始化。如果之前有初始化过，那么以前的配置将会被覆盖。 
 
> Now you have to enter your baidu PSC app key . You should know that it needs a manual acting.  
> You can request for it via http://developer.baidu.com/dev#/create .  
> Make sure you have the PCS app key . if you haven\'t , you can use the demo key from Baidu.  
> It will exprire some time. who knows ? so the best way is to request for your own key.  
> There are a demo key from Baidu : L6g70tBRRIXLsY0Z3HwKqlRE
> So if you dont have the app secret , you have to re-init every month , for the access-token will expires every month.  
> App KEY [L6g70tBRRIXLsY0Z3HwKqlRE] :  

第一步，输入App key。这里需要输入一个有PCS权限的API KEY，如果没有的话直接敲回车就好了，这里会默认使用百度提供的一个demo API KEY。不过，由于百度没有公开app secret，所以只能获取一个有效期为一个月的access token。如果有一个有PSC权限的API KEY和secret，那么就能获得一个有效期为10年的refresh token，以便长期使用。

> App key has been setted to L6g70tBRRIXLsY0Z3HwKqlRE .  
> Now you have to enter your baidu PSC app secret. If you dont know the secret , keep it blank.  
> App SECRET [] :  

第二步，输入App secret。如果输入了app secret，将会转到device code模式验证；或者直接输入回车使用oob模式验证。先直接回车：

> Now you have to enter your app name. You can enter it later in the file [ /root/_bpcs_files_/config/appname ].  
> * Why i have to enter app name ? see FAQs.  
> If your app name have Chinese characters , please swith your client to the UTF-8 mode.  
> Here are some chinese characters . Before you enter chinese characters , make sure you can read these characters.  
> 如果你看到这里，说明你可以直接输入文字了。  
> If you cant read any chinese above , please press enter , and change it manually in the file [ /root/_bpcs_files_/config/appname ] .  
> If you have Enter the key [L6g70tBRRIXLsY0Z3HwKqlRE] (by default) , just press Enter.  
> App Name [pcstest_oauth] :   

第三步，这里需要输入app name。详情见FAQ 2。因为是使用的默认的key，所以直接回车即可。

> Now you have to get your oauth access_token by your own .  
> Here is a reference document .  
> http://developer.baidu.com/wiki/index.php?title=docs/pcs/guide/usage_example  
>   
> A simple guide :   
> 1.visit https://openapi.baidu.com/oauth/2.0/authorize?response_type=token&client_id=L6g70tBRRIXLsY0Z3HwKqlRE&redirect_uri=oob&scope=netdisk  
> in your broswer.  
> 2.when it redirected to a html page , copy the url to the notepad.  
> 3.get the access_token from it , paste it and press Enter.  
> access_token[] :   

第四步，获取access token。在浏览器中打开上述URL（ https://openapi.baidu.com/oauth/2.0/authorize?response_type=token&client_id=L6g70tBRRIXLsY0Z3HwKqlRE&redirect_uri=oob&scope=netdisk ），进行授权。  
授权完毕后，将会跳到一个写着“百度 Oauth2.0”的页面。复制出其中的网页URL，找到access_token=和&之间的字符串，例如：
`3.**05c2ea85d52c2***************a5.2592000.136***9032.3089166538-23**47`  
将其复制到shell中粘贴并回车。使用这种方式初始化的用户，需要每月重新初始化。  

如果第三步输入app secret的时候没有留空，将会得到下面的消息：

> Now open your broswer and visit https://openapi.baidu.com/device .   
> Copy or input 12abcxyz when it been asks.  
> After granted the access to the application , be back and press Enter key .  

来到这里，打开浏览器访问 https://openapi.baidu.com/device ，在“请输入设备上显示的用户授权码：”文本框中输入上面显示的授权码（这里是`12abcxyz`），并点击继续。
看到网页上显示“请返回设备继续操作！”后，返回ssh上按下回车后，即可继续。  

> curl -X GET -k -L "...."  
>   % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
>                                  Dload  Upload   Total   Spent    Left  Speed  
>   0    62    0    62    0     0     40      0 --:--:--  0:00:01 --:--:--   235  
> Success . Your Storage Status : 0.06G/115.00G (0.05%)  
> Have fun !  

你所看到的输出可能和这里给出的不一样，但是只要看到了存储空间的剩余量，和【Have fun !】提示，即说明成功初始化。

### 查询容量（配额）
`./bpcs_uploader.php quota`  

结果：  
> Your Storage Status : 0.06G/115.00G (0.05%)

### 上传文件
`./bpcs_uploader.php upload [path_local] [path_remote]`  
路径格式：`foo/bar/file.ext`（路径中一定要包括文件名）  
上传后，能在百度网盘/我的应用数据/应用名/foo/bar下找到一个叫file.ext的文件。

### 下载文件
`./bpcs_uploader.php download [path_local] [path_remote]` 

### 删除文件
`./bpcs_uploader.php delete [path_remote]` 

### 离线下载
`./bpcs_uploader.php fetch [path_remote] [path_to_fetch]`  
注：离线下载暂时无法在一般的api key授权的情况下使用，需要另外申请开通。

## FAQs
1. 各种错误提示  
试试`php -d disable_functions -d safe_mode=Off -f bpcs_uploader.php`。  
2. 为什么要输入app name？  
因为百度PCS的权限被限制在了/apps/appname/下。如果发现输入app name后仍然无法上传文件，请通过网页版找到【我的应用数据】找到对应的文件夹名，写入/config/appname文件。上传文件的时候会自动帮您处理文件夹，无需手动写出完整路径。