<?php
/**
 * 
 * @package SharpPHP
 * @author Kason Yang <kasonyang@163.com>
 */
class spURLParseException extends Exception {
    
}

class spURLFormatException extends Exception {
    
}

class spFileNotExistException extends Exception {
    
}

class spNoModuleException extends Exception {
    
}

class spNoControllerException extends Exception {
    
}

class spNoActionException extends Exception {
    
}

class spRedirectException extends Exception {
    
}

class spStopException extends Exception {
    
}

interface spViewDriverInterface{
    /**
     * 渲染模块
     * @param string $template_file 模板文件
     * @param array $vars 变量关联数组
     */
    function render($template_file,$vars);
}

interface spCacheDriverInterface{
    function get($key);
    /**
     * 
     * @param string $key
     * @param mixed $value
     * @param integer $ttl
     * @return bool
     */
    function set($key,$value,$ttl=null);
    function exist($key);
    /**
     * 
     * @return bool
     */
    function delete($key);
    /**
     * @return bool Description
     */
    function clear();
}

interface spDatabaseDriverInterface{
    static function connect($server, $user, $password) ;
    function __construct($connection = null) ;
    function buildSelect(array $params);
    function buildDelete(array $params);
    function buildUpdate(array $params);
    function buildInsert(array $params);
    function count(array $params);
    function query($sql);
    function error();
    function errno();
    function insertId();
    /**
     * @param string $field_name
     * @return Array
     */
    function fetchFields($table_name);
}

interface spSessionDriverInterface{
    function open($savePath,$sessionName);
    function close();
    function read($sessionId);
    function write($sessionId,$data);
    function destroy($sessionId);
    function gc($lifetime);
}

class spFilterChain {

    private $filters_path, $execute_index = 0;
    private $controller, $action;

    private function loadFilter($index) {
        $path = $this->filters_path[$index];
        $name = basename($path, '.php');
        if (!class_exists($name))
            include $path;
        return new $name;
    }

    function __construct($controller, $action) {
        $this->controller = $controller;
        $this->action = $action;
    }

    function addFilter($filter_path) {
        $this->filters_path[] = $filter_path;
    }

    function execute() {
        $filter_count = count($this->filters_path);
        if ($this->execute_index < $filter_count) {
            $filter = $this->loadFilter($this->execute_index);
            $this->execute_index++;
            $filter->execute($this);
        } elseif ($this->execute_index == $filter_count) {
            $this->execute_index++;
            $action_ret = $this->controller->dispatch($this->action);
            if ($action_ret !== false) {
                if(is_string($action_ret)){
                    echo $action_ret;
                }else{
                    if(spView::getAutoDisplay()){
                        spView::display();
                    }
                }
            }
        }
    }

}

class spFilter {

}

class spConfig {
    /**
     *
     * @var spConfigParser 
     */
    static private $config;
    
    /**
     * 读取配置信息
     * @param string $name 主键，使用'.'分级
     * @param mix $default 默认值，如果没有找到相应的配置，则返回此值
     * @return mix
     */
    static function get($name,$default = null){
        $name_arr = explode(':', $name);
        if(!isset($name_arr[1])){
            $name_arr[1] = $name_arr[0];
            $name_arr[0] = 'system';
        }
        if(!isset(self::$config[$name_arr[0]])){
            self::$config[$name_arr[0]] = new spConfigParser(SP_APP_PATH . "/configs/" . $name_arr[0] . ".config.php");
        }
        return self::$config[$name_arr[0]]->get($name_arr[1],$default);
    }
}

class spConfigParser {

    private $file_path, $included, $configs;

    function __construct($file_path) {
        $this->file_path = $file_path;
    }

    function get($name = null, $default = null) {
        if (!$this->included) {
            if (file_exists($this->file_path))
                $this->configs = include $this->file_path;
            $this->included = true;
        }
        if ($name)
            $name_arr = explode('.', $name);
        $name_deep = count($name_arr);
        $config = $this->configs;
        for ($i = 0; $i < $name_deep; $i++) {
            $config = $config[$name_arr[$i]];
            if (!isset($config))
                return $default;
        }
        return $config;
    }

}

class spView {

    static private $vars, $tpl_dir, $tpl,$version,$auto_display;
    
    /**
     * 
     * @return spViewDriverInterface
     */
    static function loadDriver(){
        $type = spConfig::get('view.engine');
        $driver = loadDriver($type . 'ViewDriver');
        return $driver;
    }
    /**
     * 设置自动渲染模板
     * @param boolean $enable 是否自动渲染模板
     */
    static function setAutoDisplay($enable){
        self::$auto_display = $enable;
    }
    
    /**
     * 返回当前自动渲染模板设置
     * @return boolean
     */
    static function getAutoDisplay(){
        return self::$auto_display;
    }


