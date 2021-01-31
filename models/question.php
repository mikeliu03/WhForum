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

class question_class extends AWS_MODEL
{
	public function get_focus_uid_by_question_id($question_id)
	{
		return $this->query_all('SELECT uid FROM ' . $this->get_table('question_focus') . ' WHERE question_id = ' . intval($question_id));
	}

	public function get_question_info_by_id($question_id, $cache = true)
	{
		if (! $question_id)
		{
			return false;
		}

		if (!$cache)
		{
			$questions[$question_id] = $this->fetch_row('question', 'question_id = ' . intval($question_id));
		}
		else
		{
			static $questions;
			if ($questions[$question_id])
			{
				return $questions[$question_id];
			}
			$questions[$question_id] = $this->fetch_row('question', 'question_id = ' . intval($question_id));
		}

		if ($questions[$question_id])
		{
			$questions[$question_id]['unverified_modify'] = @unserialize($questions[$question_id]['unverified_modify']);
			if (is_array($questions[$question_id]['unverified_modify']))
			{
				$counter = 0;
				foreach ($questions[$question_id]['unverified_modify'] AS $key => $val)
				{
					$counter = $counter + sizeof($val);
				}
				$questions[$question_id]['unverified_modify_count'] = $counter;
			}
		}
		return $questions[$question_id];
	}

    /**
     * [获取问题]
     * @param $question_ids
     * @param bool $isdel
     * @return bool
     * @throws \Zend_Exception
     */
	public function get_question_info_by_ids($question_ids,$isdel=true)
	{
		if (!$question_ids)
		{
			return false;
		}

		array_walk_recursive($question_ids, 'intval_string');
        if($isdel === true){
            $delwhere = ' is_del = 0 and ';
        }
		if ($questions_list = $this->fetch_all('question', $delwhere."question_id IN(" . implode(',', $question_ids) . ")"))
		{
			foreach ($questions_list AS $key => $val)
			{
				$result[$val['question_id']] = $val;
			}
		}
		return $result;
	}

    /**
     * 增加问题浏览次数记录
     * @param $question_id
     * @return bool
     * @throws \Zend_Exception
     */
	public function update_views($question_id)
	{
		if (AWS_APP::cache()->get('update_views_question_' . md5(session_id()) . '_' . intval($question_id)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_views_question_' . md5(session_id()) . '_' . intval($question_id), time(), 60);

		$this->shutdown_query("UPDATE " . $this->get_table('question') . " SET view_count = view_count + 1 WHERE question_id = " . intval($question_id));

		return true;
	}

    /**
     * 增加问题内容
     * @param $question_content
     * @param $question_detail
     * @param $published_uid
     * @param int $anonymous
     * @param null $ip_address
     * @param null $from
     * @param $is_del
     * @return mixed
     * @throws \Zend_Exception
     */
	public function save_question($question_content, $question_detail, $published_uid, $anonymous = 0, $ip_address = null, $from = null,$is_del=0)
	{
		if (!$ip_address)
		{
			$ip_address = fetch_ip();
		}

		$now = time();
		$to_save_question = array(
			'question_content' => htmlspecialchars($question_content),
			'question_detail' => htmlspecialchars($question_detail),
			'add_time' => $now,
			'is_del' => intval($is_del),
			'update_time' => $now,
			'published_uid' => intval($published_uid),
			'anonymous' => intval($anonymous),
			'ip' => ip2long($ip_address)
		);

		if ($from AND is_array($from))
		{
			foreach ($from AS $type => $from_id)
			{
				if (!is_digits($from_id))
				{
					continue;
				}

				$to_save_question[$type . '_id'] = $from_id;
			}
		}

		$question_id = $this->insert('question', $to_save_question);

		if ($question_id)
		{
			$this->shutdown_update('users', array(
				'question_count' => $this->count('question', 'published_uid = ' . intval($published_uid))
			), 'uid = ' . intval($published_uid));

			$this->model('search_fulltext')->push_index('question', $question_content, $question_id);
		}
        //百度推送，新增记录
        hook('bd_push','push',array('action'=>'urls','item_id'=>$question_id,'item_type'=>'question'));
		return $question_id;
	}

	public function update_question($question_id, $question_content, $question_detail, $uid, $verified = true, $modify_reason = null, $anonymous = null, $category_id = null)
	{
		if (!$question_info = $this->get_question_info_by_id($question_id) OR !$uid)
		{
			return false;
		}

		if ($verified)
		{
			$data['question_detail'] = htmlspecialchars($question_detail);

			if ($question_content)
			{
				$data['question_content'] = htmlspecialchars($question_content);
			}

			$this->model('search_fulltext')->push_index('question', $question_content, $question_id);

			$this->update('question', $data, 'question_id = ' . intval($question_id));
		}

		if ($category_id)
		{
			$this->update('question', array(
				'category_id' => intval($category_id)
			), 'question_id = ' . intval($question_id));
		}

		$addon_data = array(
			'modify_reason' => $modify_reason,
		);

		if ($question_info['question_detail'] != $question_detail)
		{
			$log_id = ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::MOD_QUESTION_DESCRI, $question_detail, $question_info['question_detail'], null, $anonymous, $addon_data);

			if (!$verified)
			{
				$this->track_unverified_modify($question_id, $log_id, 'detail');
			}

		}

		//记录日志
		if ($question_info['question_content'] != $question_content)
		{
			$log_id = ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::MOD_QUESTION_TITLE, $question_content, $question_info['question_content'], 0, 0, $addon_data);

			if (!$verified)
			{
				$this->track_unverified_modify($question_id, $log_id, 'content');
			}
		}

		$this->model('posts')->set_posts_index($question_id, 'question');
        //百度推送，更新记录
        hook('bd_push','push',array('action'=>'update','item_id'=>$question_id,'item_type'=>'question'));
		return true;
	}

	public function verify_modify($question_id, $log_id)
	{
		if (!$unverified_modify = $this->get_unverified_modify($question_id))
		{
			return false;
		}

		if (!$action_log = ACTION_LOG::get_action_by_history_id($log_id))
		{
			return false;
		}

		if (@in_array($log_id, $unverified_modify['content']))
		{
			$this->update('question', array(
				'question_content' => $action_log['associate_content'],
				'update_time' => time()
			), 'question_id = ' . intval($question_id));

			$this->model('search_fulltext')->push_index('question', $action_log['associate_content'], $question_id);

			$this->clean_unverified_modify($question_id, 'content');

			ACTION_LOG::update_action_time_by_history_id($log_id);
		}
		else if (@in_array($log_id, $unverified_modify['detail']))
		{
			$this->update('question', array(
				'question_detail' => $action_log['associate_content'],
				'update_time' => time()
			), 'question_id = ' . intval($question_id));

			$this->clean_unverified_modify($question_id, 'detail');

			ACTION_LOG::update_action_time_by_history_id($log_id);
		}
        //百度推送，更新增记录
        hook('bd_push','push',array('action'=>'update','item_id'=>$question_id,'item_type'=>'question'));
		return false;
	}

	public function unverify_modify($question_id, $log_id)
	{
		if (!$unverified_modify = $this->get_unverified_modify($question_id))
		{
			return false;
		}

		if (!$log_id)
		{
			return false;
		}

		if (@in_array($log_id, $unverified_modify['content']))
		{
			unset($unverified_modify['content'][$log_id]);

			ACTION_LOG::delete_action_history('history_id = ' . intval($log_id));
		}
		else if (@in_array($log_id, $unverified_modify['detail']))
		{
			unset($unverified_modify['detail'][$log_id]);

			ACTION_LOG::delete_action_history('history_id = ' . intval($log_id));
		}

		$this->save_unverified_modify($question_id, $unverified_modify);

		return false;
	}

