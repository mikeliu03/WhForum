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


if (!defined('IN_ANWSION'))
{
    die;
}

class tools extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'));
        }
        @set_time_limit(0);
    }

    public function index_action()
    {
        $this->crumb(AWS_APP::lang()->_t('系统维护'), 'admin/tools/');

        TPL::assign('menu_list', $this->fetch_menu_list());
        TPL::output('admin/tools');
    }

    public function init_action()
    {
        $per_page = intval($_POST['per_page']);
        H::redirect_msg(AWS_APP::lang()->_t('正在准备...'), get_js_url('/admin/tools/' . $_POST['action'] . '/page-1__per_page-' . $per_page));
    }

    public function cache_clean_action()
    {
        AWS_APP::cache()->clean();

        H::redirect_msg(AWS_APP::lang()->_t('缓存清理完成'), '/admin/tools/');
    }

    public function update_users_reputation_action()
    {
        if ($this->model('reputation')->calculate((($_GET['page'] * $_GET['per_page']) - $_GET['per_page']), $_GET['per_page']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('正在更新用户威望') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_users_reputation/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户威望更新完成'), '/admin/tools/');
        }
    }

    public function bbcode_to_html_action()
    {
       switch ($_GET['type']) {

           case 'answer':
                if ($answer_list = $this->model('question')->fetch_page('answer', null, 'answer_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    $count= AWS_APP::cache()->get('answer_count');
                    if(!$count){
                        $count = $this->model('question')->count('answer');
                        AWS_APP::cache()->set('answer_count',$count,3600*10);
                    }
                    $pi=ceil($count/$_GET['per_page']);
                    foreach ($answer_list as $key => $val)
                    {
                        $this->model('answer')->update_answer_by_id($val['answer_id'], array(
                            'answer_content' => htmlspecialchars(FORMAT::parse_attachs((FORMAT::parse_bbcode($val['answer_content']))))
                        ));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换回答内容为HTML') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page'].'/'.$pi), '/admin/tools/bbcode_to_html/page-' . ($_GET['page'] + 1) . '__type-answer__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_html/page-1__type-topic__per_page-' . $_GET['per_page']);
                }
               break;
            case 'topic':
                if ($topic_list = $this->model('topic')->get_topic_list(null, 'topic_id ASC', $_GET['per_page'], $_GET['page']))
                {
                    $count= AWS_APP::cache()->get('topic_count');
                    if(!$count){
                        $count = $this->model('question')->count('topic');
                        AWS_APP::cache()->set('topic_count',$count,3600*10);
                    }
                    $pi=ceil($count/$_GET['per_page']);
                    foreach ($topic_list as $key => $val)
                    {
                        $this->model('topic')->update('topic', array(
                            'topic_description' => htmlspecialchars(FORMAT::parse_attachs((FORMAT::parse_bbcode($val['topic_description']))))
                        ), 'topic_id = ' . intval($val['topic_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换话题内容为HTML') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page'].'/'.$pi), '/admin/tools/bbcode_to_html/page-' . ($_GET['page'] + 1) . '__type-topic__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_html/page-1__type-article__per_page-' . $_GET['per_page']);
                }
            break;
           case 'article':
               if ($article_list = $this->model('article')->fetch_page('article', null, 'id ASC', $_GET['page'], $_GET['per_page']))
               {
                   $count= AWS_APP::cache()->get('article_count');
                   if(!$count){
                       $count = $this->model('article')->count('article');
                       AWS_APP::cache()->set('article_count',$count,3600*10);
                   }
                   $pi=ceil($count/$_GET['per_page']);
                   foreach ($article_list as $key => $val)
                   {
                       $this->model('article')->update('article', array(
                           'message' => htmlspecialchars(FORMAT::parse_attachs((FORMAT::parse_bbcode($val['message']))))
                       ), 'id = ' . intval($val['id']));
                   }

                   H::redirect_msg(AWS_APP::lang()->_t('正在转换文章内容为HTML') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page'].'/'.$pi), '/admin/tools/bbcode_to_html/page-' . ($_GET['page'] + 1) . '__type-article__per_page-' . $_GET['per_page']);
               }
               else
               {
                   H::redirect_msg(AWS_APP::lang()->_t('BBCode转HTML 转换完成'), '/admin/tools/');
               }
           break;
            default:
                if ($questions_list = $this->model('question')->fetch_page('question', '', 'question_id desc', $_GET['page'], $_GET['per_page']))
                {
                    $count= AWS_APP::cache()->get('question_count');
                    if(!$count){
                        $count = $this->model('question')->count('question');
                        AWS_APP::cache()->set('question_count',$count,3600*10);
                    }
                    $pi=ceil($count/$_GET['per_page']);
                    foreach ($questions_list as $key => $val)
                    {
                        $this->model('question')->update('question', array(
                            'question_detail' => htmlspecialchars(FORMAT::parse_attachs((FORMAT::parse_bbcode($val['question_detail']))))
                        ), 'question_id = ' . intval($val['question_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换问题内容为HTML') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page'].'/'.$pi), '/admin/tools/bbcode_to_html/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_html/page-1__type-answer__per_page-' . $_GET['per_page']);
                }
            break;
       }
    }

    public function bbcode_to_markdown_action()
    {
        switch ($_GET['type'])
        {
            default:
                if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($questions_list as $key => $val)
                    {
                        $this->model('question')->update('question', array(
                            'question_detail' => FORMAT::bbcode_2_markdown($val['question_detail'])
                        ), 'question_id = ' . intval($val['question_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换问题内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_markdown/page-1__type-answer__per_page-' . $_GET['per_page']);
                }
            break;

            case 'answer':
                if ($answer_list = $this->model('question')->fetch_page('answer', null, 'answer_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($answer_list as $key => $val)
                    {
                        $this->model('answer')->update_answer_by_id($val['answer_id'], array(
                            'answer_content' => FORMAT::bbcode_2_markdown($val['answer_content'])
                        ));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换回答内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__type-answer__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_markdown/page-1__type-topic__per_page-' . $_GET['per_page']);
                }
            break;

            case 'topic':
                if ($topic_list = $this->model('topic')->get_topic_list(null, 'topic_id ASC', $_GET['per_page'], $_GET['page']))
                {
                    foreach ($topic_list as $key => $val)
                    {
                        $this->model('topic')->update('topic', array(
                            'topic_description' => FORMAT::bbcode_2_markdown($val['topic_description'])
                        ), 'topic_id = ' . intval($val['topic_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换话题内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__type-topic__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_markdown/page-1__type-topic__per_page-' . $_GET['per_page']);
                }
            break;

            case 'article':
                if ($article_list = $this->model('article')->fetch_page('article', null, 'id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($article_list as $key => $val)
                    {
                        $this->model('article')->update('article', array(
                            'message' => FORMAT::bbcode_2_markdown($val['message'])
                        ), 'id = ' . $val['id']);
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换文章内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__type-article__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('BBCode 转换完成'), '/admin/tools/');
                }
                break;
        }
    }

    public function markdown_to_bbcode_action()
    {
        switch ($_GET['type'])
        {
            default:
                if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($questions_list as $key => $val)
                    {
                        $this->model('question')->update('question', array(
                            'question_detail' => FORMAT::markdown_2_bbcode($val['question_detail'])
                        ), 'question_id = ' . intval($val['question_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换问题内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/markdown_to_bbcode/page-1__type-answer__per_page-' . $_GET['per_page']);
                }
            break;

            case 'answer':
                if ($answer_list = $this->model('question')->fetch_page('answer', null, 'answer_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($answer_list as $key => $val)
                    {
                        $this->model('answer')->update_answer_by_id($val['answer_id'], array(
                            'answer_content' => FORMAT::markdown_2_bbcode($val['answer_content'])
                        ));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换回答内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__type-answer__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/markdown_to_bbcode/page-1__type-article__per_page-' . $_GET['per_page']);
                }
            break;

            case 'article':
                if ($article_list = $this->model('article')->fetch_page('article', null, 'id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($article_list as $key => $val)
                    {
                        $this->model('article')->update('article', array(
                            'message' => FORMAT::markdown_2_bbcode($val['message'])
                        ), 'id = ' . $val['id']);
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换文章内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__type-article__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/markdown_to_bbcode/page-1__type-topic__per_page-' . $_GET['per_page']);
                }
            break;

            case 'topic':
                if ($topic_list = $this->model('topic')->get_topic_list(null, 'topic_id ASC', $_GET['per_page'], $_GET['page']))
                {
                    foreach ($topic_list as $key => $val)
                    {
                        $this->model('topic')->update('topic', array(
                            'topic_description' => FORMAT::markdown_2_bbcode($val['topic_description'])
                        ), 'topic_id = ' . intval($val['topic_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换话题内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__type-topic__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('Markdown 转换完成'), '/admin/tools/');
                }
            break;
        }
    }

    public function update_question_search_index_action()
    {
        if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
        {
            foreach ($questions_list as $key => $val)
            {
                $this->model('search_fulltext')->push_index('question', $val['question_content'], $val['question_id']);

                $this->model('posts')->set_posts_index($val['question_id'], 'question', $val);
            }

            H::redirect_msg(AWS_APP::lang()->_t('正在更新问题搜索索引') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_question_search_index/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('搜索索引更新完成'), '/admin/tools/');
        }
    }

    public function update_article_search_index_action()
    {
        if ($articles_list = $this->model('question')->fetch_page('article', null, 'id ASC', $_GET['page'], $_GET['per_page']))
        {
            foreach ($articles_list as $key => $val)
            {
                $this->model('search_fulltext')->push_index('article', $val['title'], $val['id']);

                $this->model('posts')->set_posts_index($val['id'], 'article', $val);
            }

            H::redirect_msg(AWS_APP::lang()->_t('正在更新文章搜索索引') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_article_search_index/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('搜索索引更新完成'), '/admin/tools/');
        }
    }

    public function update_project_posts_index_action()
    {
        if ($project_list = $this->model('project')->fetch_page('project', 'approved = 1', 'id ASC', $_GET['page'], $_GET['per_page']))
        {
            foreach ($project_list as $key => $val)
            {
                if ($val['approved'] == 1 AND $val['status'] == 'ONLINE')
                {
                    $this->model('posts')->set_posts_index($val['id'], 'project', $val);
                }
                else
                {
                    $this->model('posts')->remove_posts_index($val['id'], 'project');
                }
            }

            H::redirect_msg(AWS_APP::lang()->_t('正在更新活动列表索引') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tools/update_project_posts_index/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('搜索索引更新完成'));
        }
    }

    public function update_fresh_actions_action()
    {
        if ($this->model('system')->update_associate_fresh_action($_GET['page'], $_GET['per_page']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('正在更新最新动态') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_fresh_actions/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('最新动态更新完成'), '/admin/tools/');
        }
    }

    public function update_topic_discuss_count_action()
    {
        if ($this->model('system')->update_topic_discuss_count($_GET['page'], $_GET['per_page']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('正在更新话题统计') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_topic_discuss_count/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('话题统计更新完成'), '/admin/tools/');
        }
    }

    public function email_setting_test_action()
    {
        if ($error_message = AWS_APP::mail()->send($_POST['test_email'], get_setting('site_name') . ' - ' . AWS_APP::lang()->_t('邮件服务器配置测试'), AWS_APP::lang()->_t('这是一封测试邮件，收到邮件表示邮件服务器配置成功'), get_setting('site_name')))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('测试邮件发送失败, 返回的信息: %s', strip_tags($error_message))));
        }
        else
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('测试邮件已发送, 请查收邮件测试配置是否正确')));
        }
    }

    public function update_weixin_menu_action()
    {
        $accounts_info = $this->model('weixin')->get_accounts_info();

        foreach ($accounts_info AS $account_info)
        {
            if ($error_message = $this->model('weixin')->update_client_menu($account_info))
            {
                $messages .= '<br />' . $error_message;
            }
        }

        if ($messages)
        {
            $messages = '更新微信菜单出现错误：<br />' . $messages;
        }
        else
        {
            $messages = '更新微信菜单完成';
        }
        H::redirect_msg(AWS_APP::lang()->_t($messages), '/admin/weixin/mp_menu/');
    }

    public  function  sms_action()
    {
        $setting=get_setting('sms_config');
        $this->crumb(AWS_APP::lang()->_t('短信设置'), 'admin/tools/sms/');
        TPL::assign('setting', $setting);
        TPL::assign('menu_list',  $this->fetch_menu_list());
        TPL::output('admin/tools/sms');
    }

    public function save_sms_action()
    {
        $this->model('setting')->set_vars($_POST);
        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
    }

    public  function  pay_action()
    {
        $setting=get_setting('pay_config');
        $money_config=get_setting('money_config');
        $this->crumb(AWS_APP::lang()->_t('支付设置'), 'admin/tools/pay/');
        TPL::assign('setting', $setting);
        TPL::assign('money_config', $money_config);
        TPL::assign('menu_list',  $this->fetch_menu_list());
        TPL::output('admin/tools/pay');
    }

    public function notes_action()
    {
        $page = $_GET['page']?$_GET['page']:1;
        if ($lists = $this->model('tools')->fetch_page('notes', '', 'id DESC', $page, 15)){
            $total_rows = $this->model('tools')->found_rows();
        }
        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/tools/notes/'),
            'total_rows' => $total_rows,
            'per_page' => 15
        ))->create_links());
        TPL::assign('lists',  $lists);
        TPL::assign('menu_list',  $this->fetch_menu_list());
        TPL::output('admin/tools/notes');
    }

    public  function note_remove_action()
    {
        if(count($_POST['ids'])<1)
            H::ajax_json_output(AWS_APP::RSM(null, -1,'请选择要删除的数据'));
        $ids=implode(',',$_POST['ids']);
        $ret=$this->model('tools')->delete('notes',"id in ($ids)");
        if($ret!==false)
            H::ajax_json_output(AWS_APP::RSM(null, 1,null));
    }

    /*管理员操作记录*/
    public function action_log_action()
    {
        TPL::assign('menu_list',  $this->fetch_menu_list());
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }
        if ($this->is_post())
        {
            foreach ($_POST as $key => $val)
            {
                if (in_array($key, array('user_name', 'email')))
                {
                    $val = rawurlencode($val);
                }

                $param[] = $key . '-' . $val;
            }
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/tools/action_log/' . implode('__', $param))
            ), 1, null));
        }

        $where = array();
        if ($_GET['start_time'])
        {
            $where[] = 'add_time >= '.strtotime($_GET['start_time']);
        }
        if ($_GET['end_time'])
        {
            $where[] = 'add_time <= '.strtotime($_GET['end_time']);
        }
        if ($_GET['action_type'])
        {
            $where[] = "action = '".$_GET['action_type']."'";
        }

        $lists = $this->model('account')->fetch_page('action_log',implode(' AND ',$where),'add_time DESC',$_GET['page'],get_setting('contents_per_page'));
        foreach ($lists as $k=>$v)
        {
            $lists[$k]['user_name'] = get_user_name_by_uid($v['uid']);
        }
        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/tools/action_log/'),
            'total_rows' => $this->model('account')->found_rows(),
            'per_page' => get_setting('contents_per_page')
        ))->create_links());
        $actions = $this->model('account')->fetch_column('action_log','','',['action']);
        TPL::assign('actions',  array_unique($actions));
        TPL::assign('lists',  $lists);
        TPL::output('admin/tools/log');
    }

    public function log_remove_action()
    {
        if (! $_POST['ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择内容进行操作')));
        }

        foreach ($_POST['ids'] as $val)
        {
            $this->model('question')->delete('action_log','id = '.$val);
        }
        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}