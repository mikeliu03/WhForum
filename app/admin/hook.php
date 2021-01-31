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
class hook extends AWS_ADMIN_CONTROLLER
{
	public function setup()
    {
        TPL::assign('menu_list',  $this->fetch_menu_list());
	}

	public function index_action()
    {
		$page = $_GET['page'] ? intval($_GET['page']) : 1;
        $lists = $this->model('hook')->get_hook_list_by_page('', 'id asc', $page);
        $total_rows = $this->model('hook')->found_rows();
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/hook/'),
			'total_rows' => $total_rows,
			'per_page' => 15
		))->create_links());
        TPL::assign('lists',  $lists);
		TPL::output('admin/hook/index');
	}

    public function edit_action()
    {
        $id=intval($_GET['id']);
        if($id){
            $info=$this->model()->fetch_row('hook','id='.$id);
        }else{
            $info=null;
        }
        TPL::assign('info',  $info);
        TPL::output('admin/hook/edit');
    }

    /*保存钩子*/
    public function save_action()
    {
        $id=intval($_POST['id']);
        $name=trim($_POST['name']);
        $intro=trim($_POST['intro']);
        $status=intval($_POST['status']);
        if(empty($name))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1,AWS_APP::lang()->_t('钩子名称不可为空')));
        }
        $data = array(
            'name'=>$name,
            'intro'=>$intro,
            'status'=>$status,
            'system'=>0
        );
        if(!$_POST['id'])
        {
            $data['add_time'] = time();
            $ret = AWS_APP::model()->insert('hook', $data);
        }else {
            $data['update_time'] = time();
            $ret = AWS_APP::model()->update('hook', $data, 'id=' . $id);
        }

        if($ret)
        {
            H::ajax_json_output(AWS_APP::RSM(array('url'=>get_js_url('admin/hook/')), 1,''));
        }
        H::ajax_json_output(AWS_APP::RSM(null, -1,AWS_APP::lang()->_t('钩子保存失败')));
    }

    /*删除钩子*/
    public function remove_action()
    {
        $id = intval($_GET['id']);
        if(!$id)
        {
            H::redirect_msg(AWS_APP::lang()->_t('请选择需要操作的钩子'),get_js_url('admin/hook/'));
        }
        $hook_info = AWS_APP::model()->fetch_row('hook',"id = ".$id);
        if($hook_info['system'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('该钩子是系统钩子无法删除'),get_js_url('admin/hook/'));
        }
        if(AWS_APP::model()->fetch_one('hook_plugins',"hook = '".$hook_info['name']."'"))
        {
            H::redirect_msg(AWS_APP::lang()->_t('该钩子下有关联插件'),get_js_url('admin/hook/'));
        }
        if(!AWS_APP::model()->delete('hook',"id = ".$id))
        {
            H::redirect_msg(AWS_APP::lang()->_t('钩子删除失败'),get_js_url('admin/hook/'));
        }
        H::redirect_msg(AWS_APP::lang()->_t('钩子删除成功'),get_js_url('admin/hook/'));
    }
}