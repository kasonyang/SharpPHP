<?php
/**
 * 
 * @version 1.0.1
 * @author Kason Yang <kasonyang@163.com>
 */
class apcCacheDriver implements spCacheDriverInterface{
    function get($key) {
        return apc_fetch($key);
    }
    function set($key, $value, $ttl = null) {
        return apc_store($key, $value, $ttl);
    }
    function exist($key) {
        return apc_exists($key);
    }
    function delete($key) {
        return apc_delete($key);
    }
    function clear() {
        return apc_clear_cache();
    }
}