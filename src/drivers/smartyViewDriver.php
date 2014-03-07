<?php
/**
 * 
 * @version 1.0.1
 * @author Kason Yang <kasonyang@163.com>
 */
class smartyViewDriver implements spViewDriverInterface {
    
    private function getSmarty() {
        if (!class_exists('Smarty')) {
            loadVendor('Smarty/Smarty.class.php');
            Smarty::$_DATE_FORMAT = '%Y-%m-%d %H:%M:%S';
        }
        $smarty = new Smarty();
        $smarty->left_delimiter = '{{';
        $smarty->right_delimiter = '}}';
        $smarty->default_modifiers = array('escape:"html"');
        $smarty->compile_dir = SP_APP_PATH . '/cache/smarty/templates_c/';
        $smarty->cache_dir = SP_APP_PATH . '/cache/smarty/cache/';
        self::registerPlugins($smarty);
        return $smarty;
    }

    static function registerPlugins(Smarty $smarty) {
        $smarty->registerPlugin('function', 'url', array(__CLASS__, 'function_url'));
        $smarty->registerPlugin('function', 'link', array(__CLASS__, 'function_link'));
        $smarty->registerPlugin('block', 'form', array(__CLASS__, 'block_form'));

        $plugins_dir = SP_APP_PATH . '/plugins/smarty';
        if (file_exists($plugins_dir)) {
            $smarty->addPluginsDir($plugins_dir);
        }
    }

    static function function_url($params) {
        $arr = isset($params['url']) ? spURL::innerurl2array($params['url']) : spRequest::getParameter();
        if (isset($params['append']))
            $arr = array_merge($arr, spURL::query2array($params['append']));
        $url = spURL::formatArray($arr);
        if ($params['return']) {
            $u = new spStandardURL($url);
            $u->setQuery('return', spRequest::getURL());
            $url = $u->build();
        }
        return $url;
    }

    static function function_link($params) {
        if (isset($params['path'])) {
            $path = $params['path'];
            if (substr($path, -1) != '/')
                $path.='/';
        }
        return spRequest::getBaseURL() . $path . $params['url'];
    }

    static function block_form($params, $content, Smarty_Internal_Template $template, &$repeat) {
        if (!$repeat) {
            $ret = '<form';
            if ($params) {
                foreach ($params as $key => $value) {
                    $ret .= ' ' . $key . '="' . $value . '"';
                }
            }
            $ret .= ">\n" . $content . '<input type="hidden" name="_SharpPHP_Form_Token" value="' . spToken::generate('sp_form') . '" /></form>';
            return $ret;
        }
    }

    function render($template_file, $vars) {
        $smarty = $this->getSmarty();
        $smarty->assign($vars);
        $smarty->setTemplateDir(dirname($template_file));
        return $smarty->fetch($template_file);
    }

}