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

class hooks extends AWS_ADMIN_CONTROLLER{
	public function setup(){
        TPL::assign('menu_list',  $this->fetch_menu_list());
	}

	public function index_action(){
		$page = $_GET['page']?$_GET['page']:1;
		$where = '';
		if ($lists = $this->model()->fetch_page('hooks', $where, 'id asc', $page, 15)){
			$total_rows = $this->model()->found_rows();
		}
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/hooks/'),
			'total_rows' => $total_rows,
			'per_page' => 15
		))->create_links());
        TPL::assign('lists',  $lists);
		TPL::output('admin/hooks/index');
	}

    public function edit_action(){
        $id=intval($_GET['id']);
        if($id){
            $info=$this->model()->fetch_row('hooks','id='.$id);
        }else{
            $info=null;
        }
        TPL::assign('info',  $info);

        TPL::output('admin/hooks/edit');
    }

    public function save_action(){
        $_POST['id']=intval($_POST['id']);
        $_POST['name']=trim($_POST['name']);
        $_POST['function_name']=trim($_POST['function_name']);
        $msg=$_POST['id']?'修改成功':'添加成功';
        unset($_POST['_post_type']);
        if(empty($_POST['name']))
            H::ajax_json_output(AWS_APP::RSM(null, -1,AWS_APP::lang()->_t('钩子名称不可为空')));
        if(empty($_POST['function_name']))
            H::ajax_json_output(AWS_APP::RSM(null, -1,AWS_APP::lang()->_t('函数名称不可为空')));
        if(!$_POST['id']) {
            $_POST['add_time'] = time();
            $ret = $this->model()->insert('hooks', $_POST);
        }else {
            $ret = $this->model()->update('hooks', $_POST, 'id=' . $_POST['id']);
        }

        if($ret!==false)
            H::ajax_json_output(AWS_APP::RSM(null, -1,$msg));
    }

    public  function remove_action(){
        if(count($_POST['ids'])<1)
            H::ajax_json_output(AWS_APP::RSM(null, -1,'请选择要删除的数据'));
        $ids=implode(',',$_POST['ids']);
        $ret=$this->model()->delete('hooks',"id in ($ids)");
        if($ret!==false)
            H::ajax_json_output(AWS_APP::RSM(null, 1,null));
    }

}