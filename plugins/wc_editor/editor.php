<?php
/**
 * 编辑器插件
 */
class editor extends AWS_CONTROLLER
{
    protected $plugin_info;
    protected $plugin_config;
    public $hooks=['editor'];
    public function __construct()
    {
        parent::__construct();
        $this->plugin_info = get_hook_info('editor');
        $this->plugin_config = get_hook_config('editor');
    }

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;        //此处方法可以自行实现
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        return true;
    }

    public function editor($param)
    {
        $type = $this->plugin_info['state'] == 1 ? $this->plugin_config['type']['value'] : '';
        PLUTPL::assign('path', str_replace('?', '', base_url()) . '/plugins/wc_editor/static');
        PLUTPL::assign('type', $type);
        PLUTPL::assign('user_info', $this->user_info);
        PLUTPL::assign('config', $this->plugin_config);
        PLUTPL::assign('param', $param);
        PLUTPL::output("editor/init");
    }

    public function upload_file()
    {
        if (!$this->user_info['permission']['upload_attach'] and !($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']) and $_GET['cat'] != 'avatar')
        {
            H::ajax_json_output(['error' => 1, 'message' => '没有上传权限']);
        }
        if (get_hook_info('osd')['state'] == 1 and get_hook_config('osd')['group']['base']['config']['status']['value'] != 'no')
        {
            $ret = hook('osd', 'upload_files', ['type' => 'wangeditor', 'field' => 'upload', 'cat' => $_GET['cat']]);
            H::ajax_json_output($ret);
        } else {
            $files = $_FILES['upload'];
            $ext = strtolower(pathinfo(@$files['name'], PATHINFO_EXTENSION));
            $config = get_setting('allowed_upload_types');
            $config = explode(',', $config);
            if (!in_array($ext, $config))
            {
                H::ajax_json_output(['error' => 1, 'msg' => '文件类型不符合']);
            }
            if ($this->plugin_config['fileMaxSize']['value'] * 1024 * 1024 < $files['size'])
            {
                H::ajax_json_output(['error' => 1, 'msg' => '文件大小超出限制']);
            }
            $_save_path = '/uploads/' . $_GET['cat'] . '/' . date('Ymd');
            $save_path = base_url() . $_save_path;
            $save_path = str_replace(http_type() . $_SERVER['HTTP_HOST'], '', $save_path);
            $path = '.' . $_save_path;
            // 判断文件类型
            // 判断是否存在上传到的目录
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            // 生成唯一的文件名
            $fileName = md5(uniqid(microtime(true), true)) . '.' . $ext;
            // 将文件名拼接到指定的目录下
            $destName = $path . "/" . $fileName;
            // 进行文件移动
            if (!move_uploaded_file($files['tmp_name'], $destName))
            {
                H::ajax_json_output(['uploaded' => 0, 'error' => ['message' => '文件上传失败'],]);
            }
            $img_mimes = array(
                'image/gif',
                'image/jpeg',
                'image/png',
            );
            $is_image =  (in_array($files['type'], $img_mimes, TRUE)) ? TRUE : FALSE;
            $this->model('publish')->add_attach($_GET['cat'], $files['name'], $_GET['attach_access_key'], time(), basename($fileName),$is_image);
            H::ajax_json_output(['errno' => 0, 'data' => ["$files[name]" => $save_path . "/" . $fileName]]);
        }
    }

    public function upload_video()
    {
        if (!$this->user_info['permission']['upload_attach'] and !($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']) and $_GET['cat'] != 'avatar')
        {
            H::ajax_json_output(['error' => 1, 'message' => '没有上传权限']);
        }
        if (get_hook_info('osd')['state'] == 1 and get_hook_config('osd')['group']['base']['config']['status']['value'] != 'no')
        {
            $ret = hook('osd', 'upload_files', ['type' => 'wangeditor', 'field' => 'upload', 'cat' => $_GET['cat'],'method'=>'video']);
            H::ajax_json_output($ret);
        } else {
            $files = $_FILES['upload'];
            $ext = strtolower(pathinfo(@$files['name'], PATHINFO_EXTENSION));
            $config = get_setting('allowed_upload_types');
            $config = explode(',', $config);
            if (!in_array($ext, $config))
            {
                H::ajax_json_output(['error' => 1, 'msg' => '文件类型不符合']);
            }
            if ($this->plugin_config['fileMaxSize']['value'] * 1024 * 1024 < $files['size'])
            {
                H::ajax_json_output(['error' => 1, 'msg' => '文件大小超出限制']);
            }
            $_save_path = '/uploads/' . $_GET['cat'] . '/' . date('Ymd');
            $save_path = base_url() . $_save_path;
            $save_path = str_replace(http_type() . $_SERVER['HTTP_HOST'], '', $save_path);
            $path = '.' . $_save_path;
            // 判断文件类型
            // 判断是否存在上传到的目录
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            // 生成唯一的文件名
            $fileName = md5(uniqid(microtime(true), true)) . '.' . $ext;
            // 将文件名拼接到指定的目录下
            $destName = $path . "/" . $fileName;
            // 进行文件移动
            if (!move_uploaded_file($files['tmp_name'], $destName))
            {
                H::ajax_json_output(['uploaded' => 0, 'error' => ['message' => '文件上传失败'],]);
            }
            $img_mimes = array(
                'image/gif',
                'image/jpeg',
                'image/png',
            );
            $is_image =  (in_array($files['type'], $img_mimes, TRUE)) ? TRUE : FALSE;
            $this->model('publish')->add_attach($_GET['cat'], $files['name'], $_GET['attach_access_key'], time(), basename($fileName),$is_image);
            H::ajax_json_output(['errno' => 0, 'data' => [$save_path . "/" . $fileName]]);
        }
    }

    public function upload_mkfile()
    {
        if (!$this->user_info['permission']['upload_attach'] and !($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']) and $_GET['cat'] != 'avatar')
        {
            H::ajax_json_output(['error' => 1, 'message' => '没有上传权限']);
        }
        if (get_hook_info('osd')['state'] == 1 and get_hook_config('osd')['group']['base']['config']['status']['value'] != 'no')
        {
            $ret = hook('osd', 'upload_files', ['type' => 'markdown', 'field' => 'editormd-image-file', 'cat' => $_GET['cat']]);
            H::ajax_json_output($ret);
        } else {
            $files = $_FILES['editormd-image-file'];
            $ext = strtolower(pathinfo(@$files['name'], PATHINFO_EXTENSION));
            $config = get_setting('allowed_upload_types');
            $config = explode(',', $config);
            if (!in_array($ext, $config))
            {
                H::ajax_json_output(['error' => 1, 'msg' => '文件类型不符合']);
            }
            if ($this->plugin_config['fileMaxSize']['value'] * 1024 * 1024 < $files['size']) {
                H::ajax_json_output(['error' => 1, 'msg' => '文件大小超出限制']);
            }
            $_save_path = '/uploads/' . $_GET['cat'] . '/' . date('Ymd');
            $save_path = base_url() . $_save_path;
            $save_path = str_replace(http_type() . $_SERVER['HTTP_HOST'], '', $save_path);
            $path = '.' . $_save_path;
            // 判断文件类型
            // 判断是否存在上传到的目录
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            // 生成唯一的文件名
            $fileName = md5(uniqid(microtime(true), true)) . '.' . $ext;
            // 将文件名拼接到指定的目录下
            $destName = $path . "/" . $fileName;
            // 进行文件移动
            if (!move_uploaded_file($files['tmp_name'], $destName))
            {
                H::ajax_json_output(['success' => 0, 'message' => '文件上传失败']);
            }
            H::ajax_json_output(['success' => 1, 'message ' => '', 'url' => $save_path . "/" . $fileName]);
        }
    }
}