    /**
    * 设置视图版本
    * 
    * 通过此函数，可以实现对多版本视图的支持，例如手机版、英文版等，此函数必须配合模板
    * 使用，模板命名规则：{action}.{version}.tpl
    * 
    * @param string $version 版本名
    */
   static function setVersion($version){
       self::$version = $version;
   }

   /**
    * 获取当前版本
    * 
    * @return string
    */
   static function getVersion(){
       return isset(self::$version) ? self::$version : spConfig::get('view.default_version');
   }
   
   /**
    * 设置模板目录，用于改变模板目录（如果没有调用此函数，则使用默认目录）
    * 
    * @param string $tpl_dir 目录
    */
   static function setTemplateDir($tpl_dir) {
        self::$tpl_dir = $tpl_dir;
    }

    /**
     * 设置模板文件名（相对路径，不带.tpl后缀，如果没有调用此函数，则使用默认文件名）
     * @param string $tpl 模板文件
     */
    static function setTemplate($tpl) {
        self::$tpl = $tpl;
    }

    /**
     * 读取模板文件名
     * @return string
     */
    static function getTemplate() {
        return self::$tpl;
    }

    /**
     * 手动输出视图，一般情况你的代码里不需要调用此函数，系统会自动调用
     * @param string $tpl 模板文件名
     */
    static function display($tpl = null) {
        $tpl_dir = self::$tpl_dir ? self::$tpl_dir : APP_VIEW_DIR .  '/' . spRequest::getControllerName();
        if (!$tpl) {
            $version = self::getVersion();
            $suffix = ($version ? '.' . $version : '' ) . '.tpl';
            $tpl = (self::$tpl ? self::$tpl : spRequest::getActionName()) . $suffix;
        }
        $app = self::$vars['app'];
        $app['parameter'] = spRequest::getParameter();
        $app['post']  = spRequest::getPost();
        $app['cookie']    = spRequest::getCookie();
        self::$vars['app'] = $app;
        
        $driver = self::loadDriver();
        echo $driver->render($tpl_dir . '/' . $tpl, self::$vars);
    }

    /**
     * 赋值模板变量
     * @param string $names 变量名
     * @param string $value 变量值
     */
    static function set($names,$value=NULL){
        if(!is_array($names)){
            $data = array($names => $value);
        }else{
            $data = $names;
        }
        foreach ($data as $key => $value) {
            self::$vars[$key] = $value;
        }
    }
    
    /**
     * 读取模板变量
     * @param string $name 变量名
     * @return mixed
     */
    static function get($name){
        return self::$vars[$name];
    }
}

class spController {

    /**
     * 派遣动作
     * @param string $action_name 动作名
     * @return mix 返回动作方法的返回值
     * @throws spNoActionException
     */
    function dispatch($action_name) {
        if (method_exists($this, '_init'))
            $this->_init();
        $action = $action_name . 'Action';
        $validator = $action_name . 'Validator';
        if (method_exists($this, $validator))
            $this->$validator();
        if (method_exists($this, $action)) {
            return $this->$action();
        }
        else
            throw new spNoActionException;
    }

    /**
     * 调用其他动作，后面的代码继续执行
     * @param string $controller_name 控制器名
     * @param string $action_name 动作名
     * @return mixed
     */
    function execute($controller_name, $action_name) {
        return spFront::getController($controller_name)->dispatch($action_name);
    }

    /**
     * 转到其他动作，调用此函数后，后面的代码将不再执行
     * @param string $controller_name 控制器名
     * @param string $action_name 动作名
     * @throws spStopException
     */
    function forward($controller_name, $action_name) {
        if ($this->execute($controller_name, $action_name) !== false) {
            spView::display();
        }
        throw new spStopException;
    }

    /**
     * 设置出错信息，后面的代码继续执行
     * @param string $description 错误描述
     * @param int $code 错误代号
     */
    function setError($description,$code = 0){
        $app = spView::get('app');
        $app['error'] = array(
            'code'  =>  $code,
            'description'   =>  $description
        );
        spView::set('app', $app);
    }
    
    /**
     * 抛出错误，后面的代码不再执行
     * @param string $error 错误描述
     * @param int $code 错误代号
     * @throws spStopException
     */
    function error($description,$code = 0){
        $this->setError($description, $code);
        spView::display();
        throw new spStopException();
    }
}

class spRequest {

    static private $query, $post, $cookie;
    static private $params;

    static private function getParams(){
        if(!isset(self::$params)){
            $baseuri = substr(spRequest::getBaseURL(), 1);
            $uri = $_SERVER['REQUEST_URI'];
            $reluri = substr($uri, -(strlen($uri) - strlen($baseuri))); //$reluri形如'/...?...'
            //$reluri_arr = explode('?', $reluri);
            $params = spURL::parse($reluri);
            $querys = self::getAllQuery();
            if ($querys)
                $params = array_merge($querys, $params);
            self::$params = $params;
        }
        return self::$params;
    }
    
