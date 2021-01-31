<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['actions'] = array(
			'list'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function save_comment_action()
	{
        //文章评论钩子
        run_hook('article_comment_hook',['data'=>$_POST,'action'=>'before']);
		if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定文章不存在')));
		}

		if ($article_info['lock'] AND !($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的文章不能回复')));
		}

		$message = trim($_POST['message'], "\r\n\t ");
		if (! $message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		if (strlen(strip_tags($message)) < get_setting('answer_length_lower'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得少于 %s 字节', get_setting('answer_length_lower'))));
		}

		if (! $this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($message))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
		}

		if (!is_mobile() && human_valid('answer_valid_hour') and !$this->model('tools')->geetest($_POST) && !$_POST['at_uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('验证码错误')));
		}


		// !注: 来路检测后面不能再放报错提示
		if (! valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		if ($this->publish_approval_valid($message))
		{
			$this->model('publish')->publish_approval('article_comment', array(
				'article_id' => intval($_POST['article_id']),
				'message' => $message,
				'at_uid' => intval($_POST['at_uid'])
			), $this->user_id);

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/wait_approval/article_id-' . intval($_POST['article_id']) . '__is_mobile-' . $_POST['_is_mobile'])
			), 1, null));
		}
		else
		{
			$comment_id = $this->model('publish')->publish_article_comment($_POST['article_id'], $message, $this->user_id, $_POST['at_uid']);
			$comment_info = $this->model('article')->get_comment_by_id($comment_id);
			$comment_info['message'] = $this->model('question')->parse_at_user($comment_info['message']);
            //文章评论钩子
            run_hook('article_comment_hook',['data'=>$_POST,'action'=>'after','comment_id'=>$comment_id]);

			TPL::assign('comment_info', $comment_info);
			if (is_mobile())
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'ajax_html' => TPL::output('m/ajax/article_answer', false)
				), 1, null));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'ajax_html' => TPL::output('article/ajax/comment', false)
				), 1, null));
			}
		}
	}

	public function lock_action()
	{
		if (!$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (! $article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文章不存在')));
		}

		$this->model('article')->lock_article($_POST['article_id'], !$article_info['lock']);
        if(is_mobile())
        {
            H::ajax_json_output(AWS_APP::RSM(array('url'=>get_js_url('/m/article/'.intval($_POST['article_id']))), 1, AWS_APP::lang()->_t('操作成功')));
        }
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_article_action()
	{
        $article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']);
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !($this->user_id == $article_info['uid'] && get_setting('user_remove_content_enable')=='Y'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除文章的权限')));
		}

		if ($article_info)
		{
			if ($this->user_id != $article_info['uid'])
			{
				$this->model('account')->send_delete_message($article_info['uid'], $article_info['title'], $article_info['message']);
			}

			$this->model('article')->remove_article($article_info['id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/')
		), 1, null));
	}

	public function remove_comment_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除评论的权限')));
		}

		if ($comment_info = $this->model('article')->get_comment_by_id($_POST['comment_id']))
		{
			$this->model('article')->remove_comment($comment_info['id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/article/' . $comment_info['article_id'])
		), 1, null));
	}

	public function article_vote_action()
	{
		switch ($_POST['type'])
		{
			case 'article':
				$item_info = $this->model('article')->get_article_info_by_id($_POST['item_id']);
			break;

			case 'comment':
				$item_info = $this->model('article')->get_comment_by_id($_POST['item_id']);
			break;

		}

		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能对自己发表的内容进行点赞')));
		}

		$reputation_factor = $this->model('account')->get_user_group_by_id($this->user_info['reputation_group'], 'reputation_factor');

		$this->model('article')->article_vote($_POST['type'], $_POST['item_id'], $_POST['rating'], $this->user_id, $reputation_factor, $item_info['uid']);
		$uf = $this->model('account')->get_user_info_by_uid($this->user_id);
		$uf['avatar_file'] = get_avatar_url($uf['uid'], 'max');
		if(is_mobile())
        {
            H::ajax_json_output(AWS_APP::RSM([], 1, '操作成功'));
        }
		H::ajax_json_output(AWS_APP::RSM($uf, 1, null));
	}

	public function set_recommend_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置推荐的权限')));
		}

		switch ($_POST['action'])
		{
			case 'set':
				$this->model('article')->set_recommend($_POST['article_id']);
			break;

			case 'unset':
				$this->model('article')->unset_recommend($_POST['article_id']);
			break;
		}
        if(is_mobile())
        {
            H::ajax_json_output(AWS_APP::RSM(array('url'=>get_js_url('/m/article/'.intval($_POST['article_id']))), 1, AWS_APP::lang()->_t('操作成功')));
        }
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	/*设置文章置顶*/
    public function set_top_article_action()
    {
        if (!$this->user_info['permission']['is_administortar'] OR !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置置顶的权限')));
        }
        if($this->model('posts')->select_set_top_num()[0]['count(*)']>=3 && $_POST['action'] == 'set'){
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 置顶最多不超过3条')));
        }
        switch ($_POST['action'])
        {
            case 'set':
                $this->model('posts')->set_top_article($_POST['article_id']);
                break;

            case 'unset':
                $this->model('posts')->unset_top_article($_POST['article_id']);
                break;
        }

        if(is_mobile())
        {
            H::ajax_json_output(AWS_APP::RSM(array('url'=>get_js_url('/m/article/'.intval($_POST['article_id']))), 1, AWS_APP::lang()->_t('操作成功')));
        }
        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}