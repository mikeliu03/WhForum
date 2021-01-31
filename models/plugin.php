<?php
if (!defined('IN_ANWSION')) {
    die;
}

class plugin_class extends AWS_MODEL
{
    protected static $plugins;

    /**
     * 获取插件列表
     * @param bool $installed
     * @param bool $update
     * @return array|bool
     * @throws \Zend_Exception
     */
    public function getList($installed = false,$update=false)
    {
        if (self::$plugins && !$update){
            return self::$plugins;
        }else {
            $plugin_dir = ADDON_PATH;
            $dirs = array_map('basename', glob($plugin_dir . '*', GLOB_ONLYDIR));
            if ($dirs === false || !file_exists($plugin_dir))
            {
                return false;
            }
            $where = $installed ? 'state = 1' : '';
            $plugins=$this->fetch_all('plugins', $where);
            foreach ($plugins as $key => $value) {
                $plugin_dir_name     =   $this->get_plugin_dir($value['name']);
                if (!is_dir(ADDON_PATH . "{$plugin_dir_name}")  || !file_exists(ADDON_PATH . "{$plugin_dir_name}/config.php")) {
                    unset($plugins[$key]);
                    continue;
                }
                $info=require_once(ADDON_PATH . "{$plugin_dir_name}/config.php");
                $version=str_replace('.', '', $info['version']);
                $db_version=str_replace('.', '', $value['version']);
                if ($version>$db_version)
                {
                    $plugins[$key]['upgrade']=true;
                    $plugins[$key]['up_version']=$info['version'];
                } else {
                    $plugins[$key]['up_version']=false;
                }
            }
            self::$plugins = $plugins;
            return self::$plugins;
        }
    }

    /**
     * 安装插件
     * @param string $plugin_name 插件名称
     * @param bool $old_install 兼容老版本插件安装方法
     * @return bool
     * @throws \Zend_Exception
     */
    public function install($plugin_name,$old_install=true)
    {
        $plugin_dir     =   $this->get_plugin_dir($plugin_name);
        $class=ADDON_PATH . $plugin_dir.'/'.$plugin_name.'.php';
        if(file_exists($class))
        {
            require_once($class);
            if(!class_exists($plugin_name))
            {
                return false;
            }
        }
        $plugins_class = new $plugin_name();
        $class_method = get_class_methods($plugins_class);
        /*插件安装前操作方法*/
        if(in_array('install_before',$class_method))
        {
            $plugins_class->install_before();
        }

        $plugins_vars = get_object_vars($plugins_class);
        $hooks = $plugins_vars['hooks'] ? $plugins_vars['hooks'] : [];
        $_sql= file_get_contents(ADDON_PATH . "{$plugin_dir}/install.sql");
        $config = require(ADDON_PATH . "{$plugin_dir}/config.php");
        $plugins_info = $config;
        //判断是否是tab选项配置
        if(array_key_exists('config',$config))
        {
            $config = json_encode($config['config']);
            unset($plugins_info['config']);
        }

        if(array_key_exists('group',$config))
        {
            $groups = array();
            $groups['group'] = $config['group'];
            $config = json_encode($groups);
            unset($plugins_info['group']);
        }

        /*执行sql语句*/
        if($_sql)
        {
            $sql= explode(";\r", str_replace(array('[#DB_PREFIX#]', "\n"), array($this->get_prefix(), "\r"), $_sql));
            foreach (array_filter($sql) as $_value)
            {
                if(!empty(trim($_value)))
                {
                    AWS_APP::model()->query($_value.';');
                }
            }
        }

        if(!$old_install)
        {
            $plugins_class->install($plugin_name);
        }

        /*钩子处理*/
        if($hooks)
        {
            foreach ($hooks as $k=>$v)
            {
                /*添加到钩子表*/
                if(!$hook_id = AWS_APP::model()->fetch_one('hook','id',"name = '" . $v."'"))
                {
                    AWS_APP::model()->insert('hook',array(
                        'name'=>$v,
                        'intro'=>'插件【'.$plugin_name.'】自带钩子',
                        'system' =>0,
                        'status'=>1,
                        'source'=>$plugin_name,
                        'add_time'=>time()
                    ));
                }

                /*插入插件钩子关联表*/
                if(!$plugin_hook_id = AWS_APP::model()->fetch_one('hook_plugins','id',"hook = '" . $v."' AND plugins = '".$plugin_name."'"))
                {
                    AWS_APP::model()->insert('hook_plugins',array(
                        'hook'=>$v,
                        'plugins'=>$plugin_name,
                        'status'=>1,
                        'add_time'=>time()
                    ));
                }else{
                    AWS_APP::model()->update('hook_plugins',array(
                        'status'=>1,
                        'update_time'=>time()
                    ),'id = '.$plugin_hook_id);
                }
            }
        }
        
        $this->update('plugins',[
            'state'=>1,
            'config'=>$config,
            'title'=>$plugins_info['title'],
            'intro'=>$plugins_info['intro'],
            'author'=>$plugins_info['author'],
            'version'=>$plugins_info['version']
        ],'name="'.$plugin_name.'"');
        $this->getList(true,true);
        //插件安装后操作
        if(in_array('install_after',$class_method))
        {
            return $plugins_class->install_after();
        }
        return true;
    }