    static private function magic_gpc_stripslashes($arr) {
        foreach ($arr as $key => $value) {
            if (is_array($arr[$key]))
                $ret[$key] = self::magic_gpc_stripslashes($arr[$key]);
            else
                $ret[$key] = stripslashes($value);
        }
        return $ret;
    }

    static private function getOriginalGPC($arr) {
        if ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())
                || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase')) != "off"))) {
            return self::magic_gpc_stripslashes($arr);
        } else {
            return $arr;
        }
    }

    static private function getAllQuery() {
        if (!isset(self::$query)) {
            if ($qs = self::getOriginalGPC($_GET)) {
                foreach ($qs as $k => $v) {
                    $qs[$k] = urldecode($v);
                }
            }
            self::$query = $qs;
        }
        return self::$query;
    }

    static private function getAllPost() {
        if (!isset(self::$post))
            self::$post = self::getOriginalGPC($_POST);
        return self::$post;
    }

    static private function getAllCookie() {
        if (!isset(self::$cookie))
            self::$cookie = self::getOriginalGPC($_COOKIE);
        return self::$cookie;
    }

    static private function getSubArray($arr, $keys = null) {
        if ($keys) {
            if(is_array($keys)){
                foreach ($keys as $v) {
                    $sub[$v] = $arr[$v];
                }
            }else{
                $sub = $arr[$keys];
            }
        } else {
            $sub = $arr;
        }
        return $sub;
    }

    /**
     * 返回请求的模块名
     * @return string
     */
    static function getModuleName() {
        $ps = self::getParams();
        return $ps['module'];
    }

    /**
     * 返回请求的控制器名
     * @return string
     */
    static function getControllerName() {
        $ps = self::getParams();
        return $ps['controller'];
    }

    /**
     * 返回请求的动作名
     * @return string
     */
    static function getActionName() {
        $ps = self::getParams();
        return $ps['action'];
    }

    /**
     * 返回请求URL的参数
     * @param string|array $names 参数名
     * @return string|array $names为字符串时返回单个结果,为数组或null时返回多个结果(数组)
     */
    static function getParameter($names = null) {
        return self::getSubArray(self::$params, $names);
    }

    /*
      function getQuery($names=null){
      return $this->getSubArray($this->getAllQuery(),$names);
      }
     */

    /**
     * 返回表单的POST数据
     * @param string|array $names 参数名
     * @return string|array $names为字符串时返回单个结果,为数组或null时返回多个结果(数组)
     */
    static function getPost($names = null) {
        return self::getSubArray(self::getAllPost(), $names);
    }

    /**
     * 返回浏览器保存的Cookie
     * @param string|array $names
     * @return string|array $names为字符串时返回单个结果,为数组或null时返回多个结果(数组)
     */
    static function getCookie($names = null) {
        return self::getSubArray(self::getAllCookie(), $names);
    }

    /**
     * 是否存在参数
     * @param string $name
     * @return bool
     */
    static function hasParameter($name /*= null*/) {
        //if (isset($name))
            return isset(self::$params[$name]);
        //else
        //    return isset($this->params);
    }

    /*
      function hasQuery($name=null){
      if($name)   return isset ($_GET[$name]);
      else        return  isset($_GET);
      }
     */

    /**
     * 是否存在指定的POST数据
     * @param string $name
     * @return bool
     */
    static function hasPost($name = null) {
        if ($name)
            return isset($_POST[$name]);
        else
            return count($_POST) > 0;
    }

    /**
     * 是否存在指定的Cookie
     * @param string $name
     * @return bool
     */
    static function hasCookie($name = null) {
        if ($name)
            return isset($_COOKIE[$name]);
        else
            return count($_COOKIE) > 0;
    }

    /**
     * 是否存在指定的POST或GET
     * @param string $name
     * @return boolean
     */
    static function hasRequest($name){
        return self::hasPost($name) or self::hasParameter($name);
    }
    
    /**
     * 
     * 读取Request（POST或GET）
     * 
     * @param string|array $names
     * @return string|array
     */
    static function getRequest($names=NULL){
        if($ret = self::getPost($names)){
            return $ret;
        }else{
            return self::getParameter($names);
        }
    }
    
    /**
     * 是否为POST请求
     * @return bool
     */
    static function isPost(){
        return $_SERVER['REQUEST_METHOD'] == "POST";
    }
    
    /**
     * 是否为GET请求
     * @return bool
     */
    static function isGet(){
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }
    /**
     * 返回请求URL
     * @return string
     */
    static function getURL() {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * 返回入口文件的目录
     * @return string
     */
    static function getBaseURL() {
        return substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/') + 1);
    }
    
    /**
     * 验证通过POST方式提交的Token,该Token由模板的form函数自动生成
     * @return bool
     */
    static function validatePostToken(){
        return spToken::validate('sp_form', self::getPost('_SharpPHP_Form_Token'));
    }
}

