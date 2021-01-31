var AWS =
{
	loading: function (s)
	{
		var index;
		if (s == 'show')
		{
			index = layer.load();
		}
		if (s == 'hide')
		{
			layer.closeAll();
		}

	},
	alert:function(msg)
	{
		layer.msg(msg);
	},
	dowmload :function(url,uid){
		$.ajax({
			url:G_BASE_URL+'/down/download_file/',
			type:'post',
			dataType:'json',
			data:{url:url,uid:uid},
			success:function(res){
				if(res.errno==1){
					location.href=res.rsm;
				}else{
					layer.msg(res.err);
				}
			}
		});
	},
	ajaxRequest: function(url,type,dataType,data,success)
	{
		return $.ajax({
			type: type,
			data: data,
			url: url,
			dataType:dataType,
			beforeSend: function(){

			},
			success: function(res){
				layer.closeAll('loading');
				if(res.errno >0 || dataType!='json') {
					success && success(res);
				} else if(res.errno< 0){
					layer.msg(res.err || res.errno, {anim: 0,icon:-1,time:1000});
				}

			}, error: function(e){
				layer.closeAll('loading');
				layer.msg('请求异常或无操作权限', {shift: 6,time:1000});
			},
			headers: {"Access-Control-Allow-Headers":"X-Requested-With" }
		});
	},

	ajax_post: function (formEl, processer, type)	// 表单对象，用 jQuery 获取，回调函数名
	{
		if (!type)
		{
			var type = 'default';
		}
		AWS.loading('show');
		$.ajax({
			url:$(formEl).attr('action'),
			dataType: 'json',
			type:'post',
			data:$(formEl).serialize(),
			success: function (result)
			{
				AWS.loading('hide');
				if (typeof(processer) != 'function')
				{
					var msg = result.err ? result.err : '操作成功';
					layer.msg(msg, {time: 3000}, function(){
						if(result.rsm.url)
						{
							window.location.href = result.rsm.url;
						}else{
							window.location.reload();
						}
					});
				}else{
					processer(result);
				}
			},
			error:	function (error) { if ($.trim(error.responseText) != '') { AWS.loading('hide'); alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText); } }
		});
	},
	
	ajax_request: function(url, params ,selecter)
	{
		var index = layer.load();
		if (params)
		{
			$.post(url, params + '&_post_type=ajax', function (result)
			{
				_callback(result);
			}, 'json').error(function (error)
			{
				_error(error);
			});
		}
		else
		{
			$.get(url, function (result)
			{
				_callback(result);
			}, 'json').error(function (error)
			{
				_error(error);
			});
		}

		function _callback (result)
		{
			layer.close(index);
			if(result.errno > 0)
			{
				var msg = result.err ? result.err : '操作成功';
				layer.msg(msg, {
					time: 3000
				}, function(){
					if(result.rsm.url)
					{
						window.location.href = result.rsm.url;
					}else{
						window.location.reload();
					}
				});
			}else{
				return false;
			}
		}

		function _error (error)
		{
			layer.close(index);
			if ($.trim(error.responseText) != '')
			{
				layer.msg(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
			}
		}

		return false;
	},

	/*确认对话框*/
	confirm: function (msg,url,data)
	{
		var template = Hogan.compile(AWS_MOBILE_TEMPLATE.confirmBox).render(
		{
			'message': msg
		});

		if (template)
		{
			$('#aw-ajax-box').html(template).show();
			$('.aw-toast-confirm .aw-toast-yes').click(function () {
				$.post(url, data,function (result)
				{
					if(result.errno>0)
					{
						window.location.href = result.rsm.url;
					}
				}, 'json');
			});
		}
	},

	/**
	 *	公共弹窗
	 *	publish     : 发起
	 *	redirect    : 问题重定向
	 *	imageBox    : 插入图片
	 *	videoBox    : 插入视频
	 *  linkbox     : 插入链接
	 *	commentEdit : 评论编辑
	 *  favorite    : 评论添加收藏
	 *	inbox       : 私信
	 *  report      : 举报问题
	 */
	dialog: function (type, data, callback)
	{
		switch (type)
		{
			case 'alertImg':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.alertImg).render(
					{
						'hide': data.hide,
						'url': data.url,
						'message': data.message
					});
				break;

			case 'redirect':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.questionRedirect).render(
					{
						'data_id': data
					});
				break;

			case 'appeal':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.appeal).render(
					{
						'item_id': data
					});
				break;

			case 'favorite':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.favoriteBox).render(
					{
						'item_id': data.item_id,
						'item_type': data.item_type
					});
				break;

			case 'inbox':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.inbox).render(
					{
						'recipient': data
					});
				break;

			case 'report':
				var reson=data.item_reson.split(',');
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.reportBox,'').render(
					{
						'item_type': data.item_type,
						'item_id': data.item_id,
						'item_reson': reson,
					});
				break;

			case 'preview':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.previewBox,'').render(
					{
						'item_content': data.item_content,
					});
				break;
			case 'topicEditHistory':
				var template = AWS_MOBILE_TEMPLATE.ajaxData.replace('{{title}}', _t('编辑记录')).replace('{{data}}', data);
				break;

			case 'ajaxData':
				var template = AWS_MOBILE_TEMPLATE.ajaxData.replace('{{title}}', data.title).replace('{{data}}', '<div id="aw_dialog_ajax_data"></div>');
				break;

			case 'confirm':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.confirmBox).render(
					{
						'message': data.message
					});
				break;

			case 'recommend':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.recommend).render();
				break;

			case 'projectEventForm':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.projectEventForm).render(
					{
						'project_id': data.project_id,
						'contact_name': data.contact_name,
						'contact_tel': data.contact_tel,
						'contact_email': data.contact_email
					});
				break;

			case 'projectStockForm':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.projectStockForm).render(
					{
						'project_id': data.project_id,
						'contact_name': data.contact_name,
						'contact_tel': data.contact_tel,
						'contact_email': data.contact_email
					});
				break;

			case 'activityBox':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.activityBox).render(
					{
						'contact_name': data.contact_name,
						'contact_tel': data.contact_tel,
						'contact_qq': data.contact_qq
					});

				break;

			//文章评论弹窗
			case 'articleBox':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.articleBox).render(
					{
						'article_id': data.article_id,
						'at_uid':data.uid
					});

				break;
			//文章评论弹窗
			case 'answerCommentBox':
				var template = Hogan.compile(AWS_MOBILE_TEMPLATE.answerCommentBox).render(
					{
						'answer_id': data.answer_id,
						'at_user':data.at_user
					});
				break;
		}

		if (template)
		{
			if ($('.alert-box').length)
			{
				$('.alert-box').remove();
			}
			$('.aw-toast').hide();
			$('#aw-ajax-box').html(template).show();
			switch (type)
			{
				case 'redirect' :
					AWS.Dropdown.bind_dropdown_list($('.aw-question-redirect-box #question-input'), 'redirect');
					break;

				case 'favorite':
					$.get(G_BASE_URL + '/favorite/ajax/get_favorite_tags/', function (result)
					{
						var html = '';

						$.each(result, function (i, e)
						{
							html += '<li><a data-value="' + e['title'] + '"><span class="title">' + e['title'] + '</span></a><i class="icon icon-followed"></i></li>';
						});

						$('.aw-favorite-tag-list ul').append(html);

						$.post(G_BASE_URL + '/favorite/ajax/get_item_tags/', {
							'item_id' : $('#favorite_form input[name="item_id"]').val(),
							'item_type' : $('#favorite_form input[name="item_type"]').val()
						}, function (result)
						{
							if (result != null)
							{
								$.each(result, function (i, e)
								{
									var index = i;
									$.each($('.aw-favorite-tag-list ul li .title'), function (i, e)
									{
										if ($(this).text() == result[index])
										{
											$(this).parents('li').addClass('active');
										}
									});
								});
							}
						}, 'json');

						$(document).on('click', '.aw-favorite-tag-list ul li', function()
						{
							if ($(this).hasClass('disabled'))
							{
								layer.msg('已收藏');
								return false;
							}

							var tag = $(this).find('a').data('value');
							if ($(this).hasClass('active'))
							{
								$(this).removeClass('active');
								$(this).find('input').remove();
							}else{
								$(this).addClass('active');
								$(this).append('<input type="hidden" value="'+tag+'" name="tags[]">');
							}
						});
						$(document).on('click', '.aw-add-favorite a', function () {
							$('.aw-favorite-box').hide();
							$('.add_favorite_tags').show();
						});
					}, 'json');
					break;

				case 'report':
					$('.aw-report-toast .reportBox .dropdown').click(function () {
						$('.aw-report-toast .dropdown-menu').toggle();
					});

					$('.aw-report-toast ul.dropdown-menu li').click(function ()
					{
						$("#aw-report-tags-select").text($(this).attr('value'));
						$('.aw-report-toast .dropdown-menu').toggle();
						$('.aw-report-toast textarea').text($(this).attr('value'));
					});
					break;

				case 'ajaxData':
					$.get(data.url, function (result) {
						$('#aw_dialog_ajax_data').html(result);
					});
					break;

				case 'confirm':
					$('.aw-confirm-box .yes').click(function()
					{
						if (callback)
						{
							callback();
						}

						$(".alert-box").modal('hide');

						return false;
					});
					break;

				case 'recommend':
					$.get(G_BASE_URL + '/help/ajax/list/', function (result)
					{
						if (result && result != 0)
						{
							var html = '';

							$.each(result, function (i, e)
							{
								html += '<li class="aw-border-radius-5"><img class="aw-border-radius-5" src="' + e.icon + '"><a data-id="' + e.id + '" class="aw-hide-txt">' + e.title + '</a><i class="icon icon-followed"></i></li>'
							});

							$('.aw-recommend-box ul').append(html);

							$.each($('.aw-recommend-box ul li'), function (i, e)
							{
								if (data.focus_id == $(this).find('a').attr('data-id'))
								{
									$(this).addClass('active');
								}
							});

							$(document).on('click', '.aw-recommend-box ul li a', function()
							{
								var _this = $(this), url = G_BASE_URL + '/help/ajax/add_data/', removeClass = false;

								if ($(this).parents('li').hasClass('active'))
								{
									url =  G_BASE_URL + '/help/ajax/remove_data/';

									removeClass = true;
								}

								$.post(url,
									{
										'item_id' : data.item_id,
										'id' : _this.attr('data-id'),
										'title' : _this.text(),
										'type' : data.type
									}, function (result)
									{
										if (result.errno == 1)
										{
											if (removeClass)
											{
												_this.parents('li').removeClass('active');
											}
											else
											{
												$('.aw-recommend-box ul li').removeClass('active');

												_this.parents('li').addClass('active');
											}
										}
									}, 'json');
							});
						}
						else
						{
							$('.error_message').html(_t('请先去后台创建好章节'));

							if ($('.error_message').css('display') != 'none')
							{
								AWS.shake($('.error_message'));
							}
							else
							{
								$('.error_message').fadeIn();
							}
						}
					}, 'json');
					break;
			}
		}
	},
	
	/*加载数据换一换*/
	load_ajax_view:function (url,container,clickElement)
	{
		var page = 2;
		clickElement.click(function()
		{
			AWS.loading('show');
			var _this = this;
			AWS.ajaxRequest(url, 'get', 'html', {page: page}, function (res)
			{
				if ($.trim(res) != '')
				{
					page++;
					container.html(res);
					AWS.loading('hide');
				}
				else
				{
					page = 1;
					$(_this).click();
					AWS.loading('hide');
				}
			});

			flyLoad();
		});
	}
}
// 全局变量
AWS.G =
{
	loading_timer: '',
	loading_bg_count: 12,
	aw_dropdown_list_interval: '',
	aw_dropdown_list_flag: 0,
	search_val: '',
	sign_login_timer: '',
	dropdown_list_xhr: '',
}