    /**
     * 卸载插件
     * @param $plugin_name
     * @param bool $old_install 兼容老版本插件卸载方法
     * @return bool
     * @throws \Zend_Exception
     */
    public function uninstall($plugin_name,$old_install = true)
    {
        $plugin_dir     =   $this->get_plugin_dir($plugin_name);
        $class = ADDON_PATH . $plugin_dir.'/'.$plugin_name.'.php';
        if(file_exists($class))
        {
            require_once($class);
            if(!class_exists( $plugin_name))
            {
                return false;
            }
        }
        $plugins_class = new  $plugin_name;
        $class_method = get_class_methods($plugins_class);
        //插件卸载前执行方法
        if(in_array('uninstall_before',$class_method))
        {
            return $plugins_class->uninstall_before();
        }

        /*执行卸载sql*/
        $_sql= file_get_contents(ADDON_PATH . "{$plugin_dir}/uninstall.sql");
        if($_sql)
        {
            $sql= explode(";\r", str_replace(array('[#DB_PREFIX#]', "\n"), array($this->get_prefix(), "\r"), $_sql));
            foreach ($sql as $_value) {
              if(!empty(trim($_value)))
                $this->query($_value.';');
            }
        }
        if(!$old_install)
        {
            $plugins_class->uninstall($plugin_name);
        }
        $plugins_vars = get_object_vars($plugins_class);
        $hooks = $plugins_vars['hooks'] ? $plugins_vars['hooks'] :[];
        /*钩子处理*/
        if($hooks)
        {
            foreach ($hooks as $k=>$v)
            {
                /*删除插件钩子*/
                if($hook_id = AWS_APP::model()->fetch_one('hook','id',"name = '" . $v."' AND source = '".$plugin_name."'"))
                {
                    AWS_APP::model()->delete('hook','id = '.$hook_id);
                    $this->model('hook')->get_hook_info_by_hook_name($v,true);
                }

                /*更新插件钩子关联表*/
                if($plugin_hook_id = AWS_APP::model()->fetch_one('hook_plugins','id',"hook = '" . $v."' AND plugins = '".$plugin_name."'"))
                {
                    AWS_APP::model()->delete('hook_plugins','id = '.$plugin_hook_id);
                    $this->model('hook')->get_plugins_hook_list(true);
                }
            }
        }
        $this->update('plugins',['state'=>2],'name="'.$plugin_name.'"');
        $this->getList(false,true);
        //插件卸载后处理方法
        if(in_array('uninstall_after',$class_method))
        {
            return $plugins_class->uninstall_after();
        }
        return true;
    }

    /**
     * 启用插件
     * @param $plugin_name
     * @param bool $old_install 兼容老版本插件启用方法
     * @return bool
     * @throws \Zend_Exception
     */
    public function enable($plugin_name,$old_install=true)
    {
        $plugin_dir     =   $this->get_plugin_dir($plugin_name);
        $class = ADDON_PATH . $plugin_dir.'/'.$plugin_name.'.php';
        if(file_exists($class))
        {
            require_once($class);
            if(!class_exists( $plugin_name))
            {
                return false;
            }
        }
        $plugins_class = new  $plugin_name;
        if(!$old_install) {
            $plugins_class->enable($plugin_name);
        }else{
            $plugins_class->enable();
        }
        $plugins_vars = get_object_vars($plugins_class);
        $hooks = $plugins_vars['hooks'] ? $plugins_vars['hooks'] :[];
        if($hooks)
        {
            foreach ($hooks as $k=>$v)
            {
                /*更新到钩子表*/
                if($hook_id = AWS_APP::model()->fetch_one('hook','id',"name = '" . $v."' AND source = '".$plugin_name."'"))
                {
                    AWS_APP::model()->update('hook',array('status'=>1, 'update_time'=>time()),'id = '.$hook_id);
                    $this->model('hook')->get_hook_info_by_hook_name($v,true);
                }
                /*插入插件钩子关联表*/
                if($plugin_hook_id = AWS_APP::model()->fetch_one('hook_plugins','id',"hook = '" . $v."' AND plugins = '".$plugin_name."'"))
                {
                    AWS_APP::model()->update('hook_plugins',array(
                        'status'=>1,
                        'update_time'=>time()
                    ),'id = '.$plugin_hook_id);
                    $this->model('hook')->get_plugins_hook_list(true);
                }
            }
        }
        $this->update('plugins',['state'=>1],'name="'.$plugin_name.'"');
        $this->getList(false,true);
        return true;
    }