class spFront {

    /**
     * 运行前段控制器
     * @global array $sharpphp_global_var
     * @param string $module_name 模块名
     * @param string $controller_name 控制器名
     * @param string $action 动作名
     */
    static function run($module_name = null, $controller_name = null, $action = null) {
        if ($module_name == null) {
            $module_name = spRequest::getModuleName();
        }
        if ($controller_name == null) {
            $controller_name = spRequest::getControllerName();
        }
        if ($action == null) {
            $action = spRequest::getActionName();
        }
        $app_path = realpath(SP_APP_PATH);
        define('APP_MODULE_DIR', $app_path . '/modules/' . $module_name);
        define('APP_CONTROLLER_DIR', APP_MODULE_DIR . '/controllers');
        define('APP_MODEL_DIR', APP_MODULE_DIR . '/models');
        define('APP_VIEW_DIR', APP_MODULE_DIR . '/views');
        
        spFront::registerModelPath(APP_MODEL_DIR);
        
        
        if (file_exists(APP_MODULE_DIR . '/main.php')) {
            include APP_MODULE_DIR . '/main.php';
        }
        try {
            $ctrl = spFront::getController($controller_name);
            $filter_chain = new spFilterChain($ctrl, $action);
            global $sharpphp_global_var;
            if ($filters = $sharpphp_global_var['filters']) {
                $filter_count = count($filters);
                for ($i = 0; $i < $filter_count; $i++) {
                    $filter_chain->addFilter(APP_FILTER_DIR . '/' . $filters[$i] . '.php');
                }
            }
            $filter_chain->execute();
        } catch (spNoActionException $e) {
            spFront::forward404();
        } catch (spNoControllerException $e) {
            spFront::forward404();
        } catch (spNoModuleException $e) {
            spFront::forward404();
        } catch (DajectConnectionException $e){
            exit('Error:'.$e->getMessage());
        } catch (spRedirectException $e) {
            
        } catch (spStopException $e) {
            
        }
    }
    
    /**
     * 停止动作的执行，方式：抛出spStopException异常，该异常自动被系统捕获处理
     * @throws spStopException
     */
    static function stop(){
        throw new spStopException();
    }

    /**
     * 
     * @param string $url
     * @throws spRedirectException
     */
    static function redirectOut($url = null) {
        if (!isset($url))
            $url = spRequest::getBaseURL();
        header("Location:$url");
        throw new spRedirectException;
    }

    /**
     * 
     * @param string $inner_url
     * @param string $return
     */
    static function redirect($inner_url = null , $return = null){
        $url = null;
        if($inner_url){
            $url = spURL::format($inner_url);
            if($return !== null and $return !==false){
                $return_url = is_bool($return) ? spRequest::getURL() : $return;
                $u = new spStandardURL($url);
                $u->setQuery('return', $return_url);
                $url = $u->build();
            }
        }
        spFront::redirectOut($url);
    }

    /**
     * 
     */
    static function redirectReferer() {
        spFront::redirectOut($_SERVER['HTTP_REFERER']);
    }

    /**
     * 跳转到目前URL指定的URL（由URL里的return参数指定）
     * 出于安全方面的考虑，指定的URL必须以‘/’开头
     * @param string $default
     */
    static function redirectRequest($default_outerurl='/') {
        $return_url = spRequest::getParameter('return');
        if($return_url and substr($return_url, 0,1)=='/'){
            $url = $return_url;
        }else{
            $url = $default_outerurl;
        }
        spFront::redirectOut($url);
    }

    /**
     * 将POST参数转为GET参数，并跳转，后面的代码不再执行
     * @param string $inner_url
     */
    static function redirectPostAsParameter($inner_url = null){
        $inner_url_arr =$inner_url===null ? spRequest::getParameter() : spURL::innerurl2array($inner_url);
        $ps = spRequest::getPost();
        $arr = array_merge($ps, $inner_url_arr);
        spFront::redirectOut(spURL::formatArray($arr));
    }

    /**
     * 返回404状态码
     */
    static function forward404() {
        header('HTTP/1.0 404 Not Found');
        exit();
    }

