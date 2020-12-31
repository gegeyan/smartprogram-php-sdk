# 百度智能小程序第三方平台php开发包

    smartprogram-php-sdk,封装了百度智能小程序的接口基本操作。
    包括授权，获取授权方的帐号基本信息，提包，提审，发布上线及涉及到的各种ticket,accesstoken 等票据的管理，票据管理使用了redis存储。

### 使用指南

使用前，请先查看及了解百度智能小程序第三方平台接口使用说明：https://smartprogram.baidu.com/docs/third/create/


附主流程参考示例代码

1.实例化
```
<?php
        use App\Lib\Smartprogram\BaiduService;
        

        $tpId = 1000000;//自己的第三方平台id;
        $tpKey= '第三方平台tpKey';
        $secretKey = '第三方平台secretKey';
        $aesKey = '第三方平台aesKey';
        $token = '第三方平台token';
        
        $service                    = new BaiduService($tpId,$tpKey,$secretKey, $aesKey, $token);
        
```
2.获取用户授权页 URL
  ```      
        $callbackUrl                = "http://xxx.com/xxx"; //回调URI
        $authorizeUrl               = $service->getAuthorizeUrl($callbackUrl); //传入回调URI即可
```
3.授权事件接收
   ```
        $service->onComponentAuthNotify();
        return $service->responseEvent();
```
        
4.授权回调的处理
```
    //$authorizationCode  来自百度官方传回的参数 authorization_code
    
    //$expiresIn  来自百度官方传回的参数expires_in 

     $infoArr           = $service->authorizeCallbackProcess($authorizationCode, $expiresIn);
     if ($infoArr['code'] == 0) {
            $appArr      = $infoArr['appAccountInfo']; //授权小程序的基本信息
            //... 
            
      } else {
            $error = $infoArr['msg'];
        }
```
5.提包提审
```
        /*
        appId 为小程序id, 
        templateId 为模板id,
        version 为 版本号，
        desc为版本描述
        */
        //为小程序上传代码包
        
        $extJson    = [
            "extEnable"    => true,
            "extAppid"     => $appId,
            "directCommit" => false,
            "ext"          => [
                "appid"  => $appId,
            ],
        ];
        $infoArr = $service->uploadPackage($appId, $templateId, json_encode($extJson), $version, $desc);
       
        if ($infoArr['errno'] == 0) {
            sleep(3);
            $packArr = $service->getPackageList($appId);
            if ($packArr['errno'] == 0) {
                $packListArr = $packArr['data'];
                $packageId   = 0;
                foreach ($packListArr as $key => $value) {
                    if ($value['status'] == 3) {
                        $packageId   = $value['package_id'];
                        $version     = $value['version'];
                        $templateId  = $value['template_id'];
                        $versionDesc = $value['version_desc'];
                    }
                }
                if ($packageId) {
                    //送审前给小程序设置服务器域名等
                    $argsArr           = [
                        'request_domain'  => 'https://xxx.com',
                        'socket_domain'   => '',
                        'upload_domain'   => '',
                        'download_domain' => '',
                    ];
                    $argsArr['action'] = 'add';
                    $service->modifyDomain($appId, $argsArr);
                    
                    //送审
                    $toAuditStr = '送审描述';
                    $remark     = '备注';
                    $rsArr      = $service->packageSubmitAudit($appId, $packageId, $toAuditStr, $remark);
                    if (isset($rsArr['errno']) && $rsArr['errno'] == 0) {
                        //送审成功了
                    }

                }
            }
        }
        
```

        
6. 消息与事件接收(含发布上线)

```
        $ret     = $service->onAuditNotify();
        if ($ret) {
            $appId = $ret['appId']; //小程序ID
            
            $packageId = xxxxx; //该小程序提包的包id,提包后自行记录下来，发布的时候会用到
              
                
                if ($ret['event'] == 'PACKAGE_AUDIT_PASS') {
                //...
                } elseif ($ret['event'] == 'PACKAGE_AUDIT_FAIL') {
                //...          
                }
                
                //收到官方推送的审核成功了，发布上线该小程序
                if ($ret['event'] == 'PACKAGE_AUDIT_PASS') {
                    $rsArr = $service->packageRelease($appId, $packageId);
                }
            

        }
        return $service->responseEvent();
        
