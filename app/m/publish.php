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
class publish extends AWS_MOBILE_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'] = array();
        return $rule_action;
    }

    public function index_action()
    {
        if ($_GET['id'])
        {
            if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
            {
                H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
            }

            if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_question'] AND $question_info['published_uid'] != $this->user_id)
            {
                H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个问题'), '/question/' . $question_info['question_id']);
            }
        }
        else if (!$this->user_info['permission']['publish_question'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布问题'));
        }
        else if ($this->is_post() AND $_POST['question_detail'])
        {
            $question_info = array(
                'question_content' => htmlspecialchars($_POST['question_content']),
                'question_detail' => htmlspecialchars(remove_xss($_POST['question_detail'])),
                'category_id' => intval($_POST['category_id'])
            );
        }
        else
        {
            $draft_content = $this->model('draft')->get_data(0, 'question', $this->user_id);
            $question_info = array(
                'question_content' => htmlspecialchars(base64_decode($_GET['question_content'])),
                'question_detail' => html_entity_decode($draft_content['message'])
            );
        }
        if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y' AND !$_GET['id']  and get_setting('integral_system_config_new_question')<0)
        {
            H::redirect_msg(AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作'));
        }

        if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $question_info['published_uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
        {
            TPL::assign('attach_access_key', md5($this->user_id . time()));
        }

        if (!$question_info['category_id'])
        {
            $question_info['category_id'] = $_GET['category_id'] ? intval($_GET['category_id']) : 0;
        }
        if($question_info['category_id'])
        {
            $question_info['category_name'] = AWS_APP::model()->fetch_one('category','title','id = '.$question_info['category_id']);
        }

        if (get_setting('category_enable') == 'Y')
        {
            $question_category_list = $this->model('system')->fetch_category('question', 0);
            $question_category = array();
            foreach ($question_category_list as $key=>$val)
            {
                $question_category[$key]['id'] = $val['id'];
                $question_category[$key]['value'] = $val['title'];
            }
            TPL::assign('question_category_json', json_encode(array_values($question_category)));
        }

        if ($modify_reason = $this->model('question')->get_modify_reason())
        {
            TPL::assign('modify_reason', $modify_reason);
        }

        TPL::assign('human_valid', human_valid('question_valid_hour'));
        TPL::assign('category_reward', $_GET['category']);
        $_SESSION['ssid'] = rand(1000,999999);
        TPL::assign('ssid', $_SESSION['ssid']);
        $question_info['question_detail']=htmlspecialchars_decode($question_info['question_detail']);
        TPL::assign('question_info', $question_info);
        TPL::output('m/publish/index');
    }

    public function article_action()
    {
        if ($_GET['id'])
        {
            if (!$article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
            {
                H::redirect_msg(AWS_APP::lang()->_t('指定文章不存在'));
            }

            if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_article'] AND $article_info['uid'] != $this->user_id)
            {
                H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个文章'), '/article/' . $article_info['id']);
            }

            $draft_content = $this->model('draft')->get_data($article_info['id'], 'article', $this->user_id);
            if($draft_content){
                $article_info['message'] = htmlspecialchars_decode($draft_content['message']);
            }else{
                $article_info['message'] = htmlspecialchars_decode($article_info['message']);
            }
            TPL::assign('article_topics', $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article'));
        }
        else if (!$this->user_info['permission']['publish_article'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布文章'));
        }
        else if ($this->is_post() AND $_POST['message'])
        {
            $article_info = array(
                'title' => htmlspecialchars_decode($_POST['title']),
                'message' => htmlspecialchars_decode($_POST['message']),
                'category_id' => intval($_POST['category_id'])
            );
        }
        else
        {
            $draft_content = $this->model('draft')->get_data(0, 'article', $this->user_id);
            $article_info =  array(
                'title' => htmlspecialchars_decode($_POST['title']),
                'message' => htmlspecialchars_decode($draft_content['message'])
            );
        }

        if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $article_info['uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
        {
            TPL::assign('attach_access_key', md5($this->user_id . time()));
        }

        if (!$article_info['category_id'])
        {
            $article_info['category_id'] = ($_GET['category_id']) ? intval($_GET['category_id']) : 0;
        }
        if ($article_info['category_id'])
        {
            $article_info['category_name'] = AWS_APP::model()->fetch_one('category','title','id = '.$article_info['category_id']);
        }
        if (!$article_info['column_id'])
        {
            if($column_info = $this->model('column')->get_column_by_id($_GET['column_id']))
            {
                $article_info['column_id'] = $column_info['is_verify']==1 ? intval($_GET['column_id']) : 0;
            }
        }

        if ($article_info['column_id'])
        {
            $article_info['column_name'] = AWS_APP::model()->fetch_one('column','column_name','column_id = '.$article_info['column_id']);
        }

        if (get_setting('category_enable') == 'Y')
        {
            $article_category_list = $this->model('system')->fetch_category('question', 0);
            $article_category = array();
            foreach ($article_category_list as $key=>$val)
            {
                $article_category[$key]['id'] = $val['id'];
                $article_category[$key]['value'] = $val['title'];
            }
            TPL::assign('article_category_json', json_encode(array_values($article_category)));
        }
        $column_list_json = array();
        if($column_list = $this->model('column')->get_column_by_uid($this->user_id))
        {
            foreach ($column_list as $key=>$val)
            {
                $column_list_json[$key]['id'] = $val['column_id'];
                $column_list_json[$key]['value'] = $val['column_name'];
            }
        }
        TPL::assign('column_list',json_encode(array_values($column_list_json)));
        TPL::assign('human_valid', human_valid('question_valid_hour'));
        if (get_setting('upload_enable') == 'Y')
        {
            TPL::import_js('js/fileupload.js');
        }
        TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));
        TPL::assign('article_info', $article_info);
        $_SESSION['ssid'] = rand(1000,999999);
        TPL::assign('ssid', $_SESSION['ssid']);

        run_hook('publish_article_action_hook',['article_info'=>$article_info]);//发起文章页面渲染钩子
        TPL::output('m/publish/article');
    }

    /*选择标签*/
    public function select_topic_action()
    {
        TPL::output('m/publish/select_topic');
    }
}