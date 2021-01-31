<?php
/**
 * mobile_editor插件
 */
class mobile_editor extends AWS_CONTROLLER
{
     protected $mobile_editor_plugin_info;
     protected $mobile_editor_plugin_config;
     /*定义使用的钩子*/
     public $hooks = ["mobile_editor","m_publish_page"];
    
     public function __construct()
    {
        parent::__construct();
        $this->mobile_editor_plugin_info = get_hook_info('mobile_editor');
        $this->mobile_editor_plugin_config = get_plugins_config('mobile_editor');
    }
    
    /**
     * 安装前的业务处理，可在此方法实现，默认返回true
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 卸载前的业务处理，可在此方法实现，默认返回true
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }
    
    /**
     * 插件启用方法
     */
    public function enable()
    {
        return true;
    }

    /**
     * 插件禁用方法
     */
    public function disable()
    {
        return true;
    }

    /**
     * mobile_editor钩子方法
     * @param array $params
     * @throws \Zend_Exception
     */
    public function mobile_editor($params=[])
    {
        PLUTPL::assign('params',$params);
        PLUTPL::assign('static', str_replace('?', '', base_url()) . '/plugins/wc_mobile_editor/static');
        PLUTPL::output('mobile_editor/init');
    }

    /**
     * 发起页面底部js
     * @param array $params
     * @throws \Zend_Exception
     */
    public function m_publish_page($params=[])
    {
        if($params['area']=='footer')
        {
            PLUTPL::output('mobile_editor/publish_page');
        }
    }
}