	public function get_unverified_modify($question_id)
	{
		if (!$question_info = $this->get_question_info_by_id($question_id, false))
		{
			return false;
		}

		if (is_array($question_info['unverified_modify']))
		{
			return $question_info['unverified_modify'];
		}

		if ($question_info['unverified_modify'] = @unserialize($question_info['unverified_modify']))
		{
			return $question_info['unverified_modify'];
		}

		return array();
	}

	public function save_unverified_modify($question_id, $unverified_modify = array())
	{
		$unverified_modify_count = 0;

		foreach ($unverified_modify AS $unverified_modify_info)
		{
			$unverified_modify_count = $unverified_modify_count + count($unverified_modify_info);
		}

		return $this->update('question', array(
			'unverified_modify' => serialize($unverified_modify),
			'unverified_modify_count' => $unverified_modify_count
		), 'question_id = ' . intval($question_id));
	}

	public function track_unverified_modify($question_id, $log_id, $type)
	{
		$unverified_modify = $this->get_unverified_modify($question_id);

		$unverified_modify[$type][$log_id] = $log_id;

		return $this->save_unverified_modify($question_id, $unverified_modify);
	}

	public function clean_unverified_modify($question_id, $type)
	{
		$unverified_modify = $this->get_unverified_modify($question_id);

		unset($unverified_modify[$type]);

		return $this->save_unverified_modify($question_id, $unverified_modify);
	}

	public function get_unverified_modify_count($question_id)
	{
		$question_info = $this->get_question_info_by_id($question_id, false);

		if (!$question_info)
		{
			return false;
		}

		return $question_info['unverified_modify_count'];
	}

