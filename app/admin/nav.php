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

class nav extends AWS_ADMIN_CONTROLLER
{
	public function setup(){
        TPL::assign('menu_list',  $this->fetch_menu_list());
	}

	public function index_action()
    {
		$page = $_GET['page']?$_GET['page']:1;
		$where = '';
		if ($lists = $this->model()->fetch_page('nav', $where, 'id asc', $page, 15)){
			$total_rows = $this->model()->found_rows();
		}
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/nav/'),
			'total_rows' => $total_rows,
			'per_page' => 15
		))->create_links());
        TPL::assign('lists',  $lists);
		TPL::output('admin/nav/index');
	}

    public function edit_action()
    {
        $id=intval($_GET['id']);
        if($id){
            $info=$this->model()->fetch_row('nav','id='.$id);
        }else{
            $info=null;
        }
        TPL::assign('info',  $info);

        TPL::output('admin/nav/edit');
    }

    public function save_action()
    {
        $_POST['id']=intval($_POST['id']);
        $_POST['title']=trim($_POST['title']);
        $msg=$_POST['id']?'修改成功':'添加成功';
        unset($_POST['_post_type']);
        if(empty($_POST['title']))
            H::ajax_json_output(AWS_APP::RSM(null, -1,AWS_APP::lang()->_t('标题不能为空')));
        if(!$_POST['id']){
            $ret=$this->model()->insert('nav',$_POST);
            $this->model('admin')->get_front_nav_list(true);
        }else{
            $ret=$this->model()->update('nav',$_POST,'id='.$_POST['id']);
            $this->model('admin')->get_front_nav_list(true);
        }
        //若设置为首页，则将其他菜单首页设置清空
        if($_POST['is_index'])
        {
            $ret=$this->model()->update('nav',['is_index'=>0],'id<>'.$_POST['id']);
        }
        if($ret!==false)
            H::ajax_json_output(AWS_APP::RSM(null, -1,$msg));
    }

    public  function remove_action()
    {
        if(count($_POST['ids'])<1){
            H::ajax_json_output(AWS_APP::RSM(null, -1,'请选择要删除的数据'));
        }
        $ids=implode(',',$_POST['ids']);
        $ret=$this->model()->delete('nav',"id in ($ids)");
        $this->model('admin')->get_front_nav_list(true);
        if($ret!==false)
        {
            H::ajax_json_output(AWS_APP::RSM(null, 1,null));
        }
    }

}