    /**
     * 禁用插件
     * @param $plugin_name
     * @param bool $old_install 兼容老版本插件卸载方法
     * @return int
     * @throws \Zend_Exception
     */
    public function disable($plugin_name,$old_install=true)
    {
        $plugin_dir     =   $this->get_plugin_dir($plugin_name);
        $class = ADDON_PATH . $plugin_dir.'/'.$plugin_name.'.php';
        if(file_exists($class))
        {
            require_once($class);
            if(!class_exists( $plugin_name))
            {
                return false;
            }
        }
        $plugins_class = new  $plugin_name;
        if(!$old_install)
        {
            $plugins_class->disable( $plugin_name);
        }else{
            $plugins_class->disable();
        }
        $plugins_vars = get_object_vars($plugins_class);
        $hooks = $plugins_vars['hooks'] ? $plugins_vars['hooks'] :[];
        if($hooks)
        {
            foreach ($hooks as $k=>$v)
            {
                /*更新到钩子表*/
                if($hook_id = AWS_APP::model()->fetch_one('hook','id',"name = '" . $v."' AND source = '".$plugin_name."'"))
                {
                    AWS_APP::model()->update('hook',array('status'=>0, 'update_time'=>time()),'id = '.$hook_id);
                    $this->model('hook')->get_hook_info_by_hook_name($v,true);
                }
                /*插入插件钩子关联表*/
                if($plugin_hook_id = AWS_APP::model()->fetch_one('hook_plugins','id',"hook = '" . $v."' AND plugins = '".$plugin_name."'"))
                {
                    AWS_APP::model()->update('hook_plugins',array(
                        'status'=>0,
                        'update_time'=>time()
                    ),'id = '.$plugin_hook_id);
                    $this->model('hook')->get_plugins_hook_list(true);
                }
            }
        }
        $this->update('plugins',['state'=>0],'name="'.$plugin_name.'"');
        $this->getList(false,true);
       return true;
    }

    /**
     * 更新插件
     * @param $plugin_name
     * @param $version
     * @return bool
     * @throws \Zend_Exception
     */
    public function upgrade($plugin_name,$version)
    {
        $plugin_dir     =   $this->get_plugin_dir($plugin_name);
        $_sql= file_get_contents(ADDON_PATH . "{$plugin_dir}/upgrade.sql");
        $config = require(ADDON_PATH . "{$plugin_dir}/config.php");
        $plugins_info = $config;
        if(array_key_exists('config',$config))
        {
            $config = json_encode($config['config']);
            unset($plugins_info['config']);
        }

        if(array_key_exists('group',$config))
        {
            $groups = array();
            $groups['group'] = $config['group'];
            $config = json_encode($groups);
            unset($plugins_info['group']);
        }

         if($_sql)
         {
             $sql= explode(";\r", str_replace(array('[#DB_PREFIX#]', "\n"), array($this->get_prefix(), "\r"), $_sql));
             foreach (array_filter($sql) as $_value)
             {
                 if(!empty(trim($_value)))
                 {
                     $this->query($_value.';');
                 }
             }
         }

         return $this->update('plugins',[
             'state'=>1,
             'config'=>$config,
             'title'=>$plugins_info['title'],
             'intro'=>$plugins_info['intro'],
             'author'=>$plugins_info['author'],
             'version'=>$version
         ],'name="'.$plugin_name.'"');
    }

    /**
     * 获取插件信息
     * @param $plugin
     * @param bool $is_config
     * @return array|mixed
     */
    public function get_info($plugin,$is_config=false)
    {
        $info = get_hook_info($plugin);
        return $is_config ? get_hook_config($plugin) : $info;
    }

