<?php
if (! defined('IN_ANWSION'))
{
    die();
}
class ajax extends AWS_MOBILE_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black';
        return $rule_action;
    }

    /*话题列表*/
    public function topic_list_action()
    {
        $where = '';
        $topic_ids[] = intval($_GET['topic_id']);
        if ($child_topic_ids = $this->model('topic')->get_child_topic_ids($_GET['topic_id']))
        {
            $topic_ids = array_merge($child_topic_ids, $topic_ids);
        }
        if(!empty($topic_ids))
        {
            $where = 'topic_id IN(' . implode(',', array_unique($topic_ids)).')';
        }

        switch ($_GET['type'])
        {
            case 'month':
                $order = 'discuss_count_last_month DESC';
                $topics_list = $this->model('topic')->get_topic_list( $where, $order, 10, intval($_GET['page']));
                $topic_count = $this->model('topic')->get_focus_topic_list($this->user_id, 0,1);
                break;

            case 'week':
                $order = 'discuss_count_last_week DESC';
                $topics_list = $this->model('topic')->get_topic_list( $where, $order, 10, intval($_GET['page']));
                $topic_count = $this->model('topic')->found_rows();
                break;

            case 'focus':
                $topics_list = $this->model('topic')->get_focus_topic_list($this->user_id, calc_page_limit(intval($_GET['page']),10));
                $topic_count = $this->model('topic')->get_focus_topic_list($this->user_id, 0,1);
                break;

            case 'all':
                $order = 'discuss_count DESC';
                $topics_list = $this->model('topic')->get_topic_list(intval($_GET['topic_id']) ? $where : null, $order, 10, intval($_GET['page']));
                $topic_count = $this->model('topic')->found_rows();
                break;
        }

        TPL::assign('topics_list', $topics_list);
        TPL::assign('total',ceil( $topic_count/10));
        TPL::output('m/ajax/topic_list');
    }

    /*获取用户最近使用标签*/
    public function get_user_recent_topics_action()
    {
        $type = $_POST['type'];
        $item_id = intval($_POST['item_id']);
        $recent_topics = @unserialize($this->user_info['recent_topics']);
        switch ($type)
        {
            case 'question':
                $topics = $this->model('topic')->get_topics_by_item_id($item_id, 'question');
                break;
            case 'article':
                $topics = $this->model('topic')->get_topics_by_item_id($item_id, 'article');
                break;
            default:
                break;
        }
        $return = array();
        foreach ($recent_topics as $key=>$val)
        {
            foreach ($topics as $k=>$v)
            {
                if($v['topic_title']==$val)
                {
                    unset($recent_topics[$key]);
                }
            }
        }
        foreach ($recent_topics as $key=>$val)
        {
            $return[$key] = $this->model('topic')->get_topic_by_title($val);
        }
        if(empty($return))
        {
            $return = $this->model('topic')->get_topic_list(null, 'discuss_count DESC', 10,1);
        }
        TPL::assign('recent_topics',$return);
        TPL::output('m/ajax/recent_topics');
    }

    /*用户列表*/
    public function people_list_action()
    {
        if (!$_GET['page'])
        {
            $_GET['page'] = 1;
        }

        $this->crumb(AWS_APP::lang()->_t('用户列表'), '/m/people/');

        if ($_GET['feature_id'])
        {
            if ($helpful_users = $this->model('topic')->get_helpful_users_by_topic_ids($this->model('feature')->get_topics_by_feature_id($_GET['feature_id']), get_setting('contents_per_page'), 4))
            {
                foreach ($helpful_users AS $key => $val)
                {
                    $users_list[$key] = $val['user_info'];
                    $users_list[$key]['experience'] = $val['experience'];
                    foreach ($val['experience'] AS $exp_key => $exp_val)
                    {
                        $users_list[$key]['total_agree_count'] += $exp_val['agree_count'];
                    }
                }
            }
        }
        else
        {
            $where = array();
            if ($_GET['group_id'])
            {
                $where[] = 'group_id = ' . intval($_GET['group_id']);
            }
            $users_list = $this->model('account')->get_users_list(implode('', $where), calc_page_limit($_GET['page'], get_setting('contents_per_page')), true, false, 'reputation DESC');
            $where[] = 'forbidden = 0 AND group_id <> 3';
        }

        if ($users_list)
        {
            foreach ($users_list as $key => $val)
            {
                if ($val['reputation'])
                {
                    $reputation_users_ids[] = $val['uid'];
                    $users_reputations[$val['uid']] = $val['reputation'];
                }
                $uids[] = $val['uid'];
            }

            if (!$_GET['feature_id'])
            {
                $reputation_topics = $this->model('people')->get_users_reputation_topic($reputation_users_ids, $users_reputations, 4);
                foreach ($users_list as $key => $val)
                {
                    $users_list[$key]['reputation_topics'] = $reputation_topics[$val['uid']];
                }
            }

            if ($uids AND $this->user_id)
            {
                $users_follow_check = $this->model('follow')->users_follow_check($this->user_id, $uids);
            }
            foreach ($users_list as $key => $val)
            {
                $users_list[$key]['focus'] = $users_follow_check[$val['uid']];
            }
            $total = $this->model('account')->get_user_count(implode(' AND ', $where));
            TPL::assign('users_list', array_values($users_list));
            TPL::assign('total', ceil($total/get_setting('contents_per_page')));
            TPL::assign('type', 'people_list');
        }
        TPL::output('m/ajax/people_list');
    }

    /*获取用户关注内容*/
    public function get_user_focus_action()
    {
        $page = intval($_GET['page']);

        switch ($_GET['type'])
        {
            case 'question':
                /*$list = $this->model('question')->get_user_focus($this->user_id,calc_page_limit($page,10));

                foreach ($list as $key=>$val)
                {
                    if($val['last_answer'])
                    {
                        $last_answer = $this->model('answer')->get_last_answer($val['question_id']);
                        $list[$key]['answer_info'] = $last_answer;
                        $list[$key]['answer_info']['user_info'] = $this->model('account')->get_user_info_by_uid($last_answer['uid']);
                    }

                    $list[$key]['user_info'] = $this->model('account')->get_user_info_by_uid($val['published_uid']);
                    $list[$key]['post_type'] ='question';
                }

                $count = $this->model('question')->get_user_focus_count($this->user_id);

                TPL::assign('posts_list',$list);
                TPL::assign('total',ceil($count/10));
                TPL::output('m/ajax/explore_list');*/
                $list = $this->model('question')->get_focus_all($this->user_id, $page,10,'question');
                break;

            case 'user':
                $list = $this->model('question')->get_focus_all($this->user_id, $page,10,'user');
                break;

            case 'column':
                $list = $this->model('question')->get_focus_all($this->user_id, $page,10,'column');
                break;

            default:
                //集合
                $list = $this->model('question')->get_focus_all($this->user_id, $page,10,'all');
                
        }

        TPL::assign('posts_list',$list['data']);
        TPL::assign('total',ceil($list['num']/10));
        TPL::output('m/ajax/explore_list');

    }

    /*专栏文章列表*/
    public function article_list_action()
    {
        switch ($_GET['sort'])
        {
            case 'new':
                $order = 'add_time DESC';
                break;
            case 'hot':
                $order = '(votes+views) DESC';
                break;
        }
        $article_list = $this->model('column')->fetch_user_article_list(intval($_GET['column_id']), $_GET['page'] ,10,'',$order);
        foreach ($article_list as $key => $value)
        {
            $article_list[$key]['message'] = html_entity_decode(FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($value['message']))));
        }
        $total = $this->model('column')->found_rows();
        TPL::assign('article_list', $article_list);
        TPL::assign('total', ceil($total/10));
        TPL::output('m/ajax/article_list');
    }

    /*收藏列表*/
    public function favorite_list_action()
    {
        if ($action_list = $this->model('favorite')->get_item_list($_GET['tag'], $this->user_id, calc_page_limit($_GET['page'], get_setting('contents_per_page'))))
        {
            foreach ($action_list AS $key => $val)
            {
                $item_ids[] = $val['item_id'];
            }
            TPL::assign('list', $action_list);
        }
        else
        {
            if (!$_GET['page'] OR $_GET['page'] == 1)
            {
                $this->model('favorite')->remove_favorite_tag(null, null, $_GET['tag'], $this->user_id);
            }
        }

        if ($item_ids)
        {
            $favorite_items_tags = $this->model('favorite')->get_favorite_items_tags_by_item_id($this->user_id, $item_ids);
            TPL::assign('favorite_items_tags', $favorite_items_tags);
        }

        $total = $this->model('favorite')->count_favorite_items($this->user_id, $_GET['tag']);

        TPL::assign('favorite_tags', $this->model('favorite')->get_favorite_tags($this->user_id));
        TPL::assign('total',ceil($total/get_setting('contents_per_page')));
        TPL::output('m/ajax/favorite_list');
    }

    /*获取用户专栏列表*/
    public function get_user_column_list_action()
    {
        $column_info = $this->model('column')->fetch_user_column_list($this->user_id,$_GET['page']? :0 , 10 ,$_GET['sort'],intval($_GET['uid']));
        TPL::assign('column_info', $column_info);
        $total = $this->model('column')->found_rows();
        TPL::assign('total', ceil($total/10));
        TPL::output('m/ajax/column_list');
    }
}