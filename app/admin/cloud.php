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
if (!defined('IN_ANWSION')) {
    die();
}

/**
 * WeCenter云平台
 * Class auth
 */
class cloud extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        TPL::assign('menu_list', $this->fetch_menu_list());
    }

    /*在线升级信息*/
    public function index_action()
    {
        try {
            $version_info = $this->model('cloud')->check_last_version();
        } catch (\Exception $e) {
            $version_info = false;
        }
        TPL::assign('version_info', $version_info);
        TPL::output('admin/cloud/index');
    }

    /*在线升级*/
    public function upgrade_action()
    {
        $upgrade_info =$this->model('cloud')->check_upgrade_info();
        TPL::assign('upgrade_info',$upgrade_info);
        TPL::output('admin/cloud/upgrade');
    }

    /*解绑用户*/
    public function unbind_action()
    {
        HTTP::wc_request(HTTP::$api_url.HTTP::$unbind_url,'post',array('url'=>base_url()));
        H::ajax_json_output(AWS_APP::RSM(['url'=>get_js_url('admin/cloud/index/')],1,AWS_APP::lang()->_t('解绑成功')));
    }

    /*下载升级文件*/
    public function download_action()
    {
        $version = $_POST['version'];
        $download_url = $this->model('cloud')->download_upgrade_file($version);
        H::ajax_json_output($download_url);
    }

    public function download_file_action()
    {
        $action = $_POST['action'];
        $remote_url  = $_POST['curl_file_url'];
        $file_size   = $_POST['curl_file_size'];
        $file_name   = $_POST['curl_file_name'];
        $tmp_path    = ROOT_PATH . "tmp/upgrade/".$file_name;

        if (!$remote_url || !$file_size || !$file_name)
        {
            exit('发生错误：非法操作');
        }

        switch ($action)
        {
            case 'prepare-download':
                // 下载缓存文件夹
                $download_cache = ROOT_PATH . "tmp/upgrade";
                if (!is_dir($download_cache)) {
                    if (false === mkdir($download_cache)) {
                        exit('创建下载缓存文件夹失败，请检查目录权限。');
                    }
                }
                file_put_contents($tmp_path,''); // 这里保存临时文件地址
                H::ajax_json_output(compact('remote_url', 'tmp_path', 'file_size'));
                break;
            case 'start-download':
                // 这里检测下 tmp_path 是否存在
                try {
                    set_time_limit(0);
                    touch($tmp_path);
                    // 做些日志处理
                    if ($fp = fopen($remote_url, "rb"))
                    {
                        if (!$download_fp = fopen($tmp_path, "wb")) {
                            exit;
                        }
                        while (!feof($fp)) {
                            if (!file_exists($tmp_path)) {
                                // 如果临时文件被删除就取消下载
                                fclose($download_fp);
                                exit;
                            }
                            fwrite($download_fp, fread($fp, 1024 * 8 ), 1024 * 8);
                        }
                        fclose($download_fp);
                        fclose($fp);
                    } else {
                        exit;
                    }
                } catch (Exception $e){
                    @unlink($tmp_path);
                    exit('发生错误：'.$e->getMessage());
                }
                H::ajax_json_output(compact('tmp_path'));
                break;

            case 'get-file-size':
                // 这里检测下 tmp_path 是否存在
                if (file_exists($tmp_path))
                {
                    // 返回 JSON 格式的响应
                    H::ajax_json_output(['size' => filesize($tmp_path)]);
                }
                break;
            default:
                # code...
                break;
        }
    }

    /*解压文件 执行sql*/
    public function unzip_file_action()
    {
        $file_name = $_POST['file_name'];
        $version = $_POST['version'];
        $version_build = $_POST['version_build'];
        $log_id = intval($_POST['log_id']);
        if (!$file_name || !$version || !$version_build)
        {
            exit('发生错误：非法操作');
        }
        $file = ROOT_PATH . "tmp/upgrade/".$file_name;
        if (!file_exists($file))
        {
            exit('发生错误：升级文件不存在');
        }

        $outPath = ROOT_PATH;
        $zip = load_class('Services_PclZip');
        $zip->PclZip($file);
        if(!$zip->extract(PCLZIP_OPT_PATH, $outPath, PCLZIP_OPT_REPLACE_NEWER))
        {
            exit('发生错误：升级解压失败');
        }

        $sql_file = ROOT_PATH . 'sql/upgrade.sql';
        //执行sql升级
        if (file_exists($sql_file))
        {
            $sql_query = file_get_contents($sql_file);
            if (trim($sql_query) && $sql_error = $this->model('cloud')->run_query($sql_query))
            {
                exit('发生错误：'.$sql_error);
            }
            //复制sql到升级目录
            copy($sql_file,ROOT_PATH.'app/upgrade/db/'.$version.'.sql');
            unlink($sql_file);
        }

        //更新系统版本号
        $this->model('setting')->set_vars(array('db_version' => $version_build));
        //更新升级状态
        HTTP::wc_request(HTTP::$update_download_status_url,'POST',['log_id'=> $log_id]);
        H::ajax_json_output(['msg' => '升级成功，当前版本'.$version]);
    }
}