    /**
     * @description [物理删除操作]
     * @param $question_id
     * @return bool
     * @throws Zend_Exception
     * @author Laushow
     * @datatime 2018/7/18 9:13
     */
	public function remove_question_phy($question_id)
	{
		if (!$question_info = $this->get_question_info_by_id($question_id))
		{
			return false;
		}

		$this->model('answer')->remove_answers_by_question_id($question_id); // 删除关联的回复内容

		// 删除评论
		$this->delete('question_comments', 'question_id = ' . intval($question_id));

		$this->delete('question_focus', 'question_id = ' . intval($question_id));

		$this->delete('question_thanks', 'question_id = ' . intval($question_id));

		$this->delete('topic_relation', "`type` = 'question' AND item_id = " . intval($question_id));// 删除话题关联

		$this->delete('question_invite', 'question_id = ' . intval($question_id));	// 删除邀请记录

		$this->delete('question_uninterested', 'question_id = ' . intval($question_id));	// 删除不感兴趣的

		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_id = ' . intval($question_id));	// 删除动作

		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($question_id));	// 删除动作

		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_TOPIC . ' AND associate_action = ' . ACTION_LOG::ADD_TOPIC . ' AND associate_attached = ' . intval($question_id));	// 删除动作

		// 删除附件
		if ($attachs = $this->model('publish')->get_attach('question', $question_id))
		{
			foreach ($attachs as $key => $val)
			{
				$this->model('publish')->remove_attach($val['id'], $val['access_key']);
			}
		}

		$this->model('notify')->delete_notify('model_type = 1 AND source_id = ' . intval($question_id));	// 删除相关的通知

		$this->shutdown_update('users', array(
			'question_count' => $this->count('question', 'published_uid = ' . intval($question_info['published_uid']))
		), 'uid = ' . intval($question_info['published_uid']));

		$this->delete('redirect', "item_id = " . intval($question_id) . " OR target_id = " . intval($question_id));

		$this->model('posts')->remove_posts_index($question_id, 'question');

		$this->delete('geo_location', "`item_type` = 'question' AND `item_id` = " . intval($question_id));

		$this->delete('question', 'question_id = ' . intval($question_id));

		if ($question_info['weibo_msg_id'])
		{
			if ($question_info['ticket_id'])
			{
				remove_assoc('weibo_msg', 'question', $question_info['question_id']);
			}
			else
			{
				$this->model('openid_weibo_weibo')->del_msg_by_id($question_info['weibo_msg_id']);
			}
		}

		if ($question_info['received_email_id'])
		{
			if ($question_info['ticket_id'])
			{
				remove_assoc('received_email', 'question', $question_info['question_id']);
			}
			else
			{
				$this->model('edm')->remove_received_email($question_info['received_email_id']);
			}
		}

		if ($question_info['ticket_id'])
		{
			remove_assoc('ticket', 'question', $question_info['question_id']);
		}
        //百度推送，新增记录
        hook('bd_push','push',array('action'=>'del','item_id'=>$question_info['question_id'],'item_type'=>'question'));
	}

	public function add_focus_question($question_id, $uid, $anonymous = 0, $save_action = true)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}

		if (! $this->has_focus_question($question_id, $uid))
		{
			if ($this->insert('question_focus', array(
				'question_id' => intval($question_id),
				'uid' => intval($uid),
				'add_time' => time()
			)))
			{
				$this->update_focus_count($question_id);
			}

			// 记录日志
			if ($save_action)
			{
				ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_REQUESTION_FOCUS, '', '', 0, intval($anonymous));
			}

			return 'add';
		}
		else
		{
			// 减少问题关注数量
			if ($this->delete_focus_question($question_id, $uid))
			{
				$this->update_focus_count($question_id);
			}

			return 'remove';
		}
	}

	/**
	 *
	 * 取消问题关注
	 * @param int $question_id
	 *
	 * @return boolean true|false
	 */
	public function delete_focus_question($question_id, $uid)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}

		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ADD_REQUESTION_FOCUS . ' AND uid = ' . intval($uid) . ' AND associate_id = ' . intval($question_id));

		return $this->delete('question_focus', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function get_focus_question_ids_by_uid($uid)
	{
		if (!$uid)
		{
			return false;
		}

		if (!$question_focus = $this->fetch_all('question_focus', "uid = " . intval($uid)))
		{
			return false;
		}

		foreach ($question_focus as $key => $val)
		{
			$question_ids[$val['question_id']] = $val['question_id'];
		}

		return $question_ids;
	}

	/**
	 *
	 * 判断是否已经关注问题
	 * @param int $question_id
	 * @param int $uid
	 *
	 * @return boolean true|false
	 */
	public function has_focus_question($question_id, $uid)
	{
		if (!$uid OR !$question_id)
		{
			return false;
		}

		return $this->fetch_one('question_focus', 'focus_id', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function has_focus_questions($question_ids, $uid)
	{
		if (!$uid OR !is_array($question_ids) OR sizeof($question_ids) < 1)
		{
			return array();
		}

		$question_focus = $this->fetch_all('question_focus', "question_id IN(" . implode(',', $question_ids) . ") AND uid = " . intval($uid));

		if ($question_focus)
		{
			foreach ($question_focus AS $key => $val)
			{
				$result[$val['question_id']] = $val['focus_id'];
			}

			return $result;
		}
		else
		{
			return array();
		}
	}

	public function get_focus_users_by_question($question_id, $limit = 10)
	{
		if ($uids = $this->query_all('SELECT DISTINCT uid FROM ' . $this->get_table('question_focus') . ' WHERE question_id = ' . intval($question_id) . ' ORDER BY focus_id DESC', intval($limit)))
		{
			$users_list = $this->model('account')->get_user_info_by_uids(fetch_array_value($uids, 'uid'));
		}

		return $users_list;
	}

	public function get_user_focus($uid, $limit = 10)
	{
		if ($question_focus = $this->fetch_all('question_focus', "uid = " . intval($uid), 'question_id DESC', $limit))
		{
			foreach ($question_focus as $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}
		}

		if ($question_ids)
		{
			return $this->fetch_all('question', "question_id IN(" . implode(',', $question_ids) . ") and is_del =0", 'add_time DESC');
		}
	}

	/*获取用户关注问题总数*/
    public function get_user_focus_count($uid)
    {
        return $this->count('question_focus', "uid = " . intval($uid), 'question_id DESC');
    }

	public function update_answer_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}

		return $this->update('question', array(
			'answer_count' => $this->count('answer', 'is_del = 0 and question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}

	public function update_answer_users_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}

		return $this->update('question', array(
			'answer_users' => $this->count('answer', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}

	public function update_focus_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}

		return $this->update('question', array(
			'focus_count' => $this->count('question_focus', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}

	public function get_related_question_list($question_id, $question_content, $limit = 10)
	{
		$cache_key = 'question_related_list_' . md5($question_content) . '_' . $limit;

		if ($question_related_list = AWS_APP::cache()->get($cache_key))
		{
			return $question_related_list;
		}

		if ($question_keywords = $this->model('system')->analysis_keyword($question_content))
		{
			if (sizeof($question_keywords) <= 1)
			{
				return false;
			}

			if ($question_list = $this->query_all($this->model('search_fulltext')->bulid_query('question', 'question_content', $question_keywords), 2000))
			{
				$question_list = aasort($question_list, 'score', 'DESC');
				$question_list = aasort($question_list, 'agree_count', 'DESC');

				$question_list = array_slice($question_list, 0, ($limit + 1));

				foreach ($question_list as $key => $val)
				{
					if ($val['question_id'] == $question_id || $val['is_del']==1)
					{
						unset($question_list[$key]);
					}
					else
					{
						if (! isset($question_related[$val['question_id']]))
						{
							$question_related[$val['question_id']] = $val['question_content'];

							$question_info[$val['question_id']] = $val;
						}
					}
				}
			}
		}

		if ($question_related)
		{
			foreach ($question_related as $key => $question_content)
			{
				$question_related_list[] = array(
					'question_id' => $key,
					'question_content' => $question_content,
					'answer_count' => $question_info[$key]['answer_count']
				);
			}
		}

		if (sizeof($question_related) > $limit)
		{
			array_pop($question_related);
		}

		AWS_APP::cache()->set($cache_key, $question_related_list, get_setting('cache_level_low'));

		return $question_related_list;
	}

	/**
	 *
	 * 得到用户感兴趣问题列表
	 * @param int $uid
	 * @return array
	 */
	public function get_question_uninterested($uid)
	{
		if ($questions = $this->fetch_all('question_uninterested', 'uid = ' . intval($uid)))
		{
			foreach ($questions as $key => $val)
			{
				$data[] = $val['question_id'];
			}
		}

		return $data;
	}

	/**
	 *
	 * 保存用户不感兴趣问题列表
	 * @param int $uid
	 * @param int $question_id
	 *
	 * @return boolean true|false
	 */
	public function add_question_uninterested($uid, $question_id)
	{
		if (!$uid OR !$question_id)
		{
			return false;
		}

		if (! $this->has_question_uninterested($uid, $question_id))
		{
			return $this->insert('question_uninterested', array(
				"question_id" => $question_id,
				"uid" => $uid,
				"add_time" => time()
			));
		}
		else
		{
			return false;
		}
	}

	/**
	 *
	 * 删除用户不感兴趣问题列表
	 * @param int $uid
	 * @param int $question_id
	 *
	 * @return boolean true|false
	 */
	public function delete_question_uninterested($uid, $question_id)
	{
		return $this->delete('question_uninterested', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function has_question_uninterested($uid, $question_id)
	{
		return $this->fetch_row('question_uninterested', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function add_invite($question_id, $sender_uid, $recipients_uid = 0, $email = null)
	{
		if (!$question_id OR !$sender_uid)
		{
			return false;
		}

		if (!$recipients_uid AND !$email)
		{
			return false;
		}

		$data = array(
			'question_id' => intval($question_id),
			'sender_uid' => intval($sender_uid),
			'add_time' => time(),
		);

		if ($recipients_uid)
		{
			$data['recipients_uid'] = intval($recipients_uid);
		}
		else if ($email)
		{
			$data['email'] = $email;
		}

		return $this->insert('question_invite', $data);
	}

	/**
	 * 发起者取消邀请
	 * @param unknown_type $question_id
	 * @param unknown_type $sender_uid
	 * @param unknown_type $recipients_uid
	 */
	public function cancel_question_invite($question_id, $sender_uid, $recipients_uid)
	{
		AWS_APP::cache()->set('question_invite_users_' . $question_id,null);
		
		return $this->delete('question_invite', 'question_id = ' . intval($question_id) . ' AND sender_uid = ' . intval($sender_uid) . ' AND recipients_uid = ' . intval($recipients_uid));
	}

	/**
	 * 接收者删除邀请
	 * @param unknown_type $question_invite_id
	 * @param unknown_type $recipients_uid
	 */
	public function delete_question_invite($question_invite_id, $recipients_uid)
	{
		return $this->delete('question_invite', 'question_invite_id = ' . intval($question_invite_id) . ' AND recipients_uid = ' . intval($recipients_uid));
	}

	/**
	 * 接收者回复邀请的问题
	 * @param unknown_type $question_invite_id
	 * @param unknown_type $recipients_uid
	 */
	public function answer_question_invite($question_id, $recipients_uid)
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			return false;
		}

		if ($question_invites = $this->fetch_row('question_invite', 'question_id = ' . intval($question_id) . ' AND sender_uid = ' . $question_info['published_uid'] . ' AND recipients_uid = ' . intval($recipients_uid)))
		{
			$this->model('integral')->process($question_info['published_uid'], 'INVITE_ANSWER', get_setting('integral_system_config_invite_answer'), '邀请回答成功 #' . $question_id, $question_id);

			$this->model('integral')->process($recipients_uid, 'ANSWER_INVITE', -get_setting('integral_system_config_invite_answer'), '回复邀请回答 #' . $question_id, $question_id);
		}

		$this->delete('question_invite', 'question_id = ' . intval($question_id) . ' AND recipients_uid = ' . intval($recipients_uid));

		$this->model('account')->update_question_invite_count($recipients_uid);
	}

	public function has_question_invite($question_id, $recipients_uid, $sender_uid = null)
	{
		if (!$sender_uid)
		{
			return $this->fetch_one('question_invite', 'question_invite_id', 'question_id = ' . intval($question_id) . ' AND recipients_uid = ' . intval($recipients_uid));
		}
		else
		{
			return $this->fetch_one('question_invite',  'question_invite_id', 'question_id = ' . intval($question_id) . ' AND sender_uid = ' . intval($sender_uid) . ' AND recipients_uid = ' . intval($recipients_uid));
		}
	}

	public function has_question_invite_all($question_id, $recipients_uid, $sender_uid = 0)
	{
		$list = $this->fetch_all('question_invite', 'question_invite_id', 'question_id = ' . intval($question_id) . ' AND sender_uid = ' . intval($sender_uid). ' AND recipients_uid in(' . implode(',', array_unique($recipients_uid)).')');

		foreach ($list as $key => $va) 
		{
			$result[$va['recipients_uid']] = $va;
		}

		return $result;
	}

	public function check_email_invite($question_id, $sender_uid, $email)
	{
		return $this->fetch_row('question_invite', 'question_id = ' . intval($question_id) . ' AND email = \'' . $email . '\'');
	}

	public function get_invite_users($question_id, $limit = 10)
	{
		if ($invite_users_list = AWS_APP::cache()->get('question_invite_users_' . $question_id))
		{
			return $invite_users_list;
		}
		
		$invite_users_list = array();
		if ($invites = $this->fetch_all('question_invite', 'question_id = ' . intval($question_id), 'question_invite_id DESC', $limit))
		{
			foreach ($invites as $key => $val)
			{
				$invite_users[$val['recipients_uid']] = $val['recipients_uid'];
			}
			$invite_users_list = $this->model('account')->get_user_info_by_uids($invite_users);
			AWS_APP::cache()->set('question_invite_users_' . $question_id, $invite_users_list, get_setting('cache_level_normal'));
		}
		return $invite_users_list;
	}

	public function get_invite_question_list($uid, $limit = 10)
	{
		if ($list = $this->fetch_all('question_invite', 'recipients_uid = ' . intval($uid), 'question_invite_id DESC', $limit))
		{
			foreach ($list as $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}

			$question_infos = $this->get_question_info_by_ids($question_ids);

			foreach ($list as $key => $val)
			{
				$list[$key]['question_info'] = $question_infos[$val['question_id']];
			}
			return $list;
		}
	}

	public function parse_at_user($content, $popup = false, $with_user = false, $to_uid = false)
	{
		preg_match_all('/@([^@,:\s,]+)/i', strip_tags($content), $matchs);

		if (is_array($matchs[1]))
		{
			$match_name = array();

			foreach ($matchs[1] as $key => $user_name)
			{
				if (in_array($user_name, $match_name))
				{
					continue;
				}

				$match_name[] = $user_name;
			}

			$match_name = array_unique($match_name);

			arsort($match_name);

			$all_users = array();

			$content_uid = $content;

			foreach ($match_name as $key => $user_name)
			{
				if (preg_match('/^[0-9]+$/', $user_name))
				{
					$user_info = $this->model('account')->get_user_info_by_uid($user_name);
				}
				else
				{
					$user_info = $this->model('account')->get_user_info_by_username($user_name);
				}

				if ($user_info)
				{
					$content = str_replace('@' . $user_name, '<a href="people/' . $user_info['uid'] . '"' . (($popup) ? ' target="_blank"' : '') . ' class="aw-user-name" data-id="' . $user_info['uid'] . '">@' . $user_info['user_name'] . '</a>', $content);

					if ($to_uid)
					{
						$content_uid = str_replace('@' . $user_name, '@' . $user_info['uid'], $content_uid);
					}

					if ($with_user)
					{
						$all_users[] = $user_info['uid'];
					}
				}
			}
		}

		if ($with_user)
		{
			return $all_users;
		}

		if ($to_uid)
		{
			return $content_uid;
		}

		return $content;
	}

	public function update_question_comments_count($question_id)
	{
		$count = $this->count('question_comments', 'is_del = 0 and question_id = ' . intval($question_id));

		$this->shutdown_update('question', array(
			'comment_count' => $count
		), 'question_id = ' . intval($question_id));
	}

	public function set_recommend($question_id)
	{
		$this->update('question', array(
			'is_recommend' => 1
		), 'question_id = ' . intval($question_id));

		$this->model('posts')->set_posts_index($question_id, 'question');
	}

	public function unset_recommend($question_id)
	{
		$this->update('question', array(
			'is_recommend' => 0
		), 'question_id = ' . intval($question_id));

		$this->model('posts')->set_posts_index($question_id, 'question');
	}

	public function insert_question_comment($question_id, $uid, $message)
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			return false;
		}

		$message = $this->model('question')->parse_at_user($_POST['message'], false, false, true);

		$comment_id = $this->insert('question_comments', array(
			'uid' => intval($uid),
			'question_id' => intval($question_id),
			'message' => htmlspecialchars($message),
			'time' => time()
		));

		if ($question_info['published_uid'] != $uid)
		{
			$this->model('notify')->send($uid, $question_info['published_uid'], notify_class::TYPE_QUESTION_COMMENT, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
				'from_uid' => $uid,
				'question_id' => $question_info['question_id'],
				'comment_id' => $comment_id
			));

			if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($question_info['published_uid']))
			{
				$weixin_user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);

				if ($weixin_user_info['weixin_settings']['NEW_COMMENT'] != 'N')
				{
                    if(get_setting('weixin_notify_type') == 'TEXT')
                    {
                        $this->model('weixin')->send_text_message($weixin_user['openid'], "您的问题 [" . $question_info['question_content'] . "] 收到了新的评论:\n\n" . strip_tags(html_entity_decode($message)), $this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id']));
                    }
                    if(get_setting('weixin_notify_type') == 'TEMPLATE')
                    {
                        $template_info = $this->model('weixin')->fetch_row('weixin_template',"template_type = 'NEW_COMMENT'");
                        $send_message = parse_wx_template_msg($template_info['template_data'],$question_info['question_content'],strip_tags(html_entity_decode($question_info['question_detail'])),date('Y-m-d H:i',time()),strip_tags(html_entity_decode($message)));
                        $this->model('weixin')->send_template_message($weixin_user['openid'],$template_info['template_id'],$this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id']),$send_message);
                    }
				}
			}
		}

		if ($at_users = $this->model('question')->parse_at_user($message, false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id == $question_info['published_uid'])
				{
					continue;
				}

				$this->model('notify')->send($uid, $user_id, notify_class::TYPE_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
					'from_uid' => $uid,
					'question_id' => $question_info['question_id'],
					'comment_id' => $comment_id,
					'anonymous' => $question_info['published_uid'] == $uid && $question_info['anonymous'] ? 1 : 0,
				));

				if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($user_id))
				{
					$weixin_user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);

					if ($weixin_user_info['weixin_settings']['AT_ME'] != 'N')
					{
                        if(get_setting('weixin_notify_type') == 'TEXT')
                        {
                            $this->model('weixin')->send_text_message($weixin_user['openid'], "有会员在问题 [" . $question_info['question_content'] . "] 评论中提到了您", $this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id']));
                        }
                        if(get_setting('weixin_notify_type') == 'TEMPLATE')
                        {
                            $template_info = $this->model('weixin')->fetch_row('weixin_template',"template_type = 'AT_ME'");
                            $send_message = parse_wx_template_msg($template_info['template_data'],$question_info['question_content'],strip_tags(html_entity_decode($question_info['question_detail'])),date('Y-m-d H:i',time()),strip_tags(html_entity_decode($message)));
                            $this->model('weixin')->send_template_message($weixin_user['openid'],$template_info['template_id'],$this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id']),$send_message);
                        }
					}
				}
			}
		}

		$this->update_question_comments_count($question_id);

		return $comment_id;
	}

	public function get_question_comments($question_id)
	{
		return $this->fetch_all('question_comments', 'is_del = 0 and question_id = ' . intval($question_id), "time ASC");
	}

	public function get_comment_by_id($comment_id)
	{
		return $this->fetch_row('question_comments', "id = " . intval($comment_id));
	}

	public function remove_comment($comment_id,$whereDel = false)
	{
        $delWhere = null;
        $isdel = 1;
        if($whereDel !== false){
            $delWhere = ' and is_del != 1';
            $isdel = $whereDel;
        }
		return $this->update('question_comments', ['is_del'=>$isdel],"id = " . intval($comment_id).$delWhere);
	}

	/**
	 * 处理话题日志
	 * @param array $log_list
	 *
	 * @return array
	 */
	public function analysis_log($log_list, $published_uid = 0, $anonymous = 0)
	{
		if (!$log_list)
		{
			return $log_list;
		}

		foreach ($log_list as $key => $log)
		{
			$log_user_ids[] = $log['uid'];
		}

		if ($log_user_ids)
		{
			$log_users_info = $this->model('account')->get_user_info_by_uids($log_user_ids);
		}

		foreach ($log_list as $key => $log)
		{
			$title_list = null;
			$user_info = $log_users_info[$log['uid']];
			$user_name = $user_info['user_name'];
			$user_url = 'people/' . $user_info['uid'];

			if ($published_uid == $log['uid'] AND $anonymous)
			{
				$user_name_string = AWS_APP::lang()->_t('匿名用户');
			}
			else
			{
				$user_name_string = '<a href="' . $user_url . '">' . $user_name . '</a>';
			}

			switch ($log['associate_action'])
			{
				case ACTION_LOG::ADD_QUESTION :
					$title_list = $user_name_string . AWS_APP::lang()->_t(' 添加了该问题').'</p><p>' . $log['associate_content'] . '</p><p>' . $log['associate_attached'] . '';
					break;

				case ACTION_LOG::MOD_QUESTION_TITLE : //修改问题标题

					$title_list = $user_name_string . AWS_APP::lang()->_t(' 修改了问题标题');

					if ($log['addon_data']['modify_reason'])
					{
						$title_list .= '[' . $log['addon_data']['modify_reason'] . ']';
					}

					$Services_Diff = new Services_Diff($log['associate_attached'], $log['associate_content']);

					$title_list .= '<p>' . $Services_Diff->get_Text_Diff_Renderer_inline() . '</p>';

					break;

				case ACTION_LOG::MOD_QUESTION_DESCRI : //修改问题

					$title_list = $user_name_string . AWS_APP::lang()->_t(' 修改了问题内容');

					if ($log['addon_data']['modify_reason'])
					{
						$title_list .= '[' . $log['addon_data']['modify_reason'] . ']';
					}

					$Services_Diff = new Services_Diff(chinese_msubstr(htmlspecialchars_decode(html_entity_decode($log['associate_attached'])),0,1000,'utf-8'), $log['associate_content']);

					$title_list .= '<p>' .$Services_Diff->get_Text_Diff_Renderer_inline() . '</p>';

					break;

				case ACTION_LOG::ADD_TOPIC : //添加话题

					$topic_info = $this->model('topic')->get_topic_by_id($log['associate_attached']);
					$title_list = $user_name_string . AWS_APP::lang()->_t(' 给该问题添加了一个话题').' <p><a href="topic/' . $topic_info['topic_id'] . '">' . $log['associate_content'] . '</a>';

					break;

				case ACTION_LOG::DELETE_TOPIC : //移除话题

					$topic_info = $this->model('topic')->get_topic_by_id($log['associate_attached']);
					$title_list = $user_name_string . AWS_APP::lang()->_t(' 移除了该问题的一个话题').' <p><a href="topic/' . $topic_info['topic_id'] . '">' . $log['associate_content'] . '</a>';

					break;

				case ACTION_LOG::MOD_QUESTION_CATEGORY : //修改分类


					$title_list = $user_name_string . AWS_APP::lang()->_t(' 修改了该问题的分类').' <p><a href="explore/category-' . $log['associate_attached'] . '">' . $log['associate_content'] . '</a>';

					break;

				case ACTION_LOG::MOD_QUESTION_ATTACH : //修改附件


					$title_list = $user_name_string . AWS_APP::lang()->_t(' 修改了该问题的附件');

					break;

				case ACTION_LOG::REDIRECT_QUESTION : //问题重定向

					$question_info = $this->get_question_info_by_id($log['associate_attached']);

					if ($question_info)
					{
						$title_list = $user_name_string . AWS_APP::lang()->_t(' 将问题重定向至：').'<a href="question/' . $log['associate_attached'] . '">' . $question_info['question_content'] . '</a>';
					}

					break;

				case ACTION_LOG::DEL_REDIRECT_QUESTION : //取消问题重定向


					$title_list = $user_name_string . AWS_APP::lang()->_t(' 取消了问题重定向');

					break;
			}

			$data_list[] = ($title_list) ? array(
				'title' => $title_list,
				'add_time' => date('Y-m-d H:i:s', $log['add_time']),
				'log_id' => sprintf('%06s', $log['history_id'])
			) : '';
		}

		return $data_list;
	}

	public function redirect($uid, $item_id, $target_id = NULL)
	{
		if ($item_id == $target_id)
		{
			return false;
		}

		if (! $target_id)
		{
			if ($this->delete('redirect', 'item_id = ' . intval($item_id)))
			{
				return ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::DEL_REDIRECT_QUESTION);
			}
		}
		else if ($question = $this->get_question_info_by_id($item_id))
		{
			if (! $this->fetch_row('redirect', 'item_id = ' . intval($item_id) . ' AND target_id = ' . intval($target_id)))
			{
				$redirect_id = $this->insert('redirect', array(
					'item_id' => intval($item_id),
					'target_id' => intval($target_id),
					'time' => time(),
					'uid' => intval($uid)
				));

				ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::REDIRECT_QUESTION, $question['question_content'], $target_id);

				return $redirect_id;
			}
		}
	}

	public function get_redirect($item_id)
	{
		return $this->fetch_row('redirect', 'item_id = ' . intval($item_id));
	}

	public function save_last_answer($question_id, $answer_id = null)
	{
		$arr['last_answer'] = intval($answer_id);
		if (!$answer_id)
		{
			if ($last_answer = $this->fetch_row('answer', 'is_del = 0 and question_id = ' . intval($question_id), 'add_time DESC'))
			{
				$answer_id = $last_answer['answer_id'];
				$arr['update_time'] = $last_answer['add_time'];
				$arr['last_answer'] = $last_answer['uid'];
			}
		}

		return $this->shutdown_update('question', $arr , 'question_id = ' . intval($question_id));
	}

	public function calc_popular_value($question_id)
	{
		if (!$question_info = $this->fetch_row('question', 'question_id = ' . intval($question_id)))
		{
			return false;
		}

		if ($question_info['popular_value_update'] > time() - 300)
		{
			return false;
		}

		//$popular_value = (log($question_info['view_count'], 10) * 4 + $question_info['focus_count'] + ($question_info['agree_count'] * $question_info['agree_count'] / ($question_info['agree_count'] + $question_info['against_count'] + 1))) / (round(((time() - $question_info['add_time']) / 3600), 1) / 2 + round(((time() - $question_info['update_time']) / 3600), 1) / 2 + 1);
		$popular_value = log($question_info['view_count'], 10) + $question_info['focus_count'] + ($question_info['agree_count'] * $question_info['agree_count'] / ($question_info['agree_count'] + $question_info['against_count'] + 1));

		return $this->shutdown_update('question', array(
			'popular_value' => $popular_value,
			'popular_value_update' => time()
		), 'question_id = ' . intval($question_id));
	}

	public function get_modify_reason()
	{
		if ($modify_reasons = explode("\n", get_setting('question_modify_reason')))
		{
			$modify_reason = array();

			foreach($modify_reasons as $key => $val)
			{
				$val = trim($val);

				if ($val)
				{
					$modify_reason[] = $val;
				}
			}

			return $modify_reason;
		}
		else
		{
			return false;
		}
	}

	public function save_report($uid, $type, $target_id, $reason, $url)
	{
		return $this->insert('report', array(
			'uid' => $uid,
			'type' => htmlspecialchars($type),
			'target_id' => $target_id,
			'reason' => htmlspecialchars($reason),
			'url' => htmlspecialchars($url),
			'add_time' => time(),
			'status' => 0,
		));
	}

	public function get_report_list($where, $page, $pre_page, $order = 'id DESC')
	{
		return $this->fetch_page('report', $where, $order, $page, $pre_page);
	}

	public function update_report($report_id, $data)
	{
		return $this->update('report', $data, 'id = ' . intval($report_id));
	}

	public function delete_report($report_id)
	{
		return $this->delete('report', 'id = ' . intval($report_id));
	}

	/**
	 *
	 * 根据问题ID,得到相关联的话题标题信息
	 * @param int $question_id
	 * @param string $limit
	 *
	 * @return array
	 */
	public function get_question_topic_by_questions($question_ids, $limit = null)
	{
		if (!is_array($question_ids) OR sizeof($question_ids) == 0)
		{
			return false;
		}

		if (!$topic_ids_query = $this->query_all("SELECT DISTINCT topic_id FROM " . $this->get_table('topic_relation') . " WHERE item_id IN(" . implode(',', $question_ids) . ") AND `type` = 'question'"))
		{
			return false;
		}

		foreach ($topic_ids_query AS $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}

		if ($topic_list = $this->query_all("SELECT * FROM " . $this->get_table('topic') . " WHERE topic_id IN(" . implode(',', $topic_ids) . ") ORDER BY discuss_count DESC", $limit))
		{
			foreach ($topic_list AS $key => $val)
			{
				if (!$val['url_token'])
				{
					$topic_list[$key]['url_token'] = urlencode($val['topic_title']);
				}
			}
		}

		return $topic_list;
	}

	public function get_topic_info_by_question_ids($question_ids)
	{
		if (!is_array($question_ids))
		{
			return false;
		}

		if ($topic_relation = $this->fetch_all('topic_relation', 'item_id IN(' . implode(',', $question_ids) . ") AND `type` = 'question'"))
		{
			foreach ($topic_relation AS $key => $val)
			{
				$topic_ids[$val['topic_id']] = $val['topic_id'];
			}

			$topics_info = $this->model('topic')->get_topics_by_ids($topic_ids);

			foreach ($topic_relation AS $key => $val)
			{
				$topics_by_questions_ids[$val['item_id']][] = array(
					'topic_id' => $val['topic_id'],
					'topic_title' => $topics_info[$val['topic_id']]['topic_title'],
					'url_token' => $topics_info[$val['topic_id']]['url_token'],
				);
			}
		}

		return $topics_by_questions_ids;
	}

	public function lock_question($question_id, $lock_status = true)
	{
		return $this->update('question', array(
			'lock' => intval($lock_status)
		), 'question_id = ' . intval($question_id));
	}

	public function auto_lock_question()
	{
		if (!get_setting('auto_question_lock_day'))
		{
			return false;
		}

		return $this->shutdown_update('question', array(
			'lock' => 1
		), '`lock` = 0 AND `update_time` < ' . (time() - 3600 * 24 * get_setting('auto_question_lock_day')));
	}

	public function get_question_thanks($question_id, $uid)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}

		return $this->fetch_row('question_thanks', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function question_thanks($question_id, $uid, $user_name)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}

		if (!$question_info = $this->get_question_info_by_id($question_id))
		{
			return false;
		}

		if ($question_thanks = $this->get_question_thanks($question_id, $uid))
		{
			//$this->delete('question_thanks', "id = " . $question_thanks['id']);

			return false;
		}
		else
		{
			$this->insert('question_thanks', array(
				'question_id' => $question_id,
				'uid' => $uid,
				'user_name' => $user_name
			));

			$this->shutdown_update('question', array(
				'thanks_count' => $this->count('question_thanks', 'question_id = ' . intval($question_id)),
			), 'question_id = ' . intval($question_id));

			$this->model('integral')->process($uid, 'QUESTION_THANKS', get_setting('integral_system_config_thanks'), '感谢问题 #' . $question_id, $question_id);

			$this->model('integral')->process($question_info['published_uid'], 'THANKS_QUESTION', -get_setting('integral_system_config_thanks'), '问题被感谢 #' . $question_id, $question_id);

			$this->model('account')->update_thanks_count($question_info['published_uid']);

			return true;
		}
	}

	public function get_related_topics($question_content)
	{
		if ($question_related_list = $this->get_related_question_list(null, $question_content, 10))
		{
			foreach ($question_related_list AS $key => $val)
			{
				$question_related_ids[$val['question_id']] = $val['question_id'];
			}

			if (!$topic_ids_query = $this->fetch_all('topic_relation', 'item_id IN(' . implode(',', $question_related_ids) . ") AND `type` = 'question'"))
			{
				return false;
			}

			foreach ($topic_ids_query AS $key => $val)
			{
				if ($val['merged_id'])
				{
					continue;
				}

				$topic_hits[$val['topic_id']] = intval($topic_hits[$val['topic_id']]) + 1;
			}

			if (!$topic_hits)
			{
				return false;
			}

			arsort($topic_hits);

			$topic_hits = array_slice($topic_hits, 0, 3, true);

			foreach ($topic_hits AS $topic_id => $hits)
			{
				if ($topic_info = $this->model('topic')->get_topic_by_id($topic_id))
				{
					$topics[$topic_info['topic_title']] = $topic_info['topic_title'];
				}
			}
		}

		return $topics;
	}

	public function get_answer_users_by_question_id($question_id, $limit = 5, $published_uid = null)
	{
		if ($result = AWS_APP::cache()->get('answer_users_by_question_id_' . md5($question_id . $limit . $published_uid)))
		{
			return $result;
		}

		if (!$published_uid)
		{
			if (!$question_info = $this->get_question_info_by_id($question_id))
			{
				return false;
			}

			$published_uid = $question_info['published_uid'];
		}

		if ($answer_users = $this->query_all("SELECT DISTINCT uid FROM " . get_table('answer') . " WHERE is_del =0 AND question_id = " . intval($question_id) . " AND uid <> " . intval($published_uid) . " AND anonymous = 0 ORDER BY agree_count DESC LIMIT " . intval($limit)))
		{
			foreach ($answer_users AS $key => $val)
			{
				$answer_uids[] = $val['uid'];
			}

			$result = $this->model('account')->get_user_info_by_uids($answer_uids);

			AWS_APP::cache()->set('answer_users_by_question_id_' . md5($question_id . $limit . $published_uid), $result, get_setting('cache_level_normal'));
		}

		return $result;
	}

	public function get_near_by_questions($longitude, $latitude, $uid, $limit = 10)
	{
		$squares = $this->model('geo')->get_square_point($longitude, $latitude, 1);

		if ($near_by_locations = $this->fetch_all('geo_location', "item_type = 'question' AND `latitude` > 0 AND `latitude` > " . $squares['BR']['latitude'] . " AND `latitude` < " . $squares['TL']['latitude'] . " AND `longitude` > " . $squares['TL']['longitude'] . " AND `longitude` < " . $squares['BR']['longitude'], 'add_time DESC', null, $limit))
		{
			foreach ($near_by_locations AS $key => $val)
			{
				$near_by_question_ids[$val['item_id']] = $val['item_id'];
				$near_by_location_longitude[$val['item_id']] = $val['longitude'];
				$near_by_location_latitude[$val['item_id']] = $val['latitude'];
			}

			if ($near_by_questions = $this->get_question_info_by_ids($near_by_question_ids))
			{
				foreach ($near_by_questions AS $key => $val)
				{
					$near_by_uids = $val['published_uid'];
				}

				$near_by_users = $this->model('account')->get_user_info_by_uids($near_by_uids);

				foreach ($near_by_questions AS $key => $val)
				{
					$near_by_questions[$key]['user_info'] = $near_by_users[$val['published_uid']];

					$near_by_questions[$key]['distance'] = $this->model('geo')->get_distance($longitude, $latitude, $near_by_location_longitude[$val['question_id']], $near_by_location_longitude[$val['question_id']]);
				}
			}
		}

		return $near_by_questions;
	}
    public function get_by_like($q, $page = 1, $limit = 1, $is_recommend = false)
    {
        if(!is_array($q))
        	$q=(array)$q;
        $sql = "select * from ".$this->get_table('question')." where is_del=0 and (question_content regexp'".$this->quote(implode('|', $q))."' or question_detail regexp'".$this->quote(implode('|', $q))."') order by add_time desc limit ".calc_page_limit($page,$limit);
        $ret = $this->query_all($sql);
        return $ret;
    }
    /*更新首页删除回复排序*/
    public function update_index_post_order($question_id)
    {
        if($anser_lists=$this->fetch_row('answer',"`question_id` =".intval($question_id),"add_time DESC"))
        {
            $update_time=$anser_lists['add_time'];
            $this->update('posts_index',['update_time'=>$update_time],"post_type='question' AND post_id =".intval($question_id));
        }else{
            $posts_info=$this->fetch_row('posts_index',"post_type = 'question' AND post_id =".intval($question_id));
            if($posts_info){
                $this->update('posts_index',['update_time'=>$posts_info['add_time']],"post_type='question' AND post_id =".intval($question_id));
            }
        }
    }

    /**
     * 逻辑删除问题
     * @param $question_id
     * @param bool $whereDel
     * @return bool
     * @throws \Zend_Exception
     */
    public function remove_question($question_id,$whereDel = false)
    {
        if (!$question_info = $this->get_question_info_by_id($question_id))
        {
            return false;
        }
        $delWhere = null;
        $is_del = 1;
        if($whereDel !== false){
            $delWhere = ' and is_del != 1';
            $is_del = $whereDel;
        }

        #更新问题表 状态删除
        $this->update('topic_relation', ['is_del'=>$is_del],"`type` = 'question' AND item_id = " . intval($question_id).$delWhere);
        $this->update('posts_index', ['is_del'=>$is_del],'post_type="question" and post_id = ' . intval($question_id).$delWhere);
        $this->update('question', ['is_del'=>$is_del],'question_id = ' . intval($question_id).$delWhere);
        $this->update('answer', ['is_del'=>$is_del],'question_id = ' . intval($question_id).$delWhere);
        $this->update('posts_index', ['is_del'=>$is_del],'post_id = ' . intval($question_id).' and post_type = "question"'.$delWhere);
        
        $topic_relation = $this->fetch_all('topic_relation', "`type` = 'question' AND item_id = " . intval($question_id));

        foreach ($topic_relation as $key => $va) 
        {
        	$this->model('topic')->update_discuss_count(intval($va['topic_id']));
        }

        $answers = $this->query_all('select uid from ' . $this->get_table('answer').' where question_id = ' . intval($question_id).' group by uid');
        foreach($answers as $row)
        {
            $this->shutdown_update('users', array(
                'answer_count' => $this->count('answer', 'is_del = 0 and question_id = ' . intval($question_id).' and uid = ' . intval($row['uid']))
            ), 'uid = ' . intval($row['uid']));
        }
        $this->shutdown_update('users', array(
            'question_count' => $this->count('question', 'is_del=0 and published_uid = ' . intval($question_info['published_uid']))
        ,'answer_count' => $this->count('answer', 'is_del=0 and uid = ' . intval($question_info['published_uid']))
        ), 'uid = ' . intval($question_info['published_uid']));

        ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_id = ' . intval($question_id),$whereDel);	// 删除动作
        ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($question_id),$whereDel);	// 删除动作
        ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_TOPIC . ' AND associate_action = ' . ACTION_LOG::ADD_TOPIC . ' AND associate_attached = ' . intval($question_id),$whereDel);	// 删除动作
        AWS_APP::cache()->clean();
        //百度推送，删除记录
        hook('bd_push','push',array('action'=>'del','item_id'=>$question_info['question_id'],'item_type'=>'question'));
        if($whereDel==false && $question_info['is_pay'] && $question_info['is_reward'] < 2 )
        {
            $this->begin_transaction();
            try{
                $users_attrib = $this->db()->fetchRow("SELECT * FROM ".$this->get_table('users_attrib')." WHERE is_del=0 and uid={$question_info['published_uid']}");
                if(!$users_attrib){
                    return false;
                    //$this->roll_back();
                }

                //退围观费用
                if($question_info['looker_money'] > 0){
                    $this->model('recharge')->refund_look($question_info['question_id'],'delete');
                }

                $money=$question_info['delay_money']==0.00 ? $question_info['reward_money'] : $question_info['delay_money'] + $question_info['reward_money'] ;
                $this->query_all("UPDATE ".$this->get_table('users_attrib')." SET balance=balance+{$money} where uid ={$question_info['published_uid']}");//更新用户余额

                if(!$this->model('question')->update('question', array('is_reward'=>3), 'is_del = 1 and question_id = '.$question_info['question_id']))
                {
                    return false;
                    //$this->roll_back();
                }

                //插入交易记录日志
                $logs = array(
                    'uid' => $question_info['published_uid'],
                    'recharge_id' => 0,
                    'order_id' => $question_info['question_id'],
                    'type' => 7,
                    'money' => $money,
                    'balance' => $this->model('account')->get_user_attrib_info_by_uid($question_info['published_uid'])['balance'],
                    'pay_way' => 0,
                    'status' => 1,
                    'remarks' => '悬赏问题 被删除 自动退费(围观/悬赏/延期) #'.$question_info['question_id'],
                    'addtime' => time()
                );
                if(!$this->insert('finance_log', $logs))
                {
                    return false;
                    //$this->roll_back();
                }
                $this->commit();
                return true;
            }catch (Exception $e){
                $this->roll_back();
                die($e->getMessage());
            }
        }
    }

    /**
     * @description [恢复问题]
     * @author Laushow
     * @datatime 2018/7/18 10:14
     */
    public function recover_question($question_id,$whereDel = false){
        if (!$question_info = $this->get_question_info_by_id($question_id))
        {
            return false;
        }
        $delWhere = null;
        if($whereDel !== false){
            $delWhere = ' and is_del = '.$whereDel;
        }
        #更新问题表 状态删除
        $this->update('topic_relation', ['is_del'=>0],"`type` = 'question' AND item_id = " . intval($question_id).$delWhere);
        $this->update('posts_index', ['is_del'=>0],'post_type="question" and post_id = ' . intval($question_id).$delWhere);
        $this->update('question', ['is_del'=>0],'question_id = ' . intval($question_id).$delWhere);
        $this->update('answer', ['is_del'=>0],'question_id = ' . intval($question_id).$delWhere);
        $this->update('posts_index', ['is_del'=>0],'post_id = ' . intval($question_id).' and post_type = "question"'.$delWhere);
        
        $topic_relation = $this->fetch_all('topic_relation', "`type` = 'question' AND item_id = " . intval($question_id));

        foreach ($topic_relation as $key => $va) 
        {
        	$this->model('topic')->update_discuss_count(intval($va['topic_id']));
        }
        
        $answers = $this->query_all('select uid from ' . $this->get_table('answer').' where question_id = ' . intval($question_id).' group by uid');
        foreach($answers as $row){
            $this->shutdown_update('users', array(
                'answer_count' => $this->count('answer', 'is_del = 0 and question_id = ' . intval($question_id).' and uid = ' . intval($row['uid']))
            ), 'uid = ' . intval($row['uid']));
        }
        $this->shutdown_update('users', array(
            'question_count' => $this->count('question', 'is_del=0 and published_uid = ' . intval($question_info['published_uid']))
        ,'answer_count' => $this->count('answer', 'is_del=0 and uid = ' . intval($question_info['published_uid']))
        ), 'uid = ' . intval($question_info['published_uid']));
        ACTION_LOG::recover_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_id = ' . intval($question_id).$delWhere);	// 删除动作
        ACTION_LOG::recover_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($question_id).$delWhere);	// 删除动作
        ACTION_LOG::recover_action_history('associate_type = ' . ACTION_LOG::CATEGORY_TOPIC . ' AND associate_action = ' . ACTION_LOG::ADD_TOPIC . ' AND associate_attached = ' . intval($question_id).$delWhere);	// 删除动作
        AWS_APP::cache()->clean();
        //百度推送，新增记录
        hook('bd_push','push',array('action'=>'update','item_id'=>$question_info['question_id'],'item_type'=>'question'));
    }

    /**
     * @description [恢复问题评论]
     * @param $comment_id
     * @return int
     * @throws Zend_Exception
     * @author Laushow
     * @datatime 2018/7/18 17:52
     */
    public function recover_comment($comment_id,$whereDel = false)
    {
        $delWhere = null;
        if($whereDel !== false){
            $delWhere = ' and is_del = '.$whereDel;
        }
        return $this->update('question_comments', ['is_del'=>0],"id = " . intval($comment_id).$delWhere);
    }

    //物理删除
    public function clear_question($question_id)
    {
    	if (!$question_info = $this->get_question_info_by_id(intval($question_id)))
        {
            return false;
        }
        
        //删除该问题下的回答数据
        $answer_info = $this->fetch_all('answer','question_id='.intval($question_id));
        foreach ($answer_info as $key => $va) {
        	$this->delete('answer','answer_id='.intval($va['answer_id']));
        	$this->delete('answer_comments','answer_id='.intval($va['answer_id']));
        	$this->delete('answer_thanks','answer_id='.intval($va['answer_id']));
        	$this->delete('answer_uninterested','answer_id='.intval($va['answer_id']));
        	$this->delete('answer_vote','answer_id='.intval($va['answer_id']));
        }

        $this->delete('question',"question_id = " . intval($question_id));
        $this->delete('question_comments',"question_id = " . intval($question_id));
        $this->delete('question_complain',"question_id = " . intval($question_id));
        $this->delete('question_focus',"question_id = " . intval($question_id));
        $this->delete('question_invite',"question_id = " . intval($question_id));
        $this->delete('question_thanks',"question_id = " . intval($question_id));
        $this->delete('question_uninterested',"question_id = " . intval($question_id));

        $this->delete('posts_index',"post_type='question' and post_id = " . intval($question_id));

        $this->delete('report',"type='question' and target_id = " . intval($question_id));
        $this->delete('topic_relation',"type='question' and item_id = " . intval($question_id));

        $pattern='/<img((?!src).)*src[\s]*=[\s]*[\'"](?<src>[^\'"]*)[\'"]/i';
        preg_match_all($pattern,replacePicUrl(htmlspecialchars_decode($question_info['question_detail']),base_url()),$out);
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

    //物理删除
    public function clear_question_comment($comment_id)
    {
    	if (!$comment_info = $this->get_comment_by_id(intval($comment_id)))
        {
            return false;
        }
        
        $this->delete('question_comments',"id = " . intval($comment_id));

        $this->update_question_comments_count($comment_info['question_id']);
        return true;
    }

    //关注的人 关注的问题  关注的专栏
    public function get_focus_all($uid, $page = 1,$limit = 10,$post_type = 'all')
	{
		if($post_type == 'all' || $post_type == 'user')
		{
			//关注的人的问题，文章
			$user_follow = $this->fetch_all('user_follow', 'fans_uid = ' . intval($uid));

			foreach ($user_follow AS $key => $val)
			{
				$friend_uids[] = $val['friend_uid'];
			}

			if($friend_uids)
			{
				$user_question_lists = $this->fetch_all('question', "published_uid IN(" . implode(',', $friend_uids).") and is_del =0", 'add_time DESC');

				foreach ($user_question_lists as $key => $val)
				{
					$question_ids[] = $val['question_id'];
				}

				$user_article_lists = $this->fetch_all('article', "uid IN(" . implode(',', $friend_uids).") and is_del =0", 'add_time DESC');

				foreach ($user_article_lists as $key => $val)
				{
					$article_ids[] = $val['id'];
				}
			}
		}
		

		if($post_type == 'all' || $post_type == 'question')
		{
			//关注的问题
			if ($question_focus = $this->fetch_all('question_focus', "uid = " . intval($uid), 'question_id DESC'));
			{
				foreach ($question_focus as $key => $val)
				{
					$question_ids[] = $val['question_id'];
				}
			}
		}

		if($post_type == 'all' || $post_type == 'column')
		{
			//关注的专栏
			if ($column_focus = $this->fetch_all('column_focus', "uid = " . intval($uid), 'column_id DESC'));
			{
				foreach ($column_focus as $key => $val)
				{
					$column_ids[] = $val['column_id'];
				}
			}

			if($column_ids)
			{
				$column_article_lists = $this->fetch_all('article', "column_id IN(" . implode(',', $column_ids).") and is_del =0", 'add_time DESC');

				foreach ($column_article_lists as $key => $val)
				{
					$article_ids[] = $val['id'];
				}
			}
		}

		//组合
		$article_lists = $question_lists = array();
		
		if ($question_ids)
		{
			$question_lists = $this->fetch_all('question', "question_id IN(" . implode(',', array_unique($question_ids)) . ") and is_del =0", 'add_time DESC');

			foreach ($question_lists as $key=>$val)
            {
                if($val['last_answer'])
                {
                    $last_answer = $this->model('answer')->get_last_answer($val['question_id']);
                    $question_lists[$key]['answer_info'] = $last_answer;
                    $question_lists[$key]['answer_info']['user_info'] = $this->model('account')->get_user_info_by_uid($last_answer['uid']);
                }

                $question_lists[$key]['user_info'] = $this->model('account')->get_user_info_by_uid($val['published_uid']);
                $question_lists[$key]['post_type'] ='question';
            }
		}

		if ($article_ids)
		{
			$article_lists = $this->fetch_all('article', "id IN(" . implode(',', array_unique($article_ids)) . ") and is_del =0", 'add_time DESC');

			foreach ($article_lists as $key=>$val)
            {
                $article_lists[$key]['user_info'] = $this->model('account')->get_user_info_by_uid($val['uid']);
                $article_lists[$key]['post_type'] ='article';
            }
		}

		$explore_list_data = array_merge($question_lists,$article_lists);

		$last_names = array_column($explore_list_data,'add_time');
		array_multisort($last_names,SORT_DESC,$explore_list_data);

		$page = intval($page) < 1 ? 1 : intval($page);
		$data = array_slice($explore_list_data, ($page-1)*$limit, $limit);

		return array('data'=>$data,'num'=>count($explore_list_data));
	}

}
 