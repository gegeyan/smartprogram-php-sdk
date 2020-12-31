<?php
namespace App\Lib\Smartprogram;

/**
 * 百度小程序服务类
 * $spId 为百度智能小程序id
 */
class BaiduService
{

    protected $tpId; //第三方平台ID

    protected $tpKey; //第三方平台Key

    protected $secretKey; //密钥

    protected $aesKey;

    protected $token;

    //此处redis 主要是进行ticket等票据的管理
    protected $redisHost = '127.0.0.1';
    protected $redisPort = 6379;

    protected $cache;
    protected $spComponent;
   

    /**
     * [__construct description]
     * @param    [type]                   $tpId      [第三方平台ID]
     * @param    [type]                   $tpKey     [第三方平台Key]
     * @param    [type]                   $secretKey [密钥]
     * @param    [type]                   $aesKey    [description]
     * @param    [type]                   $token     [description]
     */
    public function __construct($tpId, $tpKey, $secretKey, $aesKey, $token)
    {
        $this->cache = new BaiduCache($this->redisHost, $this->redisPort);
        $this->tpId = $tpId;
        $this->tpKey  = $tpKey;
        $this->secretKey  = $secretKey;
        $this->aesKey  = $aesKey;
        $this->token  = $token;

    }

    public function getSpComponent()
    {
        $ticket = $this->getComponentVerifyTicket();
        if (!$this->spComponent) {
            $this->spComponent = new SmartProgramComponent($this->tpId, $this->tpKey, $this->secretKey, $this->aesKey, $this->token, $ticket);
        }
        return $this->spComponent;
    }

    public function getAuthorizeUrl($callbackUrl)
    {
        $redirect_uri = $callbackUrl;
        $preAuthCode  = $this->getPreAuthCode();
        return $this->getSpComponent()->get_auth_cb_url($preAuthCode, $redirect_uri);
    }

    protected function getPreAuthCode()
    {
        $authName    = "bdPreAuthCode" . $this->tpId;
        $preAuthCode = $this->cache->getCache($authName);
        if ($preAuthCode) {
            return $preAuthCode;
        }
        $accessToken    = $this->getAccessToken();
        $preAuthCodeArr = $this->getSpComponent()->get_pre_auth_code($accessToken);
        $this->cache->setCache($authName, $preAuthCodeArr['pre_auth_code'], $preAuthCodeArr['expires_in'] - 10);
        return $preAuthCodeArr['pre_auth_code'];
    }

    protected function getAccessToken()
    {
        $authName    = "bdAccessTocken" . $this->tpId;
        $accessToken = $this->cache->getCache($authName);
        if ($accessToken) {
            return $accessToken;
        }

        $accessArr = $this->getSpComponent()->get_access_token();
        $this->cache->setCache($authName, $accessArr['access_token'], $accessArr['expires_in'] - 10);
        return $accessArr['access_token'];
    }

    /**
     * 得到授权小程序的接口调用凭据
     * @param $code 授权码
     * @return bool|string 接口调用凭据
     */
    public function getSpAccessTokenByCode($code)
    {
        $accessToken      = $this->getAccessToken();
        $refreshTokenInfo = $this->getSpComponent()->get_sp_access_token($accessToken, $code, '');
        if (!$refreshTokenInfo) {
            return false;
        }
        return $refreshTokenInfo;

    }

    public function getSpAccessTokenBySpId($spId)
    {

        $authName      = "spAccessToken" . $this->tpId . "_" . $spId;
        $spAccessToken = $this->cache->getCache($authName);
        if ($spAccessToken) {
            return $spAccessToken;
        } else {
            $accessToken      = $this->getAccessToken();
            $authName         = "spRefreshToken" . $this->tpId . "_" . $spId;
            $spRefreshToken   = $this->cache->getCache($authName);
            $refreshTokenInfo = $this->getSpComponent()->get_sp_access_token($accessToken, '', $spRefreshToken);

            $authName = "spAccessToken" . $this->tpId . "_" . $spId;
            $this->cache->setCache($authName, $refreshTokenInfo['access_token'], $refreshTokenInfo['expires_in']);

            $authName = "spRefreshToken" . $this->tpId . "_" . $spId;
            $this->cache->setCache($authName, $refreshTokenInfo['refresh_token'], -1);
            return $refreshTokenInfo['access_token'];
        }

    }