AWS.User =
{
	// 邀请用户回答问题
	invite_user: function(selector, question_id)
	{
		$.post(G_BASE_URL + '/question/ajax/save_invite/',
	    {
	        'question_id': question_id,
	        'uid': selector.attr('data-uid')
	    }, function (result)
	    {
	    	if (result.errno != -1)
	    	{
				selector.addClass('aw-ygz');
				selector.html('已邀请');
				layer.msg('邀请已发送');
	    	}
	    	else if (result.errno == -1)
	        {
	            layer.msg(result.err);
	        }
	    }, 'json');
	},

	// 取消邀请用户回答问题
	disinvite_user: function (selector,question_id)
	{
	    $.get(G_BASE_URL + '/question/ajax/cancel_question_invite/question_id-' + question_id + "__recipients_uid-" + selector.attr('data-uid'), function (result)
		{
			if (result.errno != -1)
	        {
				selector.removeClass('aw-ygz');
				selector.html('+ 邀请');
				layer.msg('已取消邀请');
	        }
		});
	},

	/*删除草稿*/
	delete_draft:function(type,item_id){
		$.post(G_BASE_URL + '/account/ajax/delete_draft/',
			{
				'type': type,
				'item_id': item_id
			}, function (result)
			{
				layer.msg('删除草稿成功',{},function(){
					window.location.reload();
				});
			}, 'json');
	},

	// 关注
	follow: function(selector, type, data_id)
	{
		AWS.loading('show');
		if (!selector.hasClass('disable'))
        {
			selector.html('+ '+_t('关注'));
        }
        else
        {
			selector.html(_t('已关注'));
        }
	    switch (type)
		{
			case 'question':
				var url = '/question/ajax/focus/';

				var data = {
					'question_id': data_id
				};
				break;

			case 'topic':
				var url = '/topic/ajax/focus_topic/';
				var data = {
					'topic_id': data_id
				};
				break;

			case 'user':
				var url = '/follow/ajax/follow_people/';

				var data = {
					'uid': data_id
				};
				break;
		}

		$.post(G_BASE_URL + url, data, function (result)
		{
			if (result.errno == 1)
			{
				if (result.rsm.type == 'add')
				{
					selector.addClass('disable');
					selector.html(_t('已关注'));
				}
				else
				{
					selector.removeClass('disable');
					selector.html('+ '+_t('关注'));
				}
			}
			else
			{
				if (result.err)
				{
					AWS.alert(result.err);
				}
				if (result.rsm.url)
				{
					window.location = decodeURIComponent(result.rsm.url);
				}
			}
			AWS.loading('hide');
			selector.removeClass('disabled');
		}, 'json');
	},

	// 收藏
	favorite: function(type, id,e)
	{
		if(type == "article" && $(e).hasClass("active") ){
			$.post(G_BASE_URL + '/favorite/ajax/remove_favorite_item/', {'item_type': type, 'item_id': id}, function (result){
	            $(e).removeClass("active");
			}, 'json');
		}else{
			if(type == "answer" && $(e).parent().hasClass("active")){
                $.post(G_BASE_URL + '/favorite/ajax/remove_favorite_item/', {'item_type': type, 'item_id': id}, function (result){
                    $(e).parent().removeClass("active");
                }, 'json');
			}else{
                $.post(G_BASE_URL + '/favorite/ajax/update_favorite_tag/', {'item_type': type, 'item_id': id}, function (result){
                    if (type == "article") {
                        $(e).hasClass("active") ? $(e).removeClass("active") : $(e).addClass("active");
                    } else {
                        $(e).parent().hasClass("active") ? $(e).parent().removeClass("active") : $(e).parent().addClass("active");
                    }
                }, 'json');
			}
		}
	},

	// 提交评论
	save_comment: function (selector)
	{
	    selector.attr('_onclick', selector.attr('onclick')).addClass('disabled').removeAttr('onclick').addClass('_save_comment');

	    AWS.ajax_post(selector.parents('form'));
	},

	// 删除评论
	remove_comment: function (selector, type, comment_id)
	{
		$.get(G_BASE_URL + '/question/ajax/remove_comment/type-' + type + '__comment_id-' + comment_id, function (result)
		{
			if(type == 'answer'){console.log(result.rsm);
	        	$('#count_comment'+result.rsm.answer_id).html(parseInt($('#count_comment'+result.rsm.answer_id).html())-parseInt(1));
	        }
		}, 'json');
		selector.parents('.aw-comment-box li').fadeOut();
	},

	// 问题感谢
	question_thanks: function (selector, question_id)
	{
	    $.post(G_BASE_URL + '/question/ajax/question_thanks/', 'question_id=' + question_id, function (result)
	    {
	        if (result.errno != 1)
	        {
	            layer.msg(result.err);
	        }
	        else if (result.rsm.action == 'add')
	        {
	            selector.html(selector.html().replace(_t('感谢'), _t('已感谢'))).removeAttr('onclick').addClass('active');
	        }
	        else
	        {
	            selector.html(selector.html().replace(_t('已感谢'), _t('感谢'))).removeClass('active');
	        }
	    }, 'json');
	},

	// 感谢回复者
	answer_user_rate: function (selector, type, answer_id)
	{
	    $.post(G_BASE_URL + '/question/ajax/question_answer_rate/', 'type=' + type + '&answer_id=' + answer_id, function (result)
	    {
	        if (result.errno != 1)
	        {
	            alert(result.err);
	        }
	        else if (result.errno == 1)
	        {
	            switch (type)
	            {
		            case 'thanks':
		                if (result.rsm.action == 'add')
		                {
							selector.find('dd a').html(selector.find('dd a').html().replace(_t('感谢'), _t('已感谢'))).removeAttr('onclick');
							selector.addClass('active');
		                }
		                else
		                {
							selector.find('dd a').html(selector.find('dd a').html().replace(_t('已感谢'), _t('感谢')));
							selector.removeClass('active');
		                }
		                break;

		            case 'uninterested':
		                if (result.rsm.action == 'add')
		                {
							selector.find('dd a').html(_t('撤消没有帮助'));
		                }
		                else
		                {
							selector.find('dd a').html(_t('没有帮助'));
		                }
		                break;
	            }
	        }
	    }, 'json');
	},

	//赞成投票
	agree_vote: function (selector, answer_id,user_name)
	{
		$.post(G_BASE_URL + '/question/ajax/answer_vote/', 'answer_id=' + answer_id + '&value=1', function (result) {

            if (result.errno == 1)
            {
				// 判断是否投票过
				if (selector.hasClass('active'))
				{
					$.each(selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user .aw-user-name'), function (i, e)
					{
						if ($(e).html() == user_name)
						{
							if ($(e).prev())
							{
								$(e).prev().remove();
							}
							else
							{
								$(e).next().remove();
							}
							$(e).remove();
						}
					});

					if (selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user .aw-user-name').length == 0)
					{
						selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user').empty();
					}

					$(selector).removeClass('active');
					if (parseInt(selector.find('b').html()) != 0)
					{
						selector.find('b').html(parseInt(selector.find('b').html()) - 1);
					}
				}else{
					selector.addClass('active');
					if (selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user .aw-user-name').length == 0)
					{
						selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user').empty().append('赞同来自:<a class="aw-user-name">' + user_name + '</a>');
					}
					else
					{
						selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user').append('<em>、</em><a class="aw-user-name">' + user_name + '</a>');
					}
					selector.find('b').html(parseInt(selector.find('b').html()) + 1);
					selector.parent().find('span.against').removeClass('active');
				}
            }else{
                layer.msg(result.err);
			}
		},'json');
	},

	//反对投票
	disagree_vote: function (selector, answer_id,user_name)
	{
	    $.post(G_BASE_URL + '/question/ajax/answer_vote/', 'answer_id=' + answer_id + '&value=-1', function (result) {
			if (result.errno == 1) {
				if ($(selector).hasClass('active'))
				{
					$(selector).removeClass('active');
				}
				else
				{
					// 判断是否有赞同过
					if (selector.parents('.aw-xsxq-czl').find('.agree').hasClass('active'))
					{
						// 删除赞同操作
						$.each(selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user .aw-user-name'), function (i, e)
						{
							if ($(e).html() == user_name)
							{
								if ($(e).prev('em'))
								{
									$(e).prev('em').remove();
								}
								else
								{
									$(e).next('em').remove();
								}

								$(e).remove();
							}
						});
						// 判断赞同来自内是否有人
						if (selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user a').length == 0)
						{
							selector.parents('.aw-xsxq-hfb').find('.aw-answer-agree-user').empty();
						}
						selector.parents('.aw-xsxq-czl').find('.agree b').html(parseInt(selector.parents('.aw-xsxq-czl').find('.agree b').html()) - 1);
						selector.parents('.aw-xsxq-czl').find('.agree').removeClass('active');
						selector.addClass('active');
					}
					else
					{
						selector.addClass('active');
					}
				}
			}else{
				layer.msg(result.err);
			}
		},'json');
	},

	// 文章赞同
	article_vote: function (selector, article_id, rating,type)
	{
		AWS.loading('show');
		if (selector.hasClass('active'))
		{
			 rating = 0;
		}
		$.post(G_BASE_URL + '/article/ajax/article_vote/', 'type=article&item_id=' + article_id + '&rating=' + rating, function (result)
		{
			AWS.loading('hide');
			if (result.errno != 1)
		    {
		        AWS.alert(result.err);
		    }
		    else
		    {
		    	if (rating == 0)
		    	{
		    		if(selector.parents('.aw-article-vote').find('.agree').hasClass('active') || selector.hasClass('active'))
		    		{
		    			if(type==1)
						{
							$('.aw-article-bottom-btn').find('.agree').removeClass('active');
							$('.aw-article-bottom-btn').find('.agree b').html(parseInt(selector.find('b').html()) - 1);
						}
						if(type==2)
						{
							$('.aw-article-vote').find('.agree').removeClass('active');
							$('.aw-article-vote').find('.agree b').html(parseInt(selector.find('b').html()) - 1);
						}
						selector.removeClass('active');
						selector.find('b').html(parseInt(selector.find('b').html()) - 1);
					}

					if(selector.parents('.aw-article-vote').find('.against').hasClass('active'))
					{
						selector.parents('.aw-article-vote').find('.against').removeClass('active');
					}
		    	}
		    	else if (rating == 1)
		    	{
		    		if (selector.parents('.aw-article-vote').find('.against').hasClass('active'))
		    		{
						selector.parents('.aw-article-vote').find('.against').removeClass('active');
		    		}
		    		else
		    		{
						if(type==1)
						{
							$('.aw-article-bottom-btn').find('.agree').addClass('active');
							$('.aw-article-bottom-btn').find('.agree b').html(parseInt(selector.find('b').html()) + 1);
						}
						if(type==2)
						{
							$('.aw-article-vote').find('.agree').addClass('active');
							$('.aw-article-vote').find('.agree b').html(parseInt(selector.find('b').html()) + 1);
						}

		    			selector.find('b').html(parseInt(selector.find('b').html()) + 1);
		    			selector.addClass('active');
		    		}
		    	}
		    	else
		    	{
		    		if (selector.parents('.aw-article-vote').find('.agree').hasClass('active'))
		    		{
						if(type==1)
						{
							$('.aw-article-bottom-btn').find('.agree').removeClass('active');
						}
						if(type==2)
						{
							$('.aw-article-vote').find('.agree').removeClass('active');
						}
						selector.parents('.aw-article-vote').find('.agree').removeClass('active');
		    		}
		    		else
		    		{
						selector.addClass('active');
		    		}
		    	}
				layer.msg('操作成功');
		    }
		}, 'json');
	},

	//文章评论赞同
	article_comment_vote: function (selector, comment_id, rating)
	{
		if (selector.parent().hasClass('active'))
		{
			var rating = 0;
		}
		$.post(G_BASE_URL + '/article/ajax/article_vote/', 'type=comment&item_id=' + comment_id + '&rating=' + rating, function (result)
		{
			AWS.loading('hide');
			if (result.errno != 1)
		    {
                layer.msg(result.err);
		    }
		    else
		    {
				if (rating == 0)
				{
					selector.removeClass('active');
					selector.find('b').html(parseInt(selector.find('b').html()) - 1);
					layer.msg('取消点赞',{},function () {
						window.location.reload();
					});
				}
				else
				{
					selector.addClass('active');
					selector.find('b').html(parseInt(selector.find('b').html()) + 1);
					layer.msg('点赞成功');
				}
		    }
		}, 'json');
	},

	focus_column:function (selector,data_id)
	{
		var data = {'column_id': data_id};
		$.post(G_BASE_URL + '/column/ajax/focus_column/', data, function (result)
		{
			if (result.errno == 1)
			{
				if (result.rsm.type == 'add')
				{
					selector.html('已关注');
					selector.addClass('disable');
					layer.msg('关注成功！');
				}
				else
				{
					selector.html('+ '+_t('关注'));
					selector.removeClass('disable');
					layer.msg('取消关注成功！');
				}
			}
			else
			{
				if (result.err)
				{
					layer.msg(result.err);
				}

				if (result.rsm.url)
				{
					window.location = decodeURIComponent(result.rsm.url);
				}
			}
		}, 'json');
	},

	// 回复折叠
	answer_force_fold: function(selector, answer_id)
	{
		$.post(G_BASE_URL + '/question/ajax/answer_force_fold/', 'answer_id=' + answer_id, function (result) {
			if (result.errno != 1)
			{
				layer.msg(result.err);
			}
			else if (result.errno == 1)
			{
				if (result.rsm.action == 'fold')
				{
					selector.find('dd a').html('撤消折叠');
				}
				else
				{
					selector.find('dd a').html('折叠');
				}
			}
		}, 'json');
	},

	// 创建收藏标签
	add_favorite_tag: function()
	{
		var tag = $('#favorite_form .add-input').val();
		if(!tag){
			layer.msg('请输入标签名字');
			return false;
		}
		$('.add_favorite_tags').hide();
		$('.aw-favorite-tag-list ul').prepend('<li class="active"><input type="hidden" value="'+tag+'" name="tags[]"><a data-value="' + tag + '"><span class="title">' + tag + '</span></a><i class="icon icon-followed"></i></li>');
		$('.aw-favorite-box').show();
		$('#favorite_form .add-input').val('');
	},

	add_favorite_success: function(formEl, type)
	{
		var taglist = $('.aw-favorite-tag-list ul').html();
		if(!taglist){
			layer.msg('请先创建标签');
			return false;
		}
		var custom_data = {
			_post_type: 'ajax'
		};

		formEl.ajaxSubmit(
			{
				dataType: 'json',
				data: custom_data,
				success: function (result)
				{
					if (result.errno == 1)
					{
						layer.msg('收藏成功',{},function () {
							$('#aw-ajax-box,.modal-backdrop ').hide();
							window.location.reload();
						});
					}
				},
				error: function (error)
				{
					if ($.trim(error.responseText) != '')
					{
						AWS.loading('hide');
						alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
					}
					else if (error.status == 0)
					{
						AWS.loading('hide');
						alert(_t('网络链接异常'));
					}
					else if (error.status == 500)
					{
						AWS.loading('hide');
						alert(_t('内部服务器错误'));
					}
				}
			});
	},

	/*添加话题到待选择框*/
	add_topic:function(element,title,topic_id)
	{
		var flag=0;
		$('#aw-ajax-box .aw-select-top .aw-topic-item').each(function(i,val)
		{
			var id = $(val).data('id');
			if(id==topic_id)
			{
				flag =1;
				return true;
			}
		});
		if(!flag)
		{
			element.parents('.aw-topic-toast').find('.aw-select-top').append('<span class="aw-topic-item" data-id="'+topic_id+'"><input type="hidden" name="topics[]" value="'+title+ '" class="topic-input"> '+title+ '</span>');
		}
		element.parents('li').remove();
	},

	/*取消收藏*/
	remove_favorite:function (item_type,item_id) {
		$.post(G_BASE_URL + '/favorite/ajax/remove_favorite_item/',{
			item_type:item_type,
			item_id:item_id
		}, function (result){
			layer.msg('取消成功',{},function () {
				window.location.reload();
			})
		}, 'json');
	},

	// 阅读通知
	read_notification: function(selector, notification_id , reload)
	{
		if (notification_id)
		{
			var url = G_BASE_URL + '/notifications/ajax/read_notification/notification_id-' + notification_id;
		}else{
			var url = G_BASE_URL + '/notifications/ajax/read_notification/';
		}
		AWS.loading('show');
		$.get(url, function (result)
		{
			AWS.loading('hide');
			if(notification_id)
			{
				window.location.reload();
			}
			if (reload)
			{
				window.location.reload();
			}
		},'json');
	},
}

AWS.Dropdown =
{
	// 下拉菜单功能绑定
	bind_dropdown_list: function(element, type,item_id)
	{
		var ul = $(element).parent().find('.aw-toast ul');
		$(element).keydown(function()
		{
			if (AWS.G.aw_dropdown_list_flag === 0)
			{
				AWS.G.aw_dropdown_list_interval = setInterval(function()
				{
					var val = $(element).val();
					if (val.length >= 2)
					{
						switch (type)
						{
							case 'search' :
								ul = $('#search_result');
								if (val != AWS.G.search_val)
								{
									$.get(G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent(val) + '&limit=5',function(result)
									{
										if (result.length > 0)
										{
											ul.html('');
											$.each(result, function(i, e)
											{
												switch(result[i].type)
												{
													case 'questions' :
														ul.append('<li class="question"><a href="' + decodeURIComponent(result[i].url) + '">' + result[i].name + '&nbsp;<span class="color-999">' + result[i].detail.answer_count + ' 个回答</span></a></li>');
														break;

													case 'articles' :
														ul.append('<li class="question"><a href="' + decodeURIComponent(result[i].url) + '">' + result[i].name + '&nbsp;<span class="color-999">' + result[i].detail.comments + ' 个评论</span></a></li>');
														break;

													case 'topics' :
														ul.append('<li class="topic"><span class="topic-tag"><a class="text" href="' + decodeURIComponent(result[i].url) + '">' + result[i].name  + '</a></span>&nbsp;<span class="color-999">' + result[i].detail.discuss_count + ' 个讨论</span></li>');
														break;

													case 'users' :
														ul.append('<li class="user"><a href="' + decodeURIComponent(result[i].url) + '"><img class="img" width="25" src="' + result[i].detail.avatar_file + '" /> <span>' + result[i].name + '</span></a></li>');
														break;
												}
											});

											ul.show();
											AWS.G.search_val = val;
										}
									},'json');
								}
							break;

							case 'invite' :
								ul = $('.aw-invite-result');
								$.get(G_BASE_URL + '/search/ajax/search/?type=users&q=' + encodeURIComponent($(element).val()) + '&limit=10',function(result)
								{
									if (result.length > 0)
									{
										ul.html('');
										$.each(result ,function(i, e)
										{
											ul.append('<li class="clearfix">\n' +
											'                    <a href="people/' + result[i].uid + '">\n' +
											'                        <img src="' + result[i].detail.avatar_file + '" class="aw-tb-div-l">' +
											'                    </a>\n' +
											'                    <div class="aw-tb-div-c">\n' +
											'                        <b>\n' +
											'                            <a href="people/' + result[i].uid + '">' + result[i].name + '</a>\n' +
											'                        </b>\n' +
											'                        <i>总共获得 ' + result[i].detail.agree_count + ' 个赞同</i>\n' +
											'                    </div>\n' +
											'                    <button type="button" class="aw-wgz" onclick="AWS.User.invite_user($(this),'+item_id+')" data-uid="' + result[i].uid + '">邀请</button>\n' +
											'                </li>');
											//ul.append('<li><a data-id="' + result[i].uid + '" data-value="' + result[i].name + '"><img class="img" width="25" src="' + result[i].detail.avatar_file + '"> ' + result[i].name + '</a></li>')
										});
										$('.aw-invite-box ul li a').click(function()
										{
											AWS.User.invite_user($(this),$(this).parents('li').find('img').attr('src'));
										});
										$(element).parent().find('.aw-dropdown-list').show();
										ul.show();
									}else
									{
										$(element).parent().find('.aw-dropdown-list').hide();
									}
								},'json');
							break;

							case 'topic' :
								ul = $('.aw-topic-result');

								$.get(G_BASE_URL + '/search/ajax/search/?type=topics&q=' + encodeURIComponent($(element).val()) + '&limit=10',function(result)
								{
									if (result.length > 0)
									{
										ul.html('');
										$.each(result ,function(i, e)
										{
											ul.append('<li class="clearfix"><a href="' + result[i].url + '"><img src="' + result[i].detail.topic_pic + '" class="aw-tb-div-l"></a><div class="aw-tb-div-c"><b><a href="' + result[i].url + '">' + result[i].name + '</a></b><i class="aw-one-line">'+_t('关注')+result[i].detail.focus_count+' . '+_t('讨论') + result[i].detail.discuss_count + ' </i></div><button type="button" class="aw-wgz" data-title="' + result[i].name + '" onclick="AWS.User.add_topic($(this),\'' + result[i].name + '\','+ result[i].search_id+');">'+_t('添加')+'</button></li>');
										});
									}else
									{
										$(element).parent().find('.aw-dropdown-list').hide();
									}
								},'json');

							break;
						}
					}
				},1000);
				AWS.G.aw_dropdown_list_flag = 1;
			}
		});

		$(element).blur(function()
		{
			clearInterval(AWS.G.aw_dropdown_list_interval);
			AWS.G.aw_dropdown_list_flag = 0;
		});
	},

	// 插入下拉菜单
	set_dropdown_list: function (selecter, data, selected)
	{
	    $(selecter).append(Hogan.compile(Aws_mobile_template.dropdownList).render(
	    {
	        'items': data
	    }));

	    $(selecter + ' .dropdown-menu li a').click(function ()
	    {
	        $('.aw-publish-dropdown span').html($(this).text());
	    });

	    if (selected)
	    {
	        $(selecter + " .dropdown-menu li a[data-value='" + selected + "']").click();
	    }
	}
}

AWS.Init =
{
	init_topic_edit_box: function (selector, type)
	{   
		$(selector).click(function()
		{
			var data_id = $(this).parents('.aw-topic-bar').attr('data-id'), data_type;

			if (type)
			{
				data_type = type;
			}
			else
			{
				data_type = $(this).parents('.aw-topic-bar').attr('data-type');
			}

			$('#aw-ajax-box').html(AWS_MOBILE_TEMPLATE.editTopicBox).show();

			var html = $('#select-topic-container').html();
			if(html)
			{
				$('#aw-ajax-box').find('.aw-select-top').append(html);
			}

			//获取最近使用的话题
			AWS.ajaxRequest(G_BASE_URL + '/m/ajax/get_user_recent_topics/','post','html',{type:data_type,item_id:data_id},function(result)
			{
				$('#aw-ajax-box').find('.aw-topic-toast').find('.aw-topic-result').html(result);
			});

			//搜索话题监控
			AWS.Dropdown.bind_dropdown_list('.aw-topic-toast input', 'topic');

			/*删除添加的话题*/
			$(document).on('click', '#aw-ajax-box  .aw-topic-item', function () {
				var _this =$(this);
				if(!data_id && !data_type)
				{
					_this.remove();
					return  false;
				}
				$.post(G_BASE_URL + '/topic/ajax/remove_topic_relation/', {
					'type': data_type,
					'item_id': data_id,
					'topic_id': _this.data('id')
				}, function (result) {
					if (result.errno == 1) {
						_this.remove();
					} else {
						layer.msg(result.err);
					}
				}, 'json');
				return false;
			});

			/* 话题编辑添加按钮 */
			$(document).on('click', '#aw-ajax-box .aw-select-done', function () {
				$('#aw-ajax-box .topic-input').each(function(i,val)
				{
					switch (data_type)
					{
						case 'publish' :
							break;

						case 'question' :
							$.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=question&item_id=' + data_id + '&topic_title=' + encodeURIComponent($(val).val()), function(result)
							{
								if ($('.no-topic').length > 0 ) {
									$('.no-topic').hide();
								}
							}, 'json');
							break;

						case 'article' :
							$.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=article&item_id=' + data_id + '&topic_title=' + encodeURIComponent($(val).val()), function (result)
							{
							}, 'json');
							break;
					}
				});
				var html = $('#aw-ajax-box .aw-topic-toast .aw-select-top').html();
				$('.aw-topic-toast').hide();
				$('#select-topic-container').html( html);
			});

			// 是否允许创建新话题
	        if (!G_CAN_CREATE_TOPIC)
	        {
				//$('#aw-ajax-box').find('.aw-topic-result button').hide();
	        }
		});
	},

	init_comment_box: function (selector)
	{
	    $(document).on('click', selector, function ()
	    {
	        if (!$(this).attr('data-type') || !$(this).attr('data-id'))
	        {
	            return true;
	        }

	        var comment_box_id = '#aw-comment-box-' + $(this).attr('data-type') + '-' + 　$(this).attr('data-id');

	        if ($(comment_box_id).length > 0)
	        {
	            if ($(comment_box_id).css('display') == 'none')
	            {
	                $(comment_box_id).fadeIn();
	                $(this).parent().addClass('active');
	            }
	            else
	            {
	                $(comment_box_id).fadeOut();
                    $(this).parent().removeClass('active');
	            }
	        }
	        else
	        {
                $(this).parent().addClass('active');

	            // 动态插入commentBox
	            switch ($(this).attr('data-type'))
	            {
		            case 'question':
		                var comment_form_action = G_BASE_URL + '/question/ajax/save_question_comment/question_id-' + $(this).attr('data-id');
		                var comment_data_url = G_BASE_URL + '/question/ajax/get_question_comments/question_id-' + $(this).attr('data-id');
		                break;

		            case 'answer':
		                var comment_form_action = G_BASE_URL + '/question/ajax/save_answer_comment/answer_id-' + $(this).attr('data-id');
		                var comment_data_url = G_BASE_URL + '/question/ajax/get_answer_comments/answer_id-' + $(this).attr('data-id');
		                break;
	            }

	            if (G_USER_ID && $(this).attr('data-close') != 'true')
	            {
	                $(this).parents('.mod-footer').append(Hogan.compile(Aws_mobile_template.commentBox).render(
	                {
	                    'comment_form_id': comment_box_id.replace('#', ''),
	                    'comment_form_action': comment_form_action
	                }));

	                $(comment_box_id).find('.cancel').click(function ()
	                {
	                    $(comment_box_id).fadeOut();
                        $(selector).parent().removeClass('active');
	                });

	                $(comment_box_id).find('.aw-comment-txt').autosize();
	            }
	            else
	            {
	                $(this).parents('.mod-footer').append(Hogan.compile(Aws_mobile_template.commentBoxClose).render(
	                {
	                    'comment_form_id': comment_box_id.replace('#', ''),
	                    'comment_form_action': comment_form_action
	                }));
	            }

	            //判断是否有评论数据
	            $.get(comment_data_url, function (result)
	            {
	                if ($.trim(result) == '')
	                {
	                    result = '<p class="text-center margin-0">' + _t('暂无评论') + '</p>';
	                }

	                $(comment_box_id).find('.aw-comment-list').html(result);
	            });
                $(this).parents('.mod-footer').find('i').css('left', 55);
                $(this).parents('.mod-footer').find('i.aaw-add-commentctive').css('left', 55);
	        }
	    });
	},

	init_article_comment_box: function (selector)
	{
		$(document).on('click', selector, function ()
	    {
	        if ($(this).parents('.mod-footer').find('.aw-comment-box').length)
	        {
	            if ($(this).parents('.mod-footer').find('.aw-comment-box').css('display') == 'block')
	            {
	               $(this).parents('.mod-footer').find('.aw-comment-box').fadeOut();
	               $(this).removeClass('active');
	            }
	            else
	            {
	                $(this).parents('.mod-footer').find('.aw-comment-box').fadeIn();
	                $(this).addClass('active');
	            }
	        }
	        else
	        {
	        	$(this).addClass('active');

	            $(this).parents('.mod-footer').append(Hogan.compile(Aws_mobile_template.articleCommentBox).render(
	            {
	                'at_uid' : $(this).attr('data-id'),
	                'article_id' : $('.aw-replay-box input[name="article_id"]').val()
	            }));
	            $(this).parents('.mod-footer').find('.cancel').click(function ()
	            {
	                $(this).parents('.mod-footer').find('.aw-comment-box').fadeOut();
                    $(selector).removeClass('active');
	            });
	            $(this).parents('.mod-footer').find('.aw-comment-txt').autosize();

	            /*给三角形定位*/
                $(this).parents('.mod-footer').find('i').css('left', 56 - $(this).width() / 2);
                $(this).parents('.mod-footer').find('i.aaw-add-commentctive').css('left', 56 - $(this).width() / 2 - 1);
	        }
	    });
	}
}

function _t(string, replace)
{
	if (typeof (aws_lang) != 'undefined')
	{
		if (typeof (aws_lang[string]) != 'undefined')
		{
			string = aws_lang[string];
		}
	}
	if (replace)
	{
		string = string.replace('%s', replace);
	}
	return string;
};

(function ($){
	$.fn.extend(
    {
    	insertAtCaret: function (textFeildValue)
	    {
	        var textObj = $(this).get(0);
	        if (document.all && textObj.createTextRange && textObj.caretPos)
	        {
	            var caretPos = textObj.caretPos;
	            caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == '' ?
	                textFeildValue + '' : textFeildValue;
	        }
	        else if (textObj.setSelectionRange)
	        {
	            var rangeStart = textObj.selectionStart;
	            var rangeEnd = textObj.selectionEnd;
	            var tempStr1 = textObj.value.substring(0, rangeStart);
	            var tempStr2 = textObj.value.substring(rangeEnd);
	            textObj.value = tempStr1 + textFeildValue + tempStr2;
	            textObj.focus();
	            var len = textFeildValue.length;
	            textObj.setSelectionRange(rangeStart + len, rangeStart + len);
	            textObj.blur();
	        }
	        else
	        {
	            textObj.value += textFeildValue;
	        }
	    },

	    highText: function (searchWords, htmlTag, tagClass)
	    {
	        return this.each(function ()
	        {
	            $(this).html(function high(replaced, search, htmlTag, tagClass)
	            {
	                var pattarn = search.replace(/\b(\w+)\b/g, "($1)").replace(/\s+/g, "|");

	                return replaced.replace(new RegExp(pattarn, "ig"), function (keyword)
	                {
	                    return $("<" + htmlTag + " class=" + tagClass + ">" + keyword + "</" + htmlTag + ">").outerHTML();
	                });
	            }($(this).text(), searchWords, htmlTag, tagClass));
	        });
	    },

	    outerHTML: function (s)
	    {
	        return (s) ? this.before(s).remove() : jQuery("<p>").append(this.eq(0).clone()).html();
	    },
    });

	$.extend(
	{
		// 滚动到指定位置
		scrollTo : function (type, duration, options)
		{
			if (typeof type == 'object')
			{
				var type = $(type).offset().top
			}

			$('html, body').animate({
				scrollTop: type
			}, {
				duration: duration,
				queue: options.queue
			});
		}
	})

})(jQuery);

function upload (params) {
    // 实例化XMLHttpRequest
    var xhr = new XMLHttpRequest();
    // post方式，url为服务器请求地址，true 该参数规定请求是否异步处理。
    xhr.open('post', params.url);
    // 请求完成
    xhr.onload = function (e) {
        if (this.readyState === 4 && this.status === 200) {
            params.success(this.response)
        } else {
            params.error({
                code: this.status,
                message: this.statusText
            })
        }
    }
    xhr.onerror = function (e) {
        params.error(e)
    }
    xhr.send(params.file)
}