    /**
     * 获取新增插件
     * @param int $type
     * @return bool|int
     * @throws \Exception
     */
    public function get_new_plugin($type=1)
    {
        $plugins=$this->query_all('select GROUP_CONCAT("wc_",name) as name from '.$this->get_table('plugins') );
        $plugins=explode(',',$plugins[0]['name']);
        $plugin_dir = ADDON_PATH;
        $dirs = array_map('basename', glob($plugin_dir . '*', GLOB_ONLYDIR));
        if ($dirs === FALSE || !file_exists($plugin_dir))
        {
            return FALSE;
        }
        $count=0;
        $dir_lists = array_flip(array_flip($dirs));
        foreach ($dir_lists as $key => $value)
        {
            if(!in_array($value,$plugins) and strstr($value, 'wc_'))
            {
                if(!file_exists(ADDON_PATH . "{$value}/config.php"))
                {
                    continue;
                }
                $count+=1;
                if($type!=1)
                {
                    $config= include(ADDON_PATH . "{$value}/config.php");
                    if($config['group'])
                    {
                        $_config['group'] = $config['group'];
                    }
                    else{
                        $_config = $config['config'];
                    }
                    $config['config']=json_encode($_config,JSON_UNESCAPED_UNICODE);
                    unset($config['group']);
                    $this->insert('plugins',$config);
                }
            }
        }
        return $type==1 ? $count : true;
    }

    public function get_plugin_dir($name)
    {
        return 'wc_'.$name;
    }

    public function design_plugins($data,$hooks=[],$config=[])
    {
        $plugins_path = ADDON_PATH.'wc_'.$data['name'].'/';
        if (is_dir($plugins_path))
        {
            return false;
        }
        // 生成插件主目录
        mkdir($plugins_path, 0777, true);

        // 生成插件信息
        $this->mkConfig($plugins_path, $data,$config);

        // 生成sql文件
        $this->mkSql($plugins_path);

        // 生成钩子文件
        $this->mkHook($data['name'],$plugins_path, $hooks);

        // 生成目录结构
        $this->mkDir($plugins_path, ['view']);

        // 生成说明文档
        return true;
    }

    /**
     * 生成目录结构
     * @param string $path 插件完整路径
     * @param array $list 目录列表
     */
    public function mkDir($path = '', $list = [])
    {
        foreach ($list as $dir)
        {
            if (!is_dir($path . $dir)) {
                // 创建目录
                mkdir($path . $dir, 0755, true);
            }
        }
    }

    /**
     * 生成SQL文件
     * @param string $path 插件完整路径
     */
    public function mkSql($path = '')
    {
        file_put_contents($path . 'install.sql', "");
        file_put_contents($path . 'uninstall.sql', "");
        //file_put_contents($path . 'upgrade.sql', "");
    }

    /**
     * 生成钩子文件
     * @param $plugins_name
     * @param $path
     * @param array $hooks
     */
    public function mkHook($plugins_name,$path , $hooks = [])
    {
        $params = '$params=[]';
        $plugin_info = '$'.$plugins_name.'_plugin_info';
        $plugin_config =  '$'.$plugins_name.'_plugin_config';
        $plugin_info_this = '$this->'.$plugins_name.'_plugin_info';
        $plugin_config_this = '$this->'.$plugins_name.'_plugin_config';
        $hook_text = '$hooks';
        $hook_template = $hook_arr ='';
        if(isset($hooks) && !empty($hooks))
        {
            foreach ($hooks as $k=>$v)
            {
                $hook_arr .= '"'.$v.'",';
                $hook_template .=<<<INFO
    /**
     * {$v}钩子方法
     * @param $params
     */
    public function {$v}($params)
    {
        echo '这是[{$plugins_name}]插件[{$v}]钩子的示例！<br>';
    }
INFO;
            }
        }
        $code = <<<INFO
<?php
/**
 * {$plugins_name}插件
 */
class {$plugins_name} extends AWS_CONTROLLER
{
     protected $plugin_info;
     protected $plugin_config;
     /*定义使用的钩子*/
     public $hook_text = [{$hook_arr}];
    
     public function __construct()
    {
        parent::__construct();
        $plugin_info_this = get_hook_info('{$plugins_name}');
        $plugin_config_this = get_plugins_config('{$plugins_name}');
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
    
    {$hook_template}
}
INFO;
        file_put_contents($path.$plugins_name.'.php', $code);
    }

    /**
     * 生成插件基本信息
     * @param string $path 插件完整路径
     * @param string $data 插件基本信息
     * @param string $config 插件配置信息
     */
    public function mkConfig($path = '', $data = [],$config=[])
    {
        if (empty($config)) {
            $config = [];
        }
        // 美化数组格式
        $config = var_export($config, true);
        $config = str_replace(['array (', ')'], ['[', ']'], $config);
        $config = preg_replace("/(\s*?\r?\n\s*?)+/", "\n", $config);
        $code = <<<INFO
<?php
/**
 * {$data['name']}插件基本信息
 */

 return array (
  'config'=>[
    
  ],
  'name' => '{$data['name']}',
  'title' => '{$data['title']}',
  'intro' => '{$data['intro']}',
  'author' => '{$data['author']}',
  'version' => '{$data['version']}',
  'state' => 2,
);
INFO;
        file_put_contents($path.'config.php', $code);
    }
}