<?php
/**
 * 
 * @author Kason Yang <kasonyang@163.com>
 */
function loadResoucre($name){
    return file_get_contents(APP_RESOURCE_DIR . '/' . $name);
}
function spIncludeFile($paths,$file_name){
    $path_arr=  explode(';', $paths);
    foreach ($path_arr as $p) {
        $file=$p.'/'.$file_name;
        if(file_exists($file)) return include $file;
    }
    throw new spFileNotExistException("unable to include the file $file_name");
}
function spClassAutoLoader($class){
    switch ($class){
        default :
            $class_file_name=$class.'.class.php';
            global $sharpphp_global_var;
            try{
                spIncludeFile($sharpphp_global_var['model_path'], $class_file_name);
            }catch (spFileNotExistException $e){}
            break;
    }
}
function loadVendor($file_name){
    return include SP_BASE_DIR.'/vendor/'.$file_name;
}
function loadDriver($driver_name,$params = NULL){
    if(!class_exists($driver_name)){
        $driver_file = SP_DRIVER_DIR . '/' . $driver_name . '.php';
        if(!is_file($driver_file)){
            throw new Exception('无法装载驱动，驱动文件不存在！');
        }
        include_once $driver_file;
    }
    if($params){
        return new $driver_name($params);
    }else{
        return new $driver_name;
    }
}
function import($name){
    $file_name = $name . '.php';
    global $sharpphp_global_var;
    return spIncludeFile($sharpphp_global_var['import_path'], $file_name);
}

function initSharpPHP(){
    if (!defined('SP_APP_PATH'))
                die('ERROR:SP_APP_PATH undefined!');

    define('SP_BASE_DIR',dirname(__FILE__));
    define('SP_DRIVER_DIR',SP_BASE_DIR . '/drivers');

    spl_autoload_register('spClassAutoLoader');
    include SP_BASE_DIR.'/core/BaseClass.php';

    if(spConfig::get('debug.enable', false)){
        $debug_config = spConfig::get('debug');
        error_reporting($debug_config['error_reporting']);
        unset($debug_config);
    }else{
        error_reporting(0);
    }

    if(spConfig::get('timezone')){
        date_default_timezone_set(spConfig::get('timezone'));
    }



    spSessionManager::register();

    spFront::registerModelPath(SP_APP_PATH . '/widgets/models');
    loadVendor('Daject/Daject.php');
    $databases = spConfig::get('db:');
    foreach($databases['database'] as $db_name => $dbinfo){
        DajectConfig::addDatabase($db_name, $dbinfo['type'], $dbinfo['host'], $dbinfo['user'], $dbinfo['password'], $dbinfo['name'], $dbinfo['charset']);
    }
    $rwdb = explode(',', $databases['default']);
    DajectConfig::setDatabase($rwdb[0], $rwdb[1]);
    DajectConfig::setTablePrefix($databases['tb_prefix']);
    
    $app_path = realpath(SP_APP_PATH);
    define('APP_FILTER_DIR', $app_path . '/filters');
    define('APP_INCLUDE_DIR', $app_path . '/includes');
    define('APP_CACHE_DIR', $app_path . '/cache');
    define('APP_RESOURCE_DIR',$app_path . '/resources');

    spFront::registerModelPath($app_path . '/libs');
    spFront::registerModelPath($app_path . '/models');
    spFront::registerImportPath(APP_INCLUDE_DIR);
}
initSharpPHP();