    /**
     * 转到错误页面，后面的代码不再执行
     * @param string $err_msg 错误描述
     * @param string $error_tpl_id 错误页面使用的模板
     * @throws spStopException
     */
    static function forwardError($err_msg = '错误的请求！', $error_tpl_id = null) {
        spView::set('error' , $err_msg);
        $tpl = 'error';
        if (isset($error_tpl_id))
            $tpl.=$error_tpl_id;
        spView::setTemplateDir(SP_BASE_DIR . '/templates');
        spView::display($tpl . '.tpl');
        throw new spStopException;
    }

    /**
     * 向浏览器发送文件（弹出下载对话框）
     * @param string $file_path 要发送的文件
     * @param string $display_name 显示的文件名
     */
    static function sendFile($file_path,$display_name = null){
        if($display_name ===null){
            $display_name = basename($file_path);
        }
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename='.urlencode($display_name));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Content-Length: '.filesize($file_path));
        readfile($file_path);
    }
    
    /**
     * 以文件的形式向浏览器发送字符串（弹出下载对话框）
     * @param string $str
     * @param string $display_name
     */
    static function sendStringAsFile($str,$display_name){
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename='.urlencode($display_name));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Content-Length: '. strlen($str));
        echo $str;
    }


    /**
     * 返回控制器
     * @param string $controller_name 控制器名
     * @return spController
     * @throws spNoControllerException
     */
    static function getController($controller_name) {
        static $ctrls = NULL;
        if (!$ctrls[$controller_name]) {
            $controller = $controller_name . 'Controller';
            if (!class_exists($controller)) {
                $ctrl_file = APP_CONTROLLER_DIR . '/' . $controller . '.php';
                if (!file_exists($ctrl_file))
                    throw new spNoControllerException;
                include $ctrl_file;
                if (!class_exists($controller))
                    throw new spNoControllerException;
            }
            $c = new $controller();
            $ctrls[$controller_name] = $c;
        }
        return $ctrls[$controller_name];
    }

    /**
     * 注册过滤器
     * @global array $sharpphp_global_var
     * @param string $filter_name 过滤器名，不带.php后缀，如：baseFilter
     */
    static function registerFilter($filter_name) {
        global $sharpphp_global_var;
        $sharpphp_global_var['filters'][] = $filter_name;
    }

    /**
     * 注册模型搜索路径
     * @global array $sharpphp_global_var
     * @param string $path 路径
     */
    static function registerModelPath($path) {
        global $sharpphp_global_var;
        if ($sharpphp_global_var['model_path'])
            $sharpphp_global_var['model_path'].=';';
        $sharpphp_global_var['model_path'].=$path;
    }

    /**
     * 
     * @global array $sharpphp_global_var
     * @param string $path 要注册的路径
     */
    static function registerImportPath($path) {
        global $sharpphp_global_var;
        if ($sharpphp_global_var['import_path'])
            $sharpphp_global_var['import_path'].=';';
        $sharpphp_global_var['import_path'].=$path;
    }

}

class spStandardURL {

    private $main_url, $querys;

    private static function query2array($query) {
        $querys = explode('&', $query);
        foreach ($querys as $q) {
            $arr = explode('=', $q);
            if ($arr[0] != '')
                $ret[$arr[0]] = isset($arr[1]) ? $arr[1] : '';
        }
        return $ret;
    }

    static function array2query($arr) {
        foreach ($arr as $k => $v) {
            if ($query)
                $query.='&';
            $query.=urlencode($k) . '=' . urlencode($v);
        }
        return $query;
    }

    function __construct($url) {
        $url_arr = explode('?', $url);
        $this->main_url = $url_arr[0];
        if (isset($url_arr[1])) {
            $this->querys = self::query2array($url_arr[1]);
        }
    }

    function getQuery($name = null) {
        if (isset($name))
            return $this->querys[$name];
        else
            return $this->querys;
    }

    function setQuery($name, $value) {
        $this->querys[$name] = $value;
    }

    function build() {
        return $this->main_url . '?' . self::array2query($this->querys);
    }

    function __toString() {
        return $this->build();
    }

}

class spURL {

    const MODE_COMPATIBLE = 0;
    const MODE_REWRITE = 1;

    /**
     * 将URL格式的参数串转换为数组
     * @param string $query URL格式的参数串,如'name=value&name=value'
     * @return array 关联数组
     */
    static function query2array($query) {
        $ret = array();
        $querys = explode('&', $query);
        foreach ($querys as $q) {
            $arr = explode('=', $q);
            if ($arr[0] != '')
                $ret[$arr[0]] = isset($arr[1]) ? urldecode($arr[1]) : '';
        }
        return $ret;
    }

    /**
     * 将关联数组格式化为URL格式的参数串
     * @param array $arr 关联数组
     * @return string
     */
    static function array2query($arr) {
        foreach ($arr as $k => $v) {
            if ($query)
                $query.='&';
            $query.=urlencode($k) . '=' . urlencode($v);
        }
        return $query;
    }

