<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

if (! defined('IN_ANWSION'))
{
	die();
}

class plugin extends AWS_ADMIN_CONTROLLER
{
	public function setup()
    {
		TPL::assign('menu_list', $this->fetch_menu_list());
	}

	/*插件列表*/
	public function index_action()
    {
		$plugins = $this->model('plugin')->getList(false,true);
        $new_plugins_count = $this->model('plugin')->get_new_plugin();

        TPL::assign('list', $plugins);
		TPL::assign('new_plugins_count', $new_plugins_count);
		TPL::output('admin/plugin/index');
	}

	/*安装插件*/
    public function install_action()
    {
        $plugin_name   =  trim($_POST['plugin_name']);
        $ret = $this->model('plugin')->install($plugin_name,false);
        if($ret)
        {
            H::ajax_json_output(AWS_APP::RSM(null, 1, null));
     	}else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, '插件不存在或者已经损坏'));
        }
    }

    /*卸载插件*/
    public function uninstall_action()
    {
        $plugin_name = trim($_POST['plugin_name']);
        $ret = $this->model('plugin')->uninstall($plugin_name,false);

        if($ret)
        {
            H::ajax_json_output(AWS_APP::RSM(null, 1, null));
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, '插件不存在或者已经损坏'));
        }
    }

    /*启用插件*/
    public function enable_action()
    {
        $plugin_name = trim($_POST['plugin_name']);
        $ret = $this->model('plugin')->enable($plugin_name,false);

        if($ret)
        {
            H::ajax_json_output(AWS_APP::RSM(null, 1, null));
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, '插件不存在或者已经损坏'));
        }
    }

    /*禁用插件*/
    public function disable_action()
    {
        $plugin_name = trim($_POST['plugin_name']);
        $ret = $this->model('plugin')->disable($plugin_name,false);

        if($ret)
        {
            H::ajax_json_output(AWS_APP::RSM(null, 1, null));
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, '插件不存在或者已经损坏'));
        }
    }

    /*编辑插件配置*/
    public function config_action()
    {
        $plugin_name = trim($_GET['plugin_name']);
        $info = get_hook_info($plugin_name);
        $config = get_hook_config($plugin_name);
		TPL::assign('configs', $config);
		TPL::assign('info', $info);
		TPL::output('admin/plugin/config');
    }

    /*保存普通配置*/
    public function save_config_action()
    {
        $plugin_name = trim($_POST['plugin_name']);
        $config = $this->model('plugin')->get_info($plugin_name,true);
        foreach ($_POST['config'] as $key => $value)
        {
            if(is_array($value))
            {
                $config[$key]['value'] = implode(',',$value);
            }else{
                $config[$key]['value'] = $value;
            }
        }

        $this->model('plugin')->update('plugins',['config'=>json_encode($config,JSON_UNESCAPED_UNICODE)],'name="'.$plugin_name.'"');
        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('插件配置成功')));
    }

    /*保存分组配置*/
    public function save_tab_config_action()
    {
        $plugin_name = trim($_POST['plugin_name']);
        $config = $this->model('plugin')->get_info($plugin_name,true);
        foreach ($_POST['config'] as $key => $value)
        {
            foreach ($value as $k => $v)
            {
                if(is_array($v))
                {
                    $config['group'][$key]['config'][$k]['value']=implode(',',$v);
                }else{
                    $config['group'][$key]['config'][$k]['value']=$v;
                }
            }
        }
        $this->model('plugin')->update('plugins',['config'=>json_encode($config,JSON_UNESCAPED_UNICODE)],'name="'.$plugin_name.'"');
        H::ajax_json_output(AWS_APP::RSM(null, -1, '配置成功'));        
    }

    /*获取最新插件*/
    public function get_new_plugin_action()
    {
        $ret = $this->model('plugin')->get_new_plugin(2);
        if($ret)
        {
            H::ajax_json_output(AWS_APP::RSM(null, 1, null));
        }
    }

    /*升级插件*/
    public function upgrade_action()
    {
        $plugin_name = trim($_POST['plugin_name']);
        $version = trim($_POST['version']);
        $plugin_dir = $this->model('plugin')->get_plugin_dir(trim($_POST['plugin_name']));
        $class = ADDON_PATH . $plugin_dir.'/'.$plugin_name.'.php';
        if(file_exists($class))
        {
            require_once($class);
            if(!class_exists($plugin_name))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, '插件不存在或者已经损坏'));
            }
        }

        $ret = $this->model('plugin')->upgrade($plugin_name,$version);
        if($ret)
        {
            H::ajax_json_output(AWS_APP::RSM(null, 1, null));
        }
    }

    /*设计插件*/
    public function design_action()
    {
        $hooks = AWS_APP::model()->fetch_all('hook','','id ASC');

        TPL::assign('hooks',$hooks);
        TPL::output('admin/plugin/design');
    }

    /*保存插件*/
    public function save_plugin_action()
    {
        $name = trim($_POST['name']);
        $title = trim($_POST['title']);
        $author = $_POST['author'] ? trim($_POST['author']) : '插件开发者';
        $version = $_POST['version'] ? trim($_POST['version']) : '1.0.0';
        $intro = trim($_POST['intro']);
        $hooks = $_POST['hooks'];

        if(!$name)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写插件标识')));
        }

        if(AWS_APP::model()->fetch_one('plugins','name',"name = '".$name."'"))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('插件标识已存在')));
        }

        if(!$title)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写插件名称')));
        }

        $data = array(
            'name'=>$name,
            'title'=>$title,
            'author'=>$author,
            'version'=>$version,
            'intro'=>$intro
        );

        $this->model('plugin')->design_plugins($data,$hooks);

        H::ajax_json_output(AWS_APP::RSM(array('url'=>get_js_url('/admin/plugin/')), 1, AWS_APP::lang()->_t('操作成功')));
    }

    /*导入插件*/
    public function import_action()
    {
        TPL::output('admin/plugin/import');
    }

    public function save_import_action()
    {
        if ($_FILES['package']['name'])
        {
            $package_name = $_FILES['package']['name'];

            $str_name1 = substr($package_name,0,-4);//去掉后缀
            $str_name2 = substr($str_name1,0,3);

            if($str_name2 != 'wc_')
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('插件名称命名不规范')));
            }

            $plugin_dir = ADDON_PATH;
            $dirs = array_map('basename', glob($plugin_dir . '*', GLOB_ONLYDIR));

            //判断插件名称是否存在
            foreach ($dirs as $key => $va) 
            {
                if($va == $str_name1)
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('插件名称已存在')));
                    break;
                }
            }

            AWS_APP::upload()->initialize(array(
                'allowed_types' => 'zip',
                'upload_path' => $plugin_dir,
                'is_image' => TRUE,
                'max_size' => get_setting('upload_avatar_size_limit'),
                'file_name' => $package_name,
                'encrypt_name' => FALSE
            ))->do_upload('package');

            if (AWS_APP::upload()->get_error())
            {
                switch (AWS_APP::upload()->get_error())
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                    break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));
                    break;

                    case 'upload_invalid_filesize':
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));
                    break;
                }
            }

            if (!$upload_data = AWS_APP::upload()->data())
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
            }

            $file = $plugin_dir.$package_name;

            if (!file_exists($file))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('解压文件不存在')));
            }

            //zip 解压
            $zip = new ZipArchive();
            $openRes = $zip->open($file);

            if ($openRes === TRUE) 
            {
                $zip->extractTo($plugin_dir);
                $zip->close();

                //解压后删除上传的压缩包
                @unlink($file);

                $plugin_dir_name = $plugin_dir.$str_name1;

                $model_name = 0;
                $dh = opendir($plugin_dir_name);

                // 查看文件夹下是否有model文件
                while ($file = readdir($dh)) 
                {
                    if($file != "." && $file != "..") 
                    {
                        $fullpath = $plugin_dir_name."/".$file;

                        if(!is_dir($file) && substr($file,-10) == '_model.php') 
                        {
                            $model_name = 1;
                        }
                    }
                }

                if (!is_dir("{$plugin_dir_name}")  || !file_exists("{$plugin_dir_name}/config.php") || !$model_name) 
                {
                    //删除解压缩后的文件夹
                    deldir($plugin_dir_name);
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('插件格式不规范')));
                }

                H::ajax_json_output(AWS_APP::RSM(array('url'=>get_js_url('admin/plugin/')), 1, AWS_APP::lang()->_t('操作成功')));
            }
            else
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件解压失败')));
            }
            
        }
        else
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请上传代码压缩包')));
        } 
    }
}