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

class article_class extends AWS_MODEL
{
	public function get_article_info_by_id($article_id)
	{
        $article_id = intval($article_id);
		if (!is_digits($article_id))
		{
			return false;
		}

		static $articles;
		if (!$articles[$article_id])
		{
			$articles[$article_id] = $this->fetch_row('article', 'id = ' . intval($article_id));
		}
		return $articles[$article_id];
	}

	public function get_article_info_by_ids($article_ids)
	{
		if (!is_array($article_ids) OR sizeof($article_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($article_ids, 'intval_string');
		if ($articles_list = $this->fetch_all('article', 'is_del = 0 and id IN(' . implode(',', $article_ids) . ')'))
		{
			foreach ($articles_list AS $key => $val)
			{
				$result[$val['id']] = $val;
			}
		}
		return $result;
	}

	public function get_comment_by_id($comment_id)
	{
		if ($comment = $this->fetch_row('article_comments', 'id = ' . intval($comment_id)))
		{
			$comment_user_infos = $this->model('account')->get_user_info_by_uids(array(
				$comment['uid'],
				$comment['at_uid']
			));

			$comment['user_info'] = $comment_user_infos[$comment['uid']];
			$comment['at_user_info'] = $comment_user_infos[$comment['at_uid']];
		}

		return $comment;
	}

	public function get_comments_by_ids($comment_ids)
	{
		if (!is_array($comment_ids) OR !$comment_ids)
		{
			return false;
		}

		array_walk_recursive($comment_ids, 'intval_string');

		if ($comments = $this->fetch_all('article_comments', 'is_del = 0 and id IN (' . implode(',', $comment_ids) . ')'))
		{
			foreach ($comments AS $key => $val)
			{
				$article_comments[$val['id']] = $val;
			}
		}
		return $article_comments;
	}

	public function get_comments($article_id, $page, $per_page,$where=null,$order="add_time ASC")
	{
	    $query_where = 'is_del = 0 and article_id = ' . intval($article_id);
	    if($where){
            $query_where = $query_where .' and '.$where;
        }
		if ($comments = $this->fetch_page('article_comments', $query_where, $order, $page, $per_page))
		{
			foreach ($comments AS $key => $val)
			{
				$comment_uids[$val['uid']] = $val['uid'];

				if ($val['at_uid'])
				{
					$comment_uids[$val['at_uid']] = $val['at_uid'];
				}
			}

			if ($comment_uids)
			{
				$comment_user_infos = $this->model('account')->get_user_info_by_uids($comment_uids);
			}

			foreach ($comments AS $key => $val)
			{
				$comments[$key]['user_info'] = $comment_user_infos[$val['uid']];
				$comments[$key]['at_user_info'] = $comment_user_infos[$val['at_uid']];
			}
		}

		return $comments;
	}

    /**
     * 真实删除
     * @param $article_id
     * @return bool|int
     * @throws Zend_Exception
     */
	public function remove_article_phy($article_id)
	{
		if (!$article_info = $this->get_article_info_by_id($article_id))
		{
			return false;
		}

		$this->delete('article_comments', "article_id = " . intval($article_id)); // 删除关联的回复内容

		$this->delete('topic_relation', "`type` = 'article' AND item_id = " . intval($article_id));		// 删除话题关联

		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action IN(' . ACTION_LOG::ADD_ARTICLE . ', ' . ACTION_LOG::ADD_AGREE_ARTICLE . ', ' . ACTION_LOG::ADD_COMMENT_ARTICLE . ') AND associate_id = ' . intval($article_id));	// 删除动作

		// 删除附件
		if ($attachs = $this->model('publish')->get_attach('article', $article_id))
		{
			foreach ($attachs as $key => $val)
			{
				$this->model('publish')->remove_attach($val['id'], $val['access_key']);
			}
		}

		$this->model('notify')->delete_notify('model_type = 8 AND source_id = ' . intval($article_id));	// 删除相关的通知

		$this->model('posts')->remove_posts_index($article_id, 'article');

		$this->shutdown_update('users', array(
			'article_count' => $this->count('article', 'uid = ' . intval($article_info['uid']))
		), 'uid = ' . intval($article_info['uid']));
        //百度推送，删除记录
        hook('bd_push','push',array('action'=>'del','item_id'=>$article_id,'item_type'=>'article'));
		return $this->delete('article', 'id = ' . intval($article_id));
	}

    /**
     * 物理删除
     * @param $comment_id
     * @return bool
     * @throws Zend_Exception
     */
	public function remove_comment_phy($comment_id)
	{
		$comment_info = $this->get_comment_by_id($comment_id);

		if (!$comment_info)
		{
			return false;
		}

		$this->delete('article_comments', 'id = ' . $comment_info['id']);

		$this->update('article', array(
			'comments' => $this->count('article_comments', 'article_id = ' . $comment_info['article_id'])
		), 'id = ' . $comment_info['article_id']);

		return true;
	}

	public function update_article($article_id, $uid, $title,$logo_img, $message, $topics, $category_id,$column_id, $create_topic)
	{
		if (!$article_info = $this->model('article')->get_article_info_by_id($article_id))
		{
			return false;
		}
		if (is_array($topics))
		{
            $this->delete('topic_relation', 'item_id = ' . intval($article_id) . " AND `type` = 'article'");
			foreach ($topics as $key => $topic_title)
			{
				$topic_id = $this->model('topic')->save_topic($topic_title, $uid, $create_topic);
				$this->model('topic')->save_topic_relation($uid, $topic_id, $article_id, 'article');
			}
		}

		$this->model('search_fulltext')->push_index('article', htmlspecialchars($title), $article_info['id']);
		$this->update('article', array(
			'title' => htmlspecialchars($title),
			'article_img'=>$logo_img,
			'message' => htmlspecialchars($message),
			'category_id' => intval($category_id),
			'column_id' => intval($column_id),
		), 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');
        //百度推送，更新记录
        hook('bd_push','push',array('action'=>'update','item_id'=>$article_id,'item_type'=>'article'));
		return true;
	}

	public function get_articles_list($category_id, $page, $per_page, $order_by, $day = null ,$uid = 0 , $filter = '')
	{
		$where = array();
		if ($category_id)
		{
			$where[] = 'category_id = ' . intval($category_id);
		}

		if ($day)
		{
			$where[] = 'add_time > ' . (time() - $day * 24 * 60 * 60);
		}

		if ($uid)
		{
			$where[] = 'uid = ' . $uid;
		}
		$where[] = 'is_del = 0';
        $category_ids = $this->model('column')->check_suggest();
        if(!empty($category_ids)){
        	  $where[] = 'category_id not in ('.implode(',',$category_ids) .')';
        }
		return $this->fetch_page('article', implode(' AND ', $where), $order_by, $page, $per_page);
	}

	public function get_articles_list_by_topic_ids($page, $per_page, $order_by, $topic_ids)
	{
		if (!$topic_ids)
		{
			return false;
		}

		if (!is_array($topic_ids))
		{
			$topic_ids = array(
				$topic_ids
			);
		}

		array_walk_recursive($topic_ids, 'intval_string');
		$result_cache_key = 'article_list_by_topic_ids_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);
		$found_rows_cache_key = 'article_list_by_topic_ids_found_rows_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);
		if (!$result = AWS_APP::cache()->get($result_cache_key) OR $found_rows = AWS_APP::cache()->get($found_rows_cache_key))
		{
			$topic_relation_where[] = '`topic_id` IN(' . implode(',', $topic_ids) . ')';
			$topic_relation_where[] = "`type` = 'article'";

			if ($topic_relation_query = $this->query_all("SELECT item_id FROM " . get_table('topic_relation') . " WHERE " . implode(' AND ', $topic_relation_where)))
			{
				foreach ($topic_relation_query AS $key => $val)
				{
					$article_ids[$val['item_id']] = $val['item_id'];
				}
			}

			if (!$article_ids)
			{
				return false;
			}

			$where[] = "id IN (" . implode(',', $article_ids) . ")";
		}


		if (!$result)
		{
			$result = $this->fetch_page('article', implode(' AND ', $where), $order_by, $page, $per_page);

			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
		}


		if (!$found_rows)
		{
			$found_rows = $this->found_rows();

			AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
		}

		$this->article_list_total = $found_rows;

		return $result;
	}

	public function lock_article($article_id, $lock_status = true)
	{
		return $this->update('article', array(
			'lock' => intval($lock_status)
		), 'id = ' . intval($article_id));
	}

	public function article_vote($type, $item_id, $rating, $uid, $reputation_factor, $item_uid)
	{
		$this->delete('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . ' AND uid = ' . intval($uid));

		if ($rating)
		{
			if ($article_vote = $this->fetch_row('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = " . intval($rating) . ' AND uid = ' . intval($uid)))
			{
				$this->update('article_vote', array(
					'rating' => intval($rating),
					'time' => time(),
					'reputation_factor' => $reputation_factor
				), 'id = ' . intval($article_vote['id']));
			}
			else
			{
				$this->insert('article_vote', array(
					'type' => $type,
					'item_id' => intval($item_id),
					'rating' => intval($rating),
					'time' => time(),
					'uid' => intval($uid),
					'item_uid' => intval($item_uid),
					'reputation_factor' => $reputation_factor
				));
			}
		}

		switch ($type)
		{
			case 'article':
				$this->update('article', array(
					'votes' => $this->count('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = 1")
				), 'id = ' . intval($item_id));
				if($rating == 1 AND $article_vote['rating'] != 1 AND $article_vote['uid'] != $uid)
			$this->model('notify')->send($uid, $item_uid, notify_class::TYPE_ARTICLE_VOTES, notify_class::CATEGORY_ARTICLE, $item_id, array(
				'from_uid' => $uid,
				'article_id' => $item_id,
			));
				switch ($rating)
				{
					case 1:
						ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_AGREE_ARTICLE);
					break;

					case -1:
						ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ADD_AGREE_ARTICLE . ' AND uid = ' . intval($uid) . ' AND associate_id = ' . intval($item_id));
					break;
				}
			break;

			case 'comment':
				$this->update('article_comments', array(
					'votes' => $this->count('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = 1")
				), 'id = ' . intval($item_id));
				$comments=$this->get_comment_by_id($item_id);
			$this->model('notify')->send($uid, $item_uid, notify_class::TYPE_ARTICLE_COMMENT_VOTES, notify_class::CATEGORY_ARTICLE, $item_id, array(
				'from_uid' => $uid,
				'article_id' => $comments['article_id'],
			));
			break;
		}

		$this->model('account')->sum_user_agree_count($item_uid);
		return true;
	}

	public function get_article_vote_by_id($type, $item_id, $rating = null, $uid = null)
	{
		if ($article_vote = $this->get_article_vote_by_ids($type, array(
			$item_id
		), $rating, $uid))
		{
			return end($article_vote[$item_id]);
		}
	}

	public function get_article_vote_by_ids($type, $item_ids, $rating = null, $uid = null)
	{
		if (!is_array($item_ids))
		{
			return false;
		}

		if (sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($uid)
		{
			$where[] = 'uid = ' . intval($uid);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$result[$val['item_id']][] = $val;
			}
		}

		return $result;
	}

	public function get_article_vote_users_by_id($type, $item_id, $rating = null, $limit = null)
	{
		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id = ' . intval($item_id);

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}

			return $this->model('account')->get_user_info_by_uids($uids);
		}
	}

	public function get_article_vote_users_by_ids($type, $item_ids, $rating = null, $limit = null)
	{
		if (! is_array($item_ids))
		{
			return false;
		}

		if (sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}

			$users_info = $this->model('account')->get_user_info_by_uids($uids);

			foreach ($article_votes AS $key => $val)
			{
				$vote_users[$val['item_id']][$val['uid']] = $users_info[$val['uid']];
			}

			return $vote_users;
		}
	}

	public function update_views($article_id)
	{
		if (AWS_APP::cache()->get('update_views_article_' . md5(session_id()) . '_' . intval($article_id)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_views_article_' . md5(session_id()) . '_' . intval($article_id), time(), 60);

		$this->shutdown_query("UPDATE " . $this->get_table('article') . " SET views = views + 1 WHERE id = " . intval($article_id));

		return true;
	}

	public function set_recommend($article_id)
	{
		$this->update('article', array(
			'is_recommend' => 1
		), 'id = ' . intval($article_id));
		$this->model('posts')->set_posts_index($article_id, 'article');
	}

	public function unset_recommend($article_id)
	{
		$this->update('article', array(
			'is_recommend' => 0
		), 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');
	}

	public function get_user_article_views($uid)
    {
        return $this->sum('article','views','uid = '.$uid);
	}

    public function get_by_like($q, $page = 1, $limit = 20)
    {
        if(!is_array($q))
        	$q=(array)$q;
        $sql = "select * from  ".$this->get_table('article')." where is_del=0 and (title regexp'".$this->quote(implode('|', $q))."' or message regexp'".$this->quote(implode('|', $q))."') order by add_time desc limit ".calc_page_limit($page,$limit);
        $ret = $this->query_all($sql);
        return $ret;
    }

    /**
     * 逻辑删除文章
     * @param $article_id
     * @param bool $whereDel
     * @return bool|mixed
     * @throws \Zend_Exception
     */
    public function remove_article($article_id,$whereDel = false)
    {
        if (!$article_info = $this->get_article_info_by_id($article_id))
        {
            return false;
        }
        $delWhere = null;
        $isdel = 1;
        if($whereDel !== false){
            $delWhere = ' and is_del != 1';
            $isdel = $whereDel;
        }
        $this->update('topic_relation', ['is_del'=>$isdel],"`type` = 'article' AND item_id = " . intval($article_id).$delWhere);// 删除话题关联
        $this->update('posts_index', ['is_del'=>$isdel],'post_type="article" and post_id = ' . intval($article_id).$delWhere);
        $this->update('user_action_history', ['is_del'=>$isdel],'associate_id = ' . intval($article_id).$delWhere);
        $this->update('article', ['is_del'=>$isdel],'id = ' . intval($article_id).$delWhere);
        $this->shutdown_update('users', array(
            'article_count' => $this->count('article', 'is_del=0 and uid = ' . intval($article_info['uid']))
        ), 'uid = ' . intval($article_info['uid']));
        $this->model('topic')->update_discuss_count(3);
        AWS_APP::cache()->clean();
        //百度推送，删除记录
        hook('bd_push','push',array('action'=>'del','item_id'=>$article_id,'item_type'=>'article'));
        return true;
    }

    /**
     * 恢复
     * @param $article_id
     * @param bool $whereDel
     * @return bool|mixed
     * @throws \Zend_Exception
     */
    public function recover_article($article_id,$whereDel = false)
    {
        if (!$article_info = $this->get_article_info_by_id($article_id))
        {
            return false;
        }
        $delWhere = null;
        if($whereDel !== false){
            $delWhere = ' and is_del = '.$whereDel;
        }
        $this->update('topic_relation', ['is_del'=>0],"`type` = 'article' AND item_id = " . intval($article_id).$delWhere);// 删除话题关联
        $this->update('posts_index', ['is_del'=>0],'post_type="article" and post_id = ' . intval($article_id).$delWhere);
        $this->update('user_action_history', ['is_del'=>0],'associate_id = ' . intval($article_id).$delWhere);
        $this->update('article', ['is_del'=>0],'id = ' . intval($article_id).$delWhere);
        $this->shutdown_update('users', array(
            'article_count' => $this->count('article', 'is_del=0 and uid = ' . intval($article_info['uid']))
        ), 'uid = ' . intval($article_info['uid']));
        
        $topic_relation = $this->fetch_all('topic_relation', "`type` = 'article' AND item_id = " . intval($article_id));

        foreach ($topic_relation as $key => $va) 
        {
        	$this->model('topic')->update_discuss_count(intval($va['topic_id']));
        }
        
        AWS_APP::cache()->clean();
        //百度推送，更新记录
        hook('bd_push','push',array('action'=>'update','item_id'=>$article_id,'item_type'=>'article'));
        return true;
    }

    /**
     * 删除文章评论
     * @param $comment_id
     * @param bool $whereDel
     * @return bool
     * @throws \Zend_Exception
     */
    public function remove_comment($comment_id,$whereDel = false)
    {
        $comment_info = $this->get_comment_by_id($comment_id);
        if (!$comment_info) {
            return false;
        }
        $delWhere = null;
        $isdel = 1;
        if($whereDel !== false){
            $delWhere = ' and is_del != 1';
            $isdel = $whereDel;
        }
        $this->update('article_comments', ['is_del' => $isdel],'id = ' . $comment_info['id'].$delWhere);
        $this->update('article', array(
            'comments' => $this->count('article_comments', 'is_del = 0 and article_id = ' . $comment_info['article_id'])
        ), 'id = ' . $comment_info['article_id']);
        return true;
    }

    /**
     * 删除文章评论恢复
     * @param $comment_id
     * @param bool $whereDel
     * @return bool
     * @throws \Zend_Exception
     */
    public function recover_comment($comment_id,$whereDel = false)
    {
        $comment_info = $this->get_comment_by_id($comment_id);
        if (!$comment_info) {
            return false;
        }
        $delWhere = null;
        if($whereDel !== false){
            $delWhere = ' and is_del = '.$whereDel;
        }
        $this->update('article_comments', ['is_del' => 0],'id = ' . $comment_info['id'].$delWhere);
        $this->update('article', array(
            'comments' => $this->count('article_comments', 'is_del = 0 and article_id = ' . $comment_info['article_id'])
        ), 'id = ' . $comment_info['article_id']);
        return true;
    }

    public function get_mix_list($category_id,$is_ssl, $uid, $page = 0, $limit)
    {

        $order = 'set_top_time desc,c.update_time desc';
        if ($is_ssl == 'is_new') {
            $where1 = 'f.post_type="question"';
            $where2 = 'e.post_type="article"';
        }
        if ($is_ssl == 'is_hot') {
            $order = 'comments desc,views desc,votes desc';
        }
        if ($is_ssl == 'is_focus') {
            if ($uid) {
                $ids = $this->model('follow')->get_user_friends_ids($uid);
                $where1 = $ids ? 'f.post_type="article" and b.published_uid <>' . $uid . ' and (b.is_recommend=1 or b.published_uid in (' . implode(',', $ids) . ') )' : 'b.is_recommend=1 and f.post_type="article" and b.published_uid <>' . $uid;
                $where2 = $ids ? 'e.post_type="article" and (a.is_recommend=1 or a.uid in (' . implode(',', $ids) . ') )' : 'e.post_type="article" and a.is_recommend=1 ';
            }else{
                $where1 = 'b.is_recommend=1 and f.post_type="question"';
                $where2 = 'a.is_recommend=1 and e.post_type="article"';
            }
        }

        if($category_id){
            $where1 = 'b.category_id='.$category_id.' and '.$where1;
            $where2 = 'a.category_id='.$category_id.' and '.$where2;
        }

        $sql = 'select c.*,c.update_time,d.uid,d.user_name,d.avatar_file from(
			                select e.set_top, e.set_top_time,e.post_type,a.title,a.id,a.add_time,e.update_time,a.uid,a.comments,a.views,a.votes,
			                a.category_id,a.message as content,1 as type,2 as anonymous ,a.is_recommend,a.article_img from '.$this->get_table('article').' a 
							left join '.$this->get_table('posts_index').' as e on e.post_id=a.id
			                 where ' . $where2 . ' and e.is_del=0 
			                union all 
			                select f.set_top,f.set_top_time, f.post_type, b.question_content as title,b.question_id as id,b.add_time,f.update_time,b.published_uid as uid,b.answer_count as comments,
			                b.view_count as views,b.focus_count as votes,b.category_id,b.question_detail as content,2 as type,b.anonymous,b.is_recommend,1 as article_img from '.$this->get_table('question').' b 
							left join '.$this->get_table('posts_index').' as f on f.post_id=b.question_id
			                 where ' . $where1 . ' and f.is_del=0 
                 ) as c left join '.$this->get_table('users').' d on d.uid=c.uid order by '.$order.' limit '.calc_page_limit($page,$limit);
        $ret = $this->query_all($sql);
        if ($ret) {
            foreach ($ret as $key => $value) {
                preg_match("/(href|src)=([\"|']?)([^\"'>]+.(jpg|JPG|jpeg|JPEG|gif|GIF|png|PNG))/i",strip_tags(FORMAT::parse_attachs(FORMAT::parse_bbcode($value['content'])),'<img>'),$match);
                $ret[$key]['img']=$match[3];
                $ret[$key]['add_time'] = date_friendly($value['add_time']);
                $ret[$key]['content'] = cjk_substr(preg_replace('/\[attach\]([0-9]+)\[\/attach]/', '', strip_tags(html_entity_decode(FORMAT::parse_attachs(FORMAT::parse_bbcode($value['content']))))), 0, 100, 'UTF-8', '...');
                $ret[$key]['title'] = cjk_substr($value['title'], 0, 30, 'UTF-8', '...');
                $ret[$key]['avatar_file'] =get_avatar_url($value['uid'],'max');

                if ($value['type'] == 1) {
                    $imgs = strip_tags(html_entity_decode(FORMAT::parse_attachs(FORMAT::parse_bbcode($value['content']))),'<img>');
                    preg_match_all('/<img[^>]*?src="([^"]*?)"[^>]*?>/i',$imgs,$matches);
                    if($matches[1]){
                        $ret[$key]['article_img'] = $matches[1][0];
                    }
                    $ret[$key]['comment'] = $this->model('article')->get_comments($value['id'], 0, 3);
                }else {
                    $ret[$key]['comment'] = $this->model('answer')->get_answer_list_by_question_id($value['id'], 3);
                }
            }
        } else {
            return false;
        }
        return $ret;

    }

    //物理删除
    public function clear_article($article_id)
    {
    	if (!$article_info = $this->get_article_info_by_id(intval($article_id)))
        {
            return false;
        }
        //删除该文章下的回答数据
        $comments_info = $this->fetch_all('article_comments','article_id='.intval($article_id));
        foreach ($comments_info as $key => $va)
        {
        	$this->delete('article_comments','id='.intval($va['id']));
        	$this->delete('article_vote','type="comment" and item_id='.intval($va['id']));
        }
        $this->delete('article',"id = " . intval($article_id));
        $this->delete('article_vote',"type='comment' and item_id = " . intval($article_id));
        $this->delete('posts_index',"post_type='article' and post_id = " . intval($article_id));
        $this->delete('report',"type='article' and target_id = " . intval($article_id));
        $this->delete('topic_relation',"type='article' and item_id = " . intval($article_id));
        $pattern='/<img((?!src).)*src[\s]*=[\s]*[\'"](?<src>[^\'"]*)[\'"]/i';
        preg_match_all($pattern,replacePicUrl(htmlspecialchars_decode($article_info['message']),base_url()),$out);
        $img_lists = isset($out['src']) ? $out['src'] : [];
        if(!empty($img_lists)){
            foreach ($img_lists as $k=>$v){
                if(strpos($v,'http')!== false){
                    $img_path =ROOT_PATH.str_replace(base_url(),'',$v);
                }else{
                    $img_path =ROOT_PATH.$v;
                }
                @unlink($img_path);
            }
        }
        return true;
    }
}