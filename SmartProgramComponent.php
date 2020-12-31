<?php
namespace App\Lib\Smartprogram;

/**
 * 百度小程序组件类
 */
class SmartProgramComponent
{
    const API_URL_PREFIX          = 'https://openapi.baidu.com/public/2.0/smartapp';
    const API_URL_PREFIX_COMMON   = 'https://openapi.baidu.com/rest/2.0/smartapp';
    const AUTH_CB_URL             = 'https://smartprogram.baidu.com/mappconsole/tp/authorization';
    const GET_SP_ACCESS_TOKEN_URL = 'https://openapi.baidu.com/rest/2.0/oauth/token';
    const GET_ACCESS_TOKEN_URL    = '/auth/tp/token';
    const GET_PREAUTHCODE_URL     = '/tp/createpreauthcode';
    const GET_SP_ACCOUNTINFO_URL  = '/app/info';
    const UPLOAD_PACKAGE_URL      = '/package/upload';
    const GET_PACKAGE_LIST_URL    = '/package/get';
    const PACKAGE_SUBMITAUDIT_URL = '/package/submitaudit';
    const PACKAGE_RELEASE         = '/package/release';
    const PACKAGE_GETDETAIL       = '/package/getdetail';
    const APP_QRCODE              = '/app/qrcode';
    const MODIFY_DOMAIN           = '/app/modifydomain';
    const MODIFY_WEBVIEW_DOMAIN   = '/app/modifywebviewdomain';

    public $tpId;
    public $tpKey;
    public $secretKey;
    public $aesKey;
    public $token;

    public function __construct($tpId, $tpKey, $secretKey, $aesKey, $token, $ticket)
    {
        $this->tpId      = $tpId;
        $this->tpKey     = $tpKey;
        $this->secretKey = $secretKey;
        $this->aesKey    = $aesKey;
        $this->token     = $token;
        $this->ticket    = $ticket;
    }

    public function get_access_token()
    {
        $arr = [
            'client_id' => $this->tpKey,
            'ticket'    => $this->ticket,
        ];
        $result = $this->http_get(self::API_URL_PREFIX . self::GET_ACCESS_TOKEN_URL, $arr);
        if ($result) {
            $json = json_decode($result, true);
            if ($json['errno'] == 0) {
                return $json['data'];
            }
        }
        return false;
    }

    public function get_pre_auth_code($access_token)
    {
        $arr = [
            'access_token' => $access_token,
        ];
        $result = $this->http_get(self::API_URL_PREFIX_COMMON . self::GET_PREAUTHCODE_URL, $arr);
        if ($result) {
            $json = json_decode($result, true);
            if ($json['errno'] == 0) {
                return $json['data'];
            }
        }
        return false;
    }

    public function get_auth_cb_url($pre_auth_code, $redirect_uri)
    {
        return self::AUTH_CB_URL . "?client_id=" . urlencode($this->tpKey)
        . "&pre_auth_code=" . urlencode($pre_auth_code) . "&redirect_uri=" . urlencode($redirect_uri);
    }