    /**
     * 将InnerURL转换为关联数组
     * @param string $url 要转换的InnerURL
     * @return array 关联数组
     */
    static function innerurl2array($url) {
        $url_arr = explode('?', $url);
        $c_n_a = explode('/', $url_arr[0]);
        switch (count($c_n_a)) {
            case 3:
                $ret['module'] = $c_n_a[0];
                $ret['controller'] = $c_n_a[1];
                $ret['action'] = $c_n_a[2];
                break;
            case 2:
                $ret['module'] = spRequest::getModuleName();
                $ret['controller'] = $c_n_a[0];
                $ret['action'] = $c_n_a[1];
                break;
            case 1:
                $ret['module'] = spRequest::getModuleName();
                $ret['controller'] = spRequest::getControllerName();
                $ret['action'] = $c_n_a[0] == '' ? spRequest::getActionName() : $c_n_a[0];
        }
        if ($url_arr[1]) {
            $querys = self::query2array($url_arr[1]);
            $ret = array_merge($ret, $querys);
        }
        return $ret;
    }

    /**
     * 将InnerURL转换为URL格式的参数串
     * @param string $url 要转换的InnerURL
     * @return string 转换后的结果
     */
    private static function innerurl2query($url) {
        $params = self::innerurl2array($url);
        return self::array2query($params);
    }

    /**
     * 返回URL的模式
     * @return int URL的模式,类内部已定义了支持的常量
     */
    private static function getMode() {
        return spConfig::get('url.mode');
    }

    private static function getRouter() {
        static $router;
        if (!$router) {
            $router = new spRouter;
            $route_arr = spConfig::get('url.route');
            foreach ($route_arr as $k => $v) {
                $route = new spRoute($v['url'], $v['parameter'], $v['requirement'], $v['type']);
                $router->addRoute($k, $route);
            }
        }
        return $router;
    }

    /**
     * 解析外部URL
     * @param string $url 外部URL
     * @return boolean|array 成功时返回URL的参数关联数组，失败时返回FALSE
     * @throws spURLParseException
     */
    static function parse($url) {
        if ($url == '')
            return false;
        
        $url_arr = explode('?', $url);
        switch (self::getMode()) {
            case self::MODE_COMPATIBLE:
                $params = self::query2array($url_arr[1]);
                break;
            case self::MODE_REWRITE:
                $main_url = $url_arr[0];
                if($url_suffix = spConfig::get('url.suffix')){
                    if(substr($main_url,-strlen($url_suffix)) == $url_suffix)
                            $main_url = substr($main_url, 0, -strlen($url_suffix));
                }
                $params = self::getRouter()->parse($main_url);
                if ($params === false)
                    throw new spURLParseException('无法解析的URL:' . $main_url);
                break;
        }
        foreach ($params as $key => $value) {
            $params[$key] = urldecode($value);
        }
        
        if(!$params['module']){
            $params['module'] = spConfig::get('url.default_module');
        }
        if(!$params['controller']){
            $params['controller'] = spConfig::get('url.default_controller');
        }
        if(!$params['action']){
            $params['action'] = spConfig::get('url.default_action');
        }
        return $params;
    }

    /**
     * 格式化内部URL成外部URL
     * @param string $url 内部URL
     * @return string 格式化的外部URL
     */
    static function format($url) {
        $arr = self::innerurl2array($url);
        return self::formatArray($arr);
    }

    /**
     * 将数组格式化为外部URL
     * @param array $arr URL参数关联数组
     * @return string 格式化的外部URL
     * @throws spURLFormatException
     */
    static function formatArray($arr) {
        switch (self::getMode()) {
            case self::MODE_COMPATIBLE:
                return '?' . self::array2query($arr);
                break;
            case self::MODE_REWRITE:
                $ret = self::getRouter()->format($arr);
                if ($ret === false)
                    throw new spURLFormatException('无法格式化URL:' . print_r($arr, TRUE));
                return spRequest::getBaseURL() . $ret . spConfig::get('url.suffix');
                break;
        }
    }

}

class spRouter {

    /**
     *
     * @var spRoute route
     */
    private $route;

    function addRoute($name, spRoute $route) {
        $this->route[$name] = $route;
    }

    function parse($url) {
        if ($this->route) {
            foreach ($this->route as $route) {
                $params = $route->parse($url);
                if ($params !== FALSE)
                    return $params;
            }
        }
        return false;
    }

    function format($params) {
        if ($this->route) {
            foreach ($this->route as $route) {
                if ($url = $route->format($params))
                    return $url;
            }
        }
        return false;
    }

}

class spRoute {

    const TYPE_STATIC = 0;
    const TYPE_DYMATIC = 1;

