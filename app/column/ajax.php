<?php
define('IN_AJAX', TRUE);
if (!defined('IN_ANWSION'))
{
    die;
}

class ajax extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'] = array(
            'list',
            'article_list',
            'follow_column',
            'fetch_column_list',
            'get_column_articles'
        );
        return $rule_action;

    }
    public function setup()
    {
        HTTP::no_cache_header();
    }
    public function list_action()
    {
    	if ($_GET['per_page'])
		{
			$per_page = intval($_GET['per_page']);
		}
		else
		{
			$per_page = 6;
		}

        if (!$_GET['sort'])
        {
            $_GET['sort'] = 'sum';
        }
        if($_GET['sort']=='my')
        {
            if ($column_info = $this->model('column')->get_my_column_page($this->user_id,1,6))
            {
                foreach ($column_info as $key=>$column)
                {
                    $nums = $this->model('column')->column_info_nums($column['column_id']);
                    $column_info[$key]['article_count'] = $nums['article_count'];
                    $column_info[$key]['view_count'] = $nums['view_count'];
                    $column_info[$key]['vote_count'] = $nums['vote_count'];
                }
            };
        }else{
            $column_info = $this->model('column')->fetch_column_list($this->user_id,$_GET['page']? :0 , $per_page ,$_GET['sort']);
        }

        /** 最新 */
        TPL::assign('column_info', $column_info);
        if(is_mobile())
        {
            $total = $this->model('column')->found_rows();
            TPL::assign('total', ceil($total/$per_page));
            TPL::output('m/ajax/column_list');
        }else{
            TPL::output('column/ajax/column_list');
        }
    }

    public function article_list_action()
	{
		if ($_GET['per_page'])
		{
			$per_page = intval($_GET['per_page']);
		}
		else
		{
			$per_page = 6;
		}

	    $article_list = $this->model('article')->get_articles_list( null,$_GET['page'], $per_page,  'add_time desc', null, false);

	    foreach ($article_list as $key => $value) {
              $article_list[$key]['user'] = $this->model('account')->get_user_info_by_uid($value['uid']);
        }

	    TPL::assign('article_list', $article_list);
	    TPL::output('column/ajax/article_list');
	}

	public function delete_column_action(){
        if (!$column_id=$_POST['column_id']) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('未选择要删除的专栏')));
        }
        if(!$column_info = $this->model('column')->get_column_by_id($column_id)){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('未找到该专栏')));
        }
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_column'] AND $column_info['uid'] != $this->user_id)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限删除该专栏')));
        }
        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }
        if ($this->user_id != $column_info['uid'])
        {
            $this->send_delete_message($column_info['uid'], $column_info['column_name'], $column_info['column_description']);
        }

        $this->model('column')->delete_column($column_id);
        H::ajax_json_output(AWS_APP::RSM(null,1,null));
    }

    public function send_delete_message($uid, $title, $message)
    {
        $delete_message = AWS_APP::lang()->_t('你申请的专栏 %s 已被管理员删除', $title);
        $delete_message .= "\r\n----- " . AWS_APP::lang()->_t('专栏简介') . " -----\r\n" . $message;
        $delete_message .= "\r\n-----------------------------\r\n";
        $delete_message .= AWS_APP::lang()->_t('如有疑问, 请联系管理员');

        $this->model('email')->action_email('QUESTION_DEL', $uid, get_js_url('/inbox/'), array(
            'question_title' => $title,
            'question_detail' => $delete_message
        ));
        return true;
    }

    public function logo_upload_action()
    {
        if(get_hook_info('osd')['state']==1 and get_plugins_config('osd')['base']['status']!='no')
        {
                $ret=hook('osd','upload_files',['cat'=>'column','field'=>'aws_upload_file']);
                echo htmlspecialchars(json_encode(array(
                    'success' => true,
                    'thumb' => $ret['pic']
                )), ENT_NOQUOTES);
            }else{
                AWS_APP::upload()->initialize(array(
                    'allowed_types' => 'jpg,jpeg,png,gif',
                    'upload_path' => get_setting('upload_dir') . '/column',
                    'is_image' => TRUE,
                    'max_size' => get_setting('upload_avatar_size_limit'),
                ))->do_upload('aws_upload_file');
                if (AWS_APP::upload()->get_error())
                {
                    switch (AWS_APP::upload()->get_error())
                    {
                        default:
                            die("{'error':'错误代码: " . AWS_APP::upload()->get_error() . "'}");
                            break;

                        case 'upload_invalid_filetype':
                            die("{'error':'文件类型无效'}");
                            break;

                        case 'upload_invalid_filesize':
                            die("{'error':'文件尺寸过大, 最大允许尺寸为 " . get_setting('upload_avatar_size_limit') .  " KB'}");
                            break;
                        case 'upload_file_exceeds_limit':
                            die("{'error':'文件尺寸超出服务器限制'}");
                            break;
                    }
                }

                if (! $upload_data = AWS_APP::upload()->data())
                {
                    die("{'error':'上传失败, 请与管理员联系'}");
                }

                H::ajax_json_output(array('success' => true, 'thumb' => '/uploads/column/'.$upload_data['file_name']));
            }
    }

    public function apply_action()
    {
        if (!$this->user_info['permission']['publish_column'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你所在用户组没有权限申请专栏')));
        }
        if (!trim($_POST['logo_img'])) {
            H::ajax_json_output(AWS_APP::RSM(null,-1,AWS_APP::lang()->_t('专栏图片必须上传')));
        }
        $column_name = $_POST['name'];
        $column_description = $_POST['description'];
        $column_pic = $_POST['logo_img'];
        $post_hash = $_POST['post_hash'];
        if (!$column_name) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("请输入专栏名称")));
        }
        if (!$column_description) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("请输入专栏简介")));
        }
        if (cjk_strlen($column_description) > 60) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("专栏简介字数不得超过60")));
        }
        if (!$column_pic) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("请上传专栏封面")));
        }
        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($post_hash))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $column_id=$this->model('column')->apply_column($column_name, $column_description, $column_pic, $this->user_id);
        $url=is_mobile() ? get_js_url('/m/column/details/'.$column_id):get_js_url('/column/my/');
        H::ajax_json_output(AWS_APP::RSM(['url'=>$url], 1, AWS_APP::lang()->_t('申请成功')));
    }

    public function edit_apply_action()
    {
        if (!$column_info = $this->model('column')->get_column_by_id($_POST['id'])){
            H::ajax_json_output(AWS_APP::RSM(null,-1,AWS_APP::lang()->_t('指定专栏不存在')));
        }
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] and !$this->user_info['permission']['edit_column'] and $column_info['uid']!=$this->user_id)
        {
            H::ajax_json_output(AWS_APP::RSM(null,-1,AWS_APP::lang()->_t('你没有权限编辑这个专栏')));
        }
        if ($column_info['is_verify'] == 0) {
            H::ajax_json_output(AWS_APP::RSM(null,-1,AWS_APP::lang()->_t('审核中的专栏无法编辑')));
        }
        $column_name = $_POST['name'];
        $column_description = $_POST['description'];
        $column_pic = $_POST['logo_img'];
        $post_hash = $_POST['post_hash'];
        if (!trim($_POST['logo_img'])) {
            H::ajax_json_output(AWS_APP::RSM(null,-1,AWS_APP::lang()->_t('专栏图片必须上传')));
        }
        if (!$column_name) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("请输入专栏名称")));
        }
        if (!$column_description) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("请输入专栏简介")));
        }
        if (cjk_strlen($column_description) > 60) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("专栏简介字数不得超过60")));
        }
        if (!$column_pic) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t("请上传专栏封面")));
        }
        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($post_hash))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $this->model('column')->edit_apply_column($column_info['column_id'],$column_name, $column_description, $column_pic);
        H::ajax_json_output(AWS_APP::RSM(['url'=>get_js_url('/column/my/')], 1, AWS_APP::lang()->_t('编辑成功')));
    }

    public function focus_column_list_action()
    {
        $column_list = $this->model('column')->get_focus_column_list($this->user_id, calc_page_limit(intval($_GET['page']) , get_setting('index_per_page')));
        if(is_mobile())
        {
            foreach ($column_list as $key=>$val)
            {
                $column_list[$key]['has_focus_column'] = $this->model('column')->has_focus_column($this->user_id, $val['column_id']);
            }
            $total = $this->model('column')->get_focus_column_list($this->user_id, intval($_GET['page']) . ', '.get_setting('index_per_page'),1);
            TPL::assign('column_info', $column_list);
            TPL::assign('total', ceil($total/get_setting('index_per_page')));
            TPL::output('m/ajax/column_list');
        }else{
            TPL::assign('column_list', $column_list);
            TPL::output('topic/ajax/focus_column_list');
        }
    }

    public function focus_column_action()
    {
        H::ajax_json_output(AWS_APP::RSM(array(
            'type' => $this->model('column')->add_focus_column($this->user_id, intval($_POST['column_id']))
        ), '1', null));
    }

    public function load_my_column_page_action()
    {
        $page = intval($_GET['page']);
        if ($columns = $this->model('column')->get_my_column_page($this->user_id,$page,5)) {
            foreach ($columns as $key=>$column) {
                $nums = $this->model('column')->column_info_nums($column['column_id']);
                $columns[$key]['article_count'] = $nums['article_count'];
                $columns[$key]['view_count'] = $nums['view_count'];
                $columns[$key]['vote_count'] = $nums['vote_count'];
            }
        };
        TPL::assign('columns', $columns);
        TPL::output('column/ajax/my_list');
    }

    public function set_recommend_action(){
        $column_id=intval($_POST['column_id']);
        $recommend=intval($_POST['recommend']);
        $_recommend=$recommend==1?0:1;
        $ret=$this->model('column')->update('column',['recommend'=>$_recommend],'column_id='.$column_id);
        if($ret!==false)
            H::ajax_json_output(AWS_APP::RSM(null, 1,null));
    }

    public function fetch_column_list_action(){
        $page=intval($_GET['page']);
        $data['list']=  $this->model('column')->fetch_column_list($this->user_id , $page , 6 ,'sum');
        $data['pages']=ceil($this->model('column')->count('column','is_verify=1')/6);
        H::ajax_json_output(AWS_APP::RSM($data, 1,null));
    }

    public function get_my_column_page_action(){
        $is_verify=intval($_GET['is_verify']);
        $page=intval($_GET['page']);
        $data['list']=$this->model('column')->fetch_page('column', "uid = " . intval($this->user_id)." and is_verify=".$is_verify,'add_time DESC',$page,6);
        $data['pages']=ceil($this->model('column')->count('column','is_verify=1 and uid='.$this->user_id)/6);
        foreach ($data['list'] as $key => $value) {
                $nums = $this->model('column')->column_info_nums($value['column_id']);
                $data['list'][$key]['article_count'] = $nums['article_count'];
                $data['list'][$key]['view_count'] = $nums['view_count'];
                $data['list'][$key]['vote_count'] = $nums['vote_count'];
        }
        H::ajax_json_output(AWS_APP::RSM($data, 1,null));
    }

    public function get_column_articles_action(){
        $column_id=intval($_GET['column_id']);
        $sort=trim($_GET['sort']);
        $page=intval($_GET['page']);
        $order=$sort=='new'?'add_time desc':'(comments+views+votes) desc';
        $data['list']=$this->model('column')->fetch_user_article_list($column_id,$page,10,false,$order);
        $data['pages']=ceil($this->model('column')->count('article','is_del!=1 and column_id='.$column_id)/10);
        foreach ($data['list'] as $key => $value) {
            $data['list'][$key]['time']=date_friendly($value['add_time']);
            $data['list'][$key]['user_name']=$this->model('account')->get_name($value['uid']);
        }
        H::ajax_json_output(AWS_APP::RSM($data, 1,null));

    }
}
