var AWS_MOBILE_TEMPLATE = {
	'confirmBox':
		'<div class="aw-toast aw-toast-confirm">' +
		'    <div class="aw-toast-hf">' +
		'        <div class="aw-toast-title">' + '{{message}}' + '</div>' +
		'        <div class="aw-toast-no">' + _t('取消')+'' +'</div>' +
		'        <div class="aw-toast-yes">' + _t('确定') + '</div>' +
		'    </div>' +
		'    <div class="aw-bg"></div>' +
		'</div>',
	'favoriteBox' :
		'<div class="aw-toast aw-favorite-toast">' +
		'    <div class="aw-toast-hf aw-top-more-toast">' +
		'        <form id="favorite_form" action="'+ G_BASE_URL+'/favorite/ajax/update_favorite_tag/" method="post" onsubmit="return false;">' +
		'            <input type="hidden" name="item_id" value="{{item_id}}" />' +
		'            <input type="hidden" name="item_type" value="{{item_type}}" />' +
		'            <div class="add_favorite_tags" style="display: none">' +
		'                <input type="text" id="add_favorite_tags" class="add-input" placeholder="请输入标签名称"/>' +
		'                <a href="javascript:;" onclick="AWS.User.add_favorite_tag()">创建标签</a>' +
		'            </div>' +
		'            <div class="aw-favorite-box">' +
		'                <div class="aw-add-favorite">' +
		'                    <a href="javascript:;">+ 新建收藏夹</a>' +
		'                </div>' +
		'                <div class="aw-favorite-tag-list">' +
		'                    <ul>' +
		'                        ' +
		'                    </ul>' +
		'                </div>' +
		'                <div class="aw-submit-favorite">' +
		'                    <a href="javascript:;" onclick="AWS.User.add_favorite_success($(\'#favorite_form\'), \'favorite_message\');">确认</a>' +
		'                </div>' +
		'            </div>' +
		'        </form>' +
		'    </div>' +
		'    <div class="aw-bg"></div>' +
		'</div>',
	'reportBox':
		'<div class="aw-toast aw-report-toast">' +
		'    <div class="aw-toast-hf aw-top-more-toast">' +
		'        <form id="quick_publish" method="post" action="' + G_BASE_URL + '/question/ajax/save_report/">' +
		'            <input type="hidden" name="type" value="{{item_type}}" />' +
		'            <input type="hidden" name="target_id" value="{{item_id}}" />' +
		'            <div class="modal-body">' +
		'                <div class="dropdown reportBox">' +
		'                    <div class="dropdown" style="margin-bottom: 5px">' +
		'						<span id="aw-report-tags-select" class="aw-hide-txt">'+_t('选择举报理由')+'</span>' +
		'						<a style="float: right;" href="javascript:;"><i class="icon icon-down"></i></a>' +
		'					</div>' +
		'                    <ul class="dropdown-menu inner-box" style="height: 145px">' +
		'                        {{#item_reson}}' +
		'                        <li value="{{.}}"><a href="javascript:void(0);">{{.}}</a></li>' +
		'                        {{/item_reson}}' +
		'                        </ul>' +
		'                    </div>' +
		'                <div class="alert alert-danger collapse error_message"><em></em></div>' +
		'                <textarea class="form-control" name="reason" rows="5" placeholder="' + _t('请填写举报理由') + '..."></textarea>' +
		'            </div>' +
		'            <div class="modal-footer">' +
		'                <button class="save-report" id="quick_submit" onclick="AWS.ajax_post($(\'#quick_publish\'), AWS.ajax_processer, \'error_message\');return false;">' + _t('提交') + '</button>' +
		'            </div>' +
		'        </form>' +
		'    </div>' +
		'    <div class="aw-bg"></div>' +
		'</div>',
	'articleBox' :
		'<div class="aw-toast">' +
		'    <div class="aw-toast-hf aw-toast-article-comment ">' +
		'        <form action="'+G_BASE_URL+'/article/ajax/save_comment/" method="post" id="ajax-form" onsubmit="return false;">' +
		'            <div class="aw-toast-bt-top">' +
		'                <span>'+_t('发表评论')+ '</span>' +
		'				<i class="close-toast">关闭</i>'+
		'            </div>' +
		'            <div class="aw-toast-content">' +
		'                <input type="hidden" name="at_uid" value="{{at_uid}}}">'+
		'                <input type="hidden" name="post_hash" value="'+G_POST_HASH+'" />' +
		'                <input type="hidden" name="article_id" value="{{article_id}}}" />' +
		'                <textarea name="message" class="aw-hdxq-textarea" placeholder="输入评论内容" style="height: 112px;"></textarea>' +
		'            </div>' +
		'            <div class="aw-toast-bottom">' +
		'                <a href="javascript:;" onclick="AWS.ajax_post(\'#ajax-form\')">'+ _t('发表')+'</a>' +
		'            </div>' +
		'        </form>' +
		'    </div>' +
		'    <div class="aw-bg"></div>' +
		'</div>',
	'inbox':
	'<div class="aw-toast aw-inbox-toast" >' +
		'    <div class="aw-toast-hf aw-toast-inbox">' +
		'        <form action="' + G_BASE_URL + '/inbox/ajax/send/" method="post" id="quick_publish" onsubmit="return false">' +
		'            <input type="hidden" name="post_hash" value="'+G_POST_HASH+'" />' +
		'            <input type="hidden" name="recipient" value="{{recipient}}" />' +
		'            <div class="aw-toast-bt-top">' +
		'                <span>'+_t('与')+'{{recipient}}'+_t('对话')+'</span>' +
		'                <i onclick="$(\'.aw-inbox-toast\').hide();">'+ _t('关闭')+'</i>' +
		'            </div>' +
		'            <div class="inner-box">' +
		'                <textarea name="message" placeholder="私信内容"></textarea>' +
		'            </div>' +
		'            <div class="aw-toast-submit">' +
		'                <a href="javascript:;" onclick="AWS.ajax_post(\'#quick_publish\');">'+_t('发送')+'</a>' +
		'            </div>' +
		'        </form>' +
		'    </div>' +
		'    <div class="aw-bg"></div>' +
		'</div>',
	'editTopicBox':
	'<div class="aw-toast aw-topic-toast">' +
		'    <div class="aw-toast-hf aw-toast-bt">' +
		'        <div class="aw-toast-bt-top">' +
		'            <span>'+_t('编辑话题')+'</span>' +
		'            <i class="aw-select-done">'+ _t('完成')+'</i>' +
		'        </div>' +
		'        <div class="aw-toast-bt-ss">' +
		'            <i class="icon icon-search aw-toast-bt-sso"></i>' +
		'            <input type="text"  placeholder="搜索话题" />' +
		'        </div>' +
		'		<span style="font-size: 13px;color: #999;display: block">'+_t('已选话题：(点击已选话题可进行删除)')+'</span>'+
		'        <div class="aw-select-top" style="display: inline-block"></div>' +
		'        <div class="aw-toast-bt-content inner-box aw-yqhd-div aw-search-topic" style="margin-top: 10px;">' +
		'            <ul class="aw-topic-result"></ul>' +
		'        </div>' +
		'    </div>' +
		'    <div class="aw-bg"></div>' +
		'</div>',
	'answerCommentBox' :
		'<div class="aw-toast">' +
		'    <div class="aw-toast-hf aw-toast-article-comment">' +
		'        <form action="'+G_BASE_URL+'/question/ajax/save_answer_comment/answer_id-{{answer_id}}/" method="post" id="ajax-form" onsubmit="return false;">' +
		'            <div class="aw-toast-bt-top">' +
		'                <span>'+_t('发表评论')+ '</span>' +
		'				<i class="close-toast">关闭</i>'+
		'            </div>' +
		'            <div class="aw-toast-content">' +
		'                <input type="hidden" name="post_hash" value="'+G_POST_HASH+'" />' +
		'                <textarea name="message" class="aw-hdxq-textarea" placeholder="输入评论内容" style="height: 112px;">{{#at_user}}@{{at_user}}:{{/at_user}}</textarea>' +
		'            </div>' +
		'            <div class="aw-toast-bottom">' +
		'                <a href="javascript:;" onclick="AWS.ajax_post(\'#ajax-form\')">'+ _t('发表')+'</a>' +
		'            </div>' +
		'        </form>' +
		'    </div>' +
		'    <div class="aw-bg"></div>' +
		'</div>',
}