    private $delimiter = '/';
    private $parts, $names;
    private $params, $requirements, $type;

    function __construct($url, $params = null, $requirements = null, $type = 0) {
        $this->params = $params;
        $this->type = $type;
        $this->requirements = $requirements;
        if($url){
            $url_arr = explode($this->delimiter, $url);
            foreach ($url_arr as $k => $v) {
                if (substr($v, 0, 1) === ':') {
                    $this->parts[$k] = null;
                    $this->names[$k] = substr($v, 1);
                } else {
                    $this->parts[$k] = $v;
                }
            }
        }
    }

    function parse($url) {
        $parts = array();
        $vars = array();
        $url_parts = explode($this->delimiter, trim($url, $this->delimiter));
        foreach ($url_parts as $k => $v) {
            if ($v != '')
                $parts[] = $v;
        }
        $parts_count = count($parts);
        $route_parts_count = count($this->parts);
        if ($parts_count < $route_parts_count)
            return false;
        if ($this->type == self::TYPE_STATIC) {
            if ($parts_count != $route_parts_count)
                return FALSE;
        }
        foreach ($parts as $k => $v) {
            if (isset($this->names[$k])) {
                $vars[$this->names[$k]] = $v;
            } elseif (isset($this->parts[$k])) {
                if ($this->parts[$k] !== $v)
                    return false;
            }else {
                if ($this->type == self::TYPE_STATIC)
                    return false;
                $params[] = $v;
            }
        }
        if ($this->requirements) {
            foreach ($this->names as $k => $v) {
                if ($this->requirements[$v]) {
                    if (!preg_match('/^' . $this->requirements[$v] . '$/', $parts[$k]))
                        return false;
                }
            }
        }
        $params_count = count($params);
        for ($i = 0; $i < $params_count; $i+=2) {
            $vars[$params[$i]] = $params[$i + 1];
        }
        if ($this->params) {
            foreach ($this->params as $k => $v) {
                $vars[$k] = $v;
            }
        }
        //$vars=  array_merge ($vars,  $this->params);
        return $vars;
    }

    function format($params) {
        $keys = $this->names;
        if ($this->params) {
            if ($keys) {
                $keys = array_merge($keys, array_keys($this->params));
            } else {
                $keys = array_keys($this->params);
            }
        }
        if ($this->type == 0) {
            if (count($keys) != count($params))
                return false;
            foreach ($keys as $k) {
                if (!array_key_exists($k, $params))
                    return false;
            }
        }
        if ($this->requirements) {
            foreach ($this->names as $k => $v) {
                if ($this->requirements[$v]) {
                    if (!preg_match('/^' . $this->requirements[$v] . '$/', $params[$v]))
                        return false;
                }
            }
        }
        if ($this->params) {
            foreach ($this->params as $k => $v) {
                if ($params[$k] !== $v)
                    return false;
                unset($params[$k]);
            }
        }
        foreach ($this->parts as $k => $v) {
            if (isset($this->names[$k])) {
                $parts[$k] = $params[$this->names[$k]];
                unset($params[$this->names[$k]]);
            } else {
                $parts[$k] = $this->parts[$k];
            }
        }
        if ($params) {
            foreach ($params as $k => $v) {
                $parts[] = urlencode($k);
                $parts[] = urlencode($v);
            }
        }
        return implode($this->delimiter, $parts);
    }

}

class spToken{
    /**
     * 产生新的Token
     * @param string $name Token关键字
     * @return string 生成的Token
     */
    static function generate($name){
        $sid = session_id();
        if(empty($sid)) session_start ();
        return $_SESSION['sp_token'][$name] = uniqid() . rand(0,9999);
    }
    
    /**
     * 验证Token是否正确
     * @param string $name Token关键字
     * @param string $token 要验证的Token值
     * @return boolean
     */
    static function validate($name,$token){
        if(isset($_SESSION['sp_token'][$name])){
            $ret =  $_SESSION['sp_token'][$name] == $token;
            unset($_SESSION['sp_token'][$name]);
            return $ret;
        }
        return false;
    }
}

class spCache implements spCacheDriverInterface{
    /**
     *
     * @var spCacheDriverInterface
     */
    private $driver;
    
    /**
     * 返回缓存示例
     * @staticvar null $instances
     * @param string $type 缓存类型
     * @return spCache
     */
    static function getInstance($type = null){
        static $instances = null;
        if(!$type) $type = spConfig::get ('cache.default_type');
        if(!isset($instances[$type])){
            $driver_type = spConfig::get('cache.enable') ? $type : 'spNoCache';
            $instances[$type] = new self($driver_type);
        }
        return $instances[$type];
    }

    private function __construct($type) {
        $drive_name = $type . 'CacheDriver';
        include SP_DRIVER_DIR . '/' . $drive_name . '.php' ;
        $this->driver = new $drive_name();
    }
    