    /**
     * 获得（刷新）授权小程序的接口调用凭据
     * @Author   yxg
     * @DateTime 2020-04-09T20:50:28+0800
     * @param    [type]                   $access_token  [description]
     * @param    [type]                   $code          [description]
     * @param    [type]                   $refresh_token [description]
     * @return   [type]                                  [description]
     */
    public function get_sp_access_token($access_token, $code, $refresh_token)
    {
        $arr['access_token'] = $access_token;
        if ($code) {
            $arr['grant_type'] = 'app_to_tp_authorization_code';
            $arr['code']       = $code;
        } elseif ($refresh_token) {
            $arr['grant_type']    = 'app_to_tp_refresh_token';
            $arr['refresh_token'] = $refresh_token;
        } else {
            return false;
        }

        $result = $this->http_get(self::GET_SP_ACCESS_TOKEN_URL, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;

    }

    public function get_sp_account_info($sp_access_token)
    {
        $arr = [
            'access_token' => $sp_access_token,
        ];
        $result = $this->http_get(self::API_URL_PREFIX_COMMON . self::GET_SP_ACCOUNTINFO_URL, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;
    }

    public function upload_package($sp_access_token, $template_id, $ext_json, $user_version, $user_desc)
    {
        $arr = [
            'access_token' => $sp_access_token,
            'template_id'  => $template_id,
            'ext_json'     => $ext_json,
            'user_version' => $user_version,
            'user_desc'    => $user_desc,
        ];
        $result = $this->http_post(self::API_URL_PREFIX_COMMON . self::UPLOAD_PACKAGE_URL, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;
    }

    public function package_submitaudit($sp_access_token, $package_id, $content, $remark)
    {
        $arr = [
            'access_token' => $sp_access_token,
            'package_id'   => $package_id,
            'content'      => $content,
            'remark'       => $remark,
        ];
        $result = $this->http_post(self::API_URL_PREFIX_COMMON . self::PACKAGE_SUBMITAUDIT_URL, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;
    }

    public function package_release($sp_access_token, $package_id)
    {
        $arr = [
            'access_token' => $sp_access_token,
            'package_id'   => $package_id,
        ];
        $result = $this->http_post(self::API_URL_PREFIX_COMMON . self::PACKAGE_RELEASE, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;
    }

    public function modify_domain($sp_access_token, $argsArr = [])
    {
        $arr = [
            'access_token' => $sp_access_token,
        ];
        $action = isset($argsArr['action']) ? $argsArr['action'] : '';
        if ($action && $action != 'get') {
            $arr['action']          = $action;
            $arr['download_domain'] = isset($argsArr['download_domain']) ? $argsArr['download_domain'] : '';
            $arr['request_domain']  = isset($argsArr['request_domain']) ? $argsArr['request_domain'] : '';
            $arr['socket_domain']   = isset($argsArr['socket_domain']) ? $argsArr['socket_domain'] : '';
            $arr['upload_domain']   = isset($argsArr['upload_domain']) ? $argsArr['upload_domain'] : '';
        }
        $result = $this->http_post(self::API_URL_PREFIX_COMMON . self::MODIFY_DOMAIN, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;

    }

    public function modify_webview_domain($sp_access_token, $argsArr = [])
    {
        $arr = [
            'access_token' => $sp_access_token,
        ];
        $action = isset($argsArr['action']) ? $argsArr['action'] : '';
        if ($action && $action != 'get') {
            $arr['action']          = $action;
            $arr['web_view_domain'] = isset($argsArr['web_view_domain']) ? $argsArr['web_view_domain'] : '';
        }
        $result = $this->http_post(self::API_URL_PREFIX_COMMON . self::MODIFY_WEBVIEW_DOMAIN, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;

    }

    public function generate_qrcode($sp_access_token)
    {
        $arr = [
            'access_token' => $sp_access_token,
        ];

        return self::API_URL_PREFIX_COMMON . self::APP_QRCODE . '?' . http_build_query($arr);
    }

    public function get_package_list($sp_access_token)
    {
        $arr = [
            'access_token' => $sp_access_token,
        ];
        $result = $this->http_get(self::API_URL_PREFIX_COMMON . self::GET_PACKAGE_LIST_URL, $arr, ['Content-Type: application/x-www-form-urlencoded']);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;
    }

    public function get_package_detail($sp_access_token, $package_id = '', $type = 1)
    {
        $arr = [
            'access_token' => $sp_access_token,
        ];
        if ($package_id) {
            $arr['package_id'] = $package_id;
        }
        $result = $this->http_get(self::API_URL_PREFIX_COMMON . self::PACKAGE_GETDETAIL, $arr);
        if ($result) {
            $json = json_decode($result, true);
            return $json;
        }
        return false;
    }

    public function process_event_notify()
    {
        $postStr = file_get_contents('php://input');
        $postArr = json_decode($postStr, true);
        if ($this->vali_sign($postArr)) {
            $dec_msg = $this->decrypt($postArr['Encrypt']);
            if ($dec_msg) {
                $arr = json_decode($dec_msg, true);
                return $arr;
            }
        }
        return false;

    }

    public function audit_event_notify()
    {
        $postStr = file_get_contents('php://input');
        $postArr = json_decode($postStr, true);
        if ($this->vali_sign($postArr)) {
            $dec_msg = $this->decrypt($postArr['Encrypt']);
            if ($dec_msg) {
                $arr = json_decode($dec_msg, true);
                return $arr;
            }
        }

        return false;
    }

    protected function vali_sign($post)
    {
        $bool  = false;
        $allow = ['Nonce', 'TimeStamp', 'Encrypt', 'MsgSignature'];
        foreach (array_filter($post) as $key => $value) {
            in_array($key, $allow) && $data[$key] = $value;
        }
        if (count($data) == count($allow)) {
            $signObj                                   = new MsgSignatureUtil;
            $newSign                                   = $signObj->getMsgSignature($this->token, $data['TimeStamp'], $data['Nonce'], $data['Encrypt']);
            $newSign == $data['MsgSignature'] && $bool = true;
        }
        return $bool;
    }

    protected function decrypt($encryptStr)
    {

        $obj        = new AesDecryptUtil($this->aesKey);
        $xmlContent = $obj->decrypt($encryptStr);
        return $xmlContent;

    }

    private function http_get($url, $param = [], $headerArr = [])
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        $str = !empty($param) ? http_build_query($param) : '';
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $str);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($headerArr) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        }
        $content  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (intval($httpCode) == 200) {
            return $content;
        } else {
            return false;
        }
    }

    private function http_post($url, $param = [])
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $content  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (intval($httpCode) == 200) {
            return $content;
        } else {
            return false;
        }
    }
}
