<?php
/**
 * 
 * @version 1.0.1
 * @author Kason Yang <kasonyang@163.com>
 */
class spNoCacheCacheDriver implements spCacheDriverInterface {
    private $vars;
    function get($key) {
        return $this->vars[$key];
    }
    function set($key, $value, $ttl = null) {
        $this->vars[$key] = $value;
        return true;
    }
    function exist($key) {
        return isset($this->vars[$key]);
    }
    function delete($key) {
        unset($this->vars[$key]);
        return TRUE;
    }
    function clear() {
        $this->vars = NULL;
        return true;
    }
}
