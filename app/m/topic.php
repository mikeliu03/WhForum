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
class topic extends AWS_MOBILE_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black';
        return $rule_action;
    }

    public function index_square_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/topic/');
        }
        TPL::assign('parent_topics', $this->model('topic')->get_parent_topics());
        TPL::output('m/topic/square');
    }

    public function index_action()
    {
        if (!is_mobile())
        {
            HTTP::redirect('/topic/' . $_GET['id']);
        }
        if (is_digits($_GET['id']))
        {
            if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
            {
                $topic_info = $this->model('topic')->get_topic_by_title($_GET['id']);
            }
        }
        else if (!$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']))
        {
            $topic_info = $this->model('topic')->get_topic_by_url_token($_GET['id']);
        }

        if (!$topic_info)
        {
            HTTP::error_404();
        }

        if ($topic_info['merged_id'] AND $topic_info['merged_id'] != $topic_info['topic_id'])
        {
            if ($this->model('topic')->get_topic_by_id($topic_info['merged_id']))
            {
                HTTP::redirect('/topic/' . $topic_info['merged_id'] . '?rf=' . $topic_info['topic_id']);
            }
            else
            {
                $this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
            }
        }

        if (is_digits($_GET['rf']) and $_GET['rf'])
        {
            if ($from_topic = $this->model('topic')->get_topic_by_id($_GET['rf']))
            {
                $redirect_message[] = AWS_APP::lang()->_t('话题 (%s) 已与当前话题合并', $from_topic['topic_title']);
            }
        }

        if ($topic_info['seo_title'])
        {
            TPL::assign('page_title', $topic_info['seo_title']);
        }
        else
        {
            $this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['url_token']);
        }
        if ($this->user_id)
        {
            $topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
        }

        if ($topic_info['topic_description'])
        {
            TPL::set_meta('description', $topic_info['topic_title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($topic_info['topic_description'])), 0, 128, 'UTF-8', '...'));
        }
        $topic_info['topic_description'] = html_entity_decode((FORMAT::parse_bbcode($topic_info['topic_description'])));
        TPL::assign('topic_info', $topic_info);
        TPL::assign('best_answer_users', $this->model('topic')->get_best_answer_users_by_topic_id($topic_info['topic_id'], 5));
        switch ($topic_info['model_type'])
        {
            default:
                $related_topics_ids = array();
                $page_keywords[] = $topic_info['topic_title'];
                if ($related_topics = $this->model('topic')->related_topics($topic_info['topic_id']))
                {
                    foreach ($related_topics AS $key => $val)
                    {
                        $related_topics_ids[$val['topic_id']] = $val['topic_id'];

                        $page_keywords[] = $val['topic_title'];
                    }
                }

                TPL::set_meta('keywords', implode(',', $page_keywords));
                TPL::set_meta('description', cjk_substr(str_replace("\r\n", ' ', strip_tags($topic_info['topic_description'])), 0, 128, 'UTF-8', '...'));

                if ($child_topic_ids = $this->model('topic')->get_child_topic_ids($topic_info['topic_id']))
                {
                    foreach ($child_topic_ids AS $key => $topic_id)
                    {
                        $related_topics_ids[$topic_id] = $topic_id;
                    }
                }

                TPL::assign('related_topics', $related_topics);

                $log_list = ACTION_LOG::get_action_by_event_id($topic_info['topic_id'], 10, ACTION_LOG::CATEGORY_TOPIC, implode(',', array(
                    ACTION_LOG::ADD_TOPIC,
                    ACTION_LOG::MOD_TOPIC,
                    ACTION_LOG::MOD_TOPIC_DESCRI,
                    ACTION_LOG::MOD_TOPIC_PIC,
                    ACTION_LOG::DELETE_TOPIC,
                    ACTION_LOG::ADD_RELATED_TOPIC,
                    ACTION_LOG::DELETE_RELATED_TOPIC
                )), -1);

                $log_list = $this->model('topic')->analysis_log($log_list);

                $contents_topic_id = $topic_info['topic_id'];
                $contents_topic_title = $topic_info['topic_title'];

                if ($merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']))
                {
                    foreach ($merged_topics AS $key => $val)
                    {
                        $merged_topic_ids[] = $val['source_id'];
                    }

                    $contents_topic_id .= ',' . implode(',', $merged_topic_ids);

                    if ($merged_topics_info = $this->model('topic')->get_topics_by_ids($merged_topic_ids))
                    {
                        foreach($merged_topics_info AS $key => $val)
                        {
                            $merged_topic_title[] = $val['topic_title'];
                        }
                    }

                    if ($merged_topic_title)
                    {
                        $contents_topic_title .= ',' . implode(',', $merged_topic_title);
                    }
                }

                $contents_related_topic_ids = array_merge($related_topics_ids, explode(',', $contents_topic_id));

                TPL::assign('contents_related_topic_ids', implode(',', $contents_related_topic_ids));

                if ($posts_list = $this->model('posts')->get_posts_list(null, 1, get_setting('contents_per_page'), 'new', $contents_related_topic_ids))
                {
                    foreach ($posts_list AS $key => $val)
                    {
                        if ($val['answer_count'])
                        {
                            $posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
                        }
                    }
                }

                TPL::assign('posts_list', $posts_list);
                TPL::assign('all_list_bit_count', count($posts_list));
                TPL::assign('all_list_bit', TPL::output('explore/ajax/list', false));

                if ($posts_list = $this->model('posts')->get_posts_list(null, 1, get_setting('contents_per_page'), null, $contents_related_topic_ids, null, null, 30, true))
                {
                    foreach ($posts_list AS $key => $val)
                    {
                        if ($val['answer_count'])
                        {
                            $posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
                        }
                    }
                }
                TPL::assign('topic_recommend_list', $posts_list);
                TPL::assign('posts_list', $posts_list);
                TPL::assign('recommend_list_bit_count', count($posts_list));
                TPL::assign('recommend_list_bit', TPL::output('explore/ajax/list', false));

                $list = $this->model('topic')->get_topic_best_answer_action_list($contents_topic_id, $this->user_id, get_setting('contents_per_page'));
                TPL::assign('list', $list);
                TPL::assign('best_questions_list_bit_count', count($list));
                TPL::assign('best_questions_list_bit', TPL::output('home/ajax/index_actions', false));

                $post_list = $this->model('posts')->get_posts_list('question', 1, get_setting('contents_per_page'), 'new', explode(',', $contents_topic_id));
                TPL::assign('posts_list', $post_list);
                TPL::assign('all_questions_list_bit_count', count($posts_list));
                TPL::assign('all_questions_list_bit', TPL::output('explore/ajax/list', false));

                $post_list = $this->model('posts')->get_posts_list('article', 1, get_setting('contents_per_page'), 'new', explode(',', $contents_topic_id));
                TPL::assign('posts_list', $post_list);
                TPL::assign('articles_list_bit_count', count($posts_list));
                TPL::assign('articles_list_bit', TPL::output('explore/ajax/list', false));

                TPL::assign('contents_topic_id', $contents_topic_id);
                TPL::assign('contents_topic_title', $contents_topic_title);

                TPL::assign('log_list', $log_list);

                TPL::assign('redirect_message', $redirect_message);
                TPL::assign('per_page', get_setting('contents_per_page'));
                if ($topic_info['parent_id'])
                {
                    TPL::assign('parent_topic_info', $this->model('topic')->get_topic_by_id($topic_info['parent_id']));
                }

                TPL::output('m/topic/index');
                break;
        }
    }
}