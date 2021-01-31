<?php
class AWS_PLUGIN
{
    private static $hooks = [];
    private static $pluginsList=[];
    protected static $init;

    /**
     * AWS_PLUGIN constructor.
     * @throws \Zend_Exception
     */
    public function __construct()
    {
        if(self::$init) return true;
        self::$init = true;
        if(get_setting('db_version', false)>=20200407)
        {
            $hookPlugins = AWS_APP::model('hook')->get_plugins_hook_list();
            // run_hook全局钩子
            if ($hookPlugins)
            {
                foreach ($hookPlugins as $key=>$value)
                {
                    self::add_hook($value['hook'],$value['plugins']);
                }
            }
        }

        /*hook全局插件*/
        $plugins = AWS_APP::model('plugin')->getList(true);
        if($plugins)
        {
            static $class_list;
            static $file_list;
            foreach($plugins as $plugin)
            {
                $class_file=ADDON_PATH .'wc_'.$plugin['name'].'/'.$plugin['name'].'.php';
                $config_file=ADDON_PATH .'wc_'.$plugin['name'].'/config.php';
                if(!$file_list[$class_file] || !$file_list[$config_file])
                {
                    $file_list[$class_file] = file_exists($class_file);
                    $file_list[$config_file] = file_exists($config_file);
                }
                if ($file_list[$class_file] && $file_list[$config_file] && !$class_list[$plugin['name']])
                {
                    require_once($class_file);
                    $class =  $plugin['name'];
                    if (class_exists($class) && get_hook_info($plugin['name']))
                    {
                        $class_list[$class]=new $class();
                        $cls = $class_list[$class];
                        $methods=get_class_methods($class);
                        foreach ($methods as $key => $value)
                        {
                            $cls->user_id = AWS_APP::user()->get_info('uid');
                            $cls->user_info = AWS_APP::model('account')->get_user_info_by_uid($cls->user_id,TRUE);
                            $user_group = AWS_APP::model('account')->get_user_group($cls->user_info['group_id'], $cls->user_info['reputation_group']);
                            $cls->user_info['group_name'] = $user_group['group_name'];
                            $cls->user_info['permission'] = $user_group['permission'];
                            AWS_APP::session()->permission =  $cls->user_info['permission'];
                            if($cls->user_info['is_del'] == 1){
                                H::redirect_msg('用户已被管理员删除.');
                            }
                            if ($cls->user_info['forbidden'] == 1)
                            {
                                AWS_APP::model('account')->logout();
                                H::redirect_msg(AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录'), '/');
                            }
                            else
                            {
                                PLUTPL::assign('user_id', $cls->user_id);
                                PLUTPL::assign('user_info', $cls->user_info);
                            }
                            if ($cls->user_id and ! $cls->user_info['permission']['human_valid'])
                            {
                                unset(AWS_APP::session()->human_valid);
                            }
                            else if ($cls->user_info['permission']['human_valid'] and ! is_array(AWS_APP::session()->human_valid))
                            {
                                AWS_APP::session()->human_valid = array();
                            }
                            self::add($plugin['name'], $value, $cls);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $plugins 插件名称
     * @param object $name
     * @param string $func
     */
    public static function add($plugins,$name,$func)
    {
        self::$pluginsList[$plugins][$name] = $func;
    }

    /**
     * @param string $plugins 插件的名称
     * @param string $method 插件的方法
     * @param mixed $data 插件的方法
     * @return mixed
     */
    public static function trigger($plugins,$method,$data=[])
    {
        foreach (self::$pluginsList[$plugins] as $k => $v)
        {
            if($k==$method)
            {
               return $v->$k($data);
            }
        }
    }

    /**
     * 动态添加行为扩展到某个标签
     * @param $hook
     * @param $plugins
     * @return bool
     */
    public static function add_hook($hook, $plugins)
    {
        isset(self::$hooks[$hook]) || self::$hooks[$hook] = [];
        if(in_array($plugins,self::$hooks[$hook]))
        {
            return false;
        }
        self::$hooks[$hook][] = $plugins;
    }

    /**
     * 获取钩子信息
     * @access public
     * @param  string $hook 插件位置(留空获取全部)
     * @return array
     */
    public static function get($hook)
    {
        if (!empty($hook)) {
            return array_key_exists($hook, self::$hooks) ? self::$hooks[$hook] : [];
        }
    }

    /**
     * 监听钩子
     * @access public
     * @param  string $hook    钩子名称
     * @param  mixed  $params 传入参数
     * @return array|mixed
     */
    public static function listen($hook, &$params = null)
    {
        static $results = [];
        foreach (self::get($hook) as $key => $name)
        {
            if(get_hook_info($name))
            {
                $results[$key] = self::trigger($name, $hook, $params);
            }else{
                continue;
            }
        }
    }
}