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
class column extends AWS_MOBILE_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black';
        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();
        if (get_setting('enable_column') != 'Y')
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站未启用专栏功能'), '/');
        }
        $this->crumb(AWS_APP::lang()->_t('专栏'), '/column/');
    }

    /*专栏首页*/
    public function index_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/column/');
        }
        $column_info = $this->model('column')->fetch_column_list($this->user_id , 1 ,6 ,$_GET['sort']);
        //首页专栏
        $article_list = $this->model('posts')->get_hot_posts('article',0,null,$_GET['day'],1,6);
        TPL::assign('article_list', $article_list);
        TPL::assign('column_list', $column_info);
        TPL::output('m/column/index');
    }

    /*专栏详情*/
    public function details_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/column/details/'.$_GET['id']);
        }
        if (!$column_info = $this->model('column')->get_column_by_id($_GET['id']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('专栏不存在'), '/');
        }
        if ($column_info['is_verify'] != 1 && $this->user_id != $column_info['uid'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('专栏未启用或者未审核'), '/');
        }
        $this->crumb($column_info['column_name'], '/column/details/' . $column_info['column_id']);
        $column_info['user_info']=$this->model('account')->get_user_info_by_uid($column_info['uid']);
        $column_info['article_sum_count'] = $this->model('column')->get_column_views_num($column_info['column_id']);
        $column_info['article_num'] = $this->model('column')->get_column_article_num($column_info['column_id']);

        TPL::assign('column_info', $column_info);
        TPL::assign('has_focus_column', $this->model('column')->has_focus_column($this->user_id, $column_info['column_id']));
        TPL::assign('user_actions', $this->model('actions')->get_user_actions($column_info['uid'], 5, implode(',', array(
            ACTION_LOG::ADD_QUESTION,
            ACTION_LOG::ANSWER_QUESTION,
            ACTION_LOG::ADD_REQUESTION_FOCUS,
            ACTION_LOG::ADD_AGREE,
            ACTION_LOG::ADD_TOPIC,
            ACTION_LOG::ADD_TOPIC_FOCUS,
            ACTION_LOG::ADD_ARTICLE
        )), $this->user_id));
        TPL::output('m/column/details');
    }

    /*申请专栏*/
    public function apply_action()
    {
        if($_GET['id'])
        {
            if (!$column_info = $this->model('column')->get_column_by_id($_GET['id'])){
                H::redirect_msg(AWS_APP::lang()->_t('指定专栏不存在'),'/');
            }
            if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] and !$this->user_info['permission']['edit_column'] and $column_info['uid']!=$this->user_id)
            {
                H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个专栏'),'/');
            }
            if ($column_info['is_verify'] == 0) {
                H::redirect_msg(AWS_APP::lang()->_t('审核中的专栏无法编辑'),'/');
            }
            TPL::assign('column', $column_info);
        }else{
            if (!$this->user_info['permission']['publish_column'])
            {
                H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限申请专栏'));
            }
        }
        TPL::import_js('js/fileupload.js');
        TPL::output('m/column/apply');
    }

    /*专栏列表*/
    public function list_action()
    {
        TPL::output('m/column/list');
    }
}