    /**
     * 读取数据
     * @param string $key 主键
     * @return mix
     */
    function get($key){
        return $this->driver->get($key);
    }
    
    /**
     * 保存数据
     * @param string $key
     * @param mix $value
     * @param int $ttl
     * @return bool
     */
    function set($key, $value, $ttl = null) {
        return $this->driver->set($key, $value, $ttl);
    }
    
    /**
     * 检查是否已有数据
     * @param string $key 主键
     * @return bool
     */
    function exist($key) {
        return $this->driver->exist($key);
    }
    
    /**
     * 清空数据
     * @return bool
     */
    function clear() {
        return $this->driver->clear();
    }
    
    /**
     * 删除数据
     * @param type $key 主键
     * @return bool
     */
    function delete($key) {
        return $this->driver->delete($key);
    }
}

class spWidget{
    
    private $vars;
    
    function __construct() {
        $this->assign('this', $this);
    }

    /**
     * 模板赋值
     * @param string|array $name
     * @param mix $value
     */
    function assign($name,$value=null){
        if(is_array($name)){
            foreach($name as $vk => $v){
                $this->vars[$vk] = $v;
            }
        }else{
            $this->vars[$name] = $value;
        }   
    }
    /**
     * 渲染视图
     * @param string $version 模板版本
     * @return string
     * @throws Exception
     */
    function render($version = NULL) {
        $view_driver = spView::loadDriver();
        $name = get_class($this);
        if(substr($name, -6) !== 'Widget'){
            throw new Exception('错误的Widget类名:' . $name );
        }
        $short_name = substr($name, 0,-6);
        $suffix = '.tpl';
        if($version){
            $suffix = '.' . $version . $suffix;
        }
        $tpl_file = SP_APP_PATH . '/widgets/views/' . $short_name . $suffix;
        return $view_driver->render($tpl_file, $this->vars);
    }
    function __toString() {
        return $this->render();
    }
}

class spSessionFile{
    private $session;
    /**
     * 清除所有SessionFile
     * @return boolean
     */
    static function clear(){
        $ret = TRUE;
        if($sessions = $_SESSION['_SharpPHP_System']['SessionFile']){
            foreach($sessions as $s_name => $s_value){
                $sf = new spSessionFile($s_name);
                if(!$sf->delete()){
                    $ret = false;
                }
            }
        }
        return $ret;
    }
    function __construct($name) {
        $this->session = & $_SESSION['_SharpPHP_System']['SessionFile'][$name];
    }
    /**
     * 绑定文件
     * @param string $filename
     * @return void
     */
    function bind($filename){
        $this->session = $filename;
    }
    /**
     * 解除绑定
     * @return void
     */
    function unbind(){
        unset($this->session);
    }
    /**
     * 是否已绑定文件
     * @return boolean
     */
    function bound(){
        return isset($this->session);
    }
    /**
     * 返回已经绑定的文件名
     * @return string
     */
    function getBoundFile(){
        return $this->session;
    }
    /**
     * 解除绑定,并删除绑定的文件
     * @return boolean
     */
    function delete(){
        if(@unlink($this->session)){
            $this->unbind();
            return TRUE;
        }else{
            return false;
        }
        
    }
}

class spSessionManager{
    /**
     *
     * @var spSessionDriverInterface 
     */
    private $driver;
    static function register(){
        if($session_path = spConfig::get('session_path')){
            $session_path_arr = explode(':', $session_path);
            $session_type = $session_path_arr[0];
            /* @var $session_driver spSessionDriverInterface */
            $driver = loadDriver($session_type . 'SessionDriver');
            session_save_path($session_path_arr[1]);
            //$session_driver->setParameter($session_config['parameter']);
            $session_mgr = new spSessionManager($driver);
            session_set_save_handler(
                array($session_mgr, 'open'),
                array($session_mgr, 'close'),
                array($session_mgr, 'read'),
                array($session_mgr, 'write'),
                array($session_mgr, 'destroy'),
                array($session_mgr, 'gc')
            );
        }
    }
    private function __construct($driver) {
        $this->driver = $driver;
    }
    function open($savePath, $sessionName) {
        return $this->driver->open($savePath, $sessionName);
    }
    function write($sessionId, $data) {
        return $this->driver->write($sessionId, $data);
    }
    function close() {
        return $this->driver->close();
    }
    function destroy($sessionId) {
        if(spSessionFile::clear()){
            return $this->driver->destroy($sessionId);
        }else{
            return FALSE;
        }
    }
    function gc($lifetime) {
        return $this->driver->gc($lifetime);
    }
    function read($sessionId) {
        return $this->driver->read($sessionId);
    }
}