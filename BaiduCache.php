<?php
namespace App\Lib\Smartprogram;

class BaiduCache
{
    protected $redis;

    public function __construct($redisHost, $redisPort)
    {
        $this->redis      = new \Redis();
        $this->redis->connect($redisHost, $redisPort);
    }

    public function setCache($cacheName, $cacheValue, $expireIn)
    {
        $redisObj = $this->redis;
        $ret = $redisObj->get($cacheName);
        if ($expireIn > 0) {
            $redisObj->set($cacheName, $cacheValue);
            $redisObj->expire($cacheName, $expireIn);
        } else {
            $redisObj->set($cacheName, $cacheValue);
        }

        return true;
    }

    public function getCache($cacheName)
    {
        $redisObj =$this->redis;
        $ret = $redisObj->get($cacheName);
        return $ret;
        
    }

    public function removeCache($cacheName)
    {
        $redisObj = $this->redis;
        $ret = $redisObj->del($cacheName);
        return true;
    }

    public function cancelAuth($appId)
    {
        // ...视自身情况，进行授权记录的一个维护
        return true;
    }
}