    public function authorizeCallbackProcess($authCode, $expireIn)
    {
        $refreshTokenInfo = $this->getSpAccessTokenByCode($authCode);

        $appAccountInfo = $this->getSpComponent()->get_sp_account_info($refreshTokenInfo['access_token']);
        if ($appAccountInfo['errno'] != 0) {
            return array('code' => $appAccountInfo['errno'], 'msg' => $appAccountInfo['msg']);
        }
        $spId = $appAccountInfo['data']['app_id'];
        if ($spId) {
            $authName = "spAccessToken" . $this->tpId . "_" . $spId;
            $this->cache->setCache($authName, $refreshTokenInfo['access_token'], $refreshTokenInfo['expires_in']);

            $authName = "spRefreshToken" . $this->tpId . "_" . $spId;
            $this->cache->setCache($authName, $refreshTokenInfo['refresh_token'], -1);
        }

        return array('code' => 0, 'appAccountInfo' => $appAccountInfo['data']);
    }

    public function uploadPackage($spId, $templateId, $extJson, $version, $desc)
    {
        $spAccessToken = $this->getSpAccessTokenBySpId($spId);

        $rsArr = $this->getSpComponent()->upload_package($spAccessToken, $templateId, $extJson, $version, $desc);
        return $rsArr;

    }

    public function packageSubmitAudit($spId, $packageId, $content, $remark)
    {
        $spAccessToken = $this->getSpAccessTokenBySpId($spId);

        $rsArr = $this->getSpComponent()->package_submitaudit($spAccessToken, $packageId, $content, $remark);
        return $rsArr;

    }

    public function packageRelease($spId, $packageId)
    {
        $spAccessToken = $this->getSpAccessTokenBySpId($spId);

        $rsArr = $this->getSpComponent()->package_release($spAccessToken, $packageId);
        return $rsArr;
    }

    public function modifyDomain($spId, $argsArr)
    {
        $spAccessToken = $this->getSpAccessTokenBySpId($spId);

        $rsArr = $this->getSpComponent()->modify_domain($spAccessToken, $argsArr);
        return $rsArr;
    }

    public function getPackageList($spId)
    {
        $spAccessToken = $this->getSpAccessTokenBySpId($spId);

        $rsArr = $this->getSpComponent()->get_package_list($spAccessToken);
        return $rsArr;
    }

    public function generateCode($spId)
    {
        $spAccessToken = $this->getSpAccessTokenBySpId($spId);

        $codeStr = $this->getSpComponent()->generate_qrcode($spAccessToken);
        return $codeStr;
    }

    public function onAuditNotify()
    {
        $ret = $this->getSpComponent()->audit_event_notify();
        if (is_array($ret)) {
            return $ret;
        }
        return false;
    }

    public function onComponentAuthNotify()
    {
        $ret = $this->getSpComponent()->process_event_notify();
        if (is_array($ret)) {
            if ($ret['Event'] && ($ret['MsgType'] == 'ticket')) {
                $authName = "bdVerifyTicket_" . $this->tpId;
                $this->cache->setCache($authName, $ret['Ticket'], -1);
            } else {
                switch ($ret['event']) {
                    case "UNAUTHORIZED":
                        // 移除授权
                        if ($ret['tpAppId'] == $this->tpId) {
                            $this->cache->cancelAuth($ret['appId']);
                            $authName = "spAccessToken" . $this->tpId . "_" . $ret['appId'];
                            $this->cache->removeCache($authName);

                            $authName = "spRefreshToken" . $this->tpId . "_" . $ret['appId'];
                            $this->cache->removeCache($authName);
                        }
                        break;
                    case "AUTHORIZED":
                        break;
                    case "UPDATE_AUTHORIZED":
                        break;
                }
            }
        }
        return $ret;
    }

    public function responseEvent()
    {
        die("success");
    }

    protected function getComponentVerifyTicket()
    {
        $authName = "bdVerifyTicket_" . $this->tpId;
        $ticket   = $this->cache->getCache($authName);
        return $ticket;
    }

}
