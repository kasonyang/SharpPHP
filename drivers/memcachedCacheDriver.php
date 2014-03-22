<?php
/**
 * 
 * @version 1.0.1
 * @author Kason Yang <kasonyang@163.com>
 */
class memcachedCacheDriverException{}
class memcachedCacheDriver implements spCacheDriverInterface{
    private function getMemcached(){
        static $memcached =null;
        if(!$memcached){
            $mc = new Memcached();
            if(!$mc->addServer(spConfig::get('memcached.host'), spConfig::get('memcached.port'))){
                throw new memcachedCacheDriverException('Failed to Connect the Memcached Server');
            }
            $memcached = $mc;
        }
        return $memcached;
    }
    function get($key) {
        return $this->getMemcached()->get($key);
    }
    function set($key, $value, $ttl = null) {
        return $this->getMemcached()->set($key, $value, $ttl);
    }
    function exist($key) {
        $this->getMemcached()->get($key);
        return $this->getMemcached()->getResultCode() !== Memcached::RES_NOTFOUND;
    }
    function delete($key) {
        return $this->getMemcached()->delete($key);
    }
    function clear() {
        return $this->getMemcached()->flush();
    }
}