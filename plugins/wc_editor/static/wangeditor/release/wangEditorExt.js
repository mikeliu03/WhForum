var _________FullEditor={};
window.wangEditor.fullscreen = {
    init: function(editor) {
        id = editor.id;
        _________FullEditor[id]=editor;
        editor.isFullScreen = false;
        toolbar = editor.$toolbarElem[0];
        $(toolbar).append('<div class="w-e-menu btn_fullscreen" title="全屏" onclick="window.wangEditor.fullscreen.run(\''+id+'\')"><img src=\'./plugins/wc_editor/static/wangeditor/release/full.svg\' style="width: 14px;height: 14px" title="全屏"> </div>');
    },
    run: function(id) {
        editor = _________FullEditor[id];
        editor.isFullScreen = editor.isFullScreen;
        container = $(editor.toolbarSelector);
        container.toggleClass('fullscreen-editor');
    }
};
var _______SourceEditor={};
window.wangEditor.viewSource = {
    init: function(editor) {
        id = editor.id;
        editor.isHTML = false;
        _______SourceEditor[id]=editor;
        toolbar = editor.$toolbarElem[0];
        $(toolbar).append("<div class='w-e-menu btn_viewSource' title='查看源码' onclick='window.wangEditor.viewSource.run(\""+id+"\")'><img src='./plugins/wc_editor/static/wangeditor/release/source.svg' width='14' height='14'> </div>");
    },
    run: function(id) {
        editor = _______SourceEditor[id];
        editor.isHTML = !editor.isHTML;
        _source = editor.txt.html();
        toolbar = editor.$toolbarElem[0];
        if (editor.isHTML) {
            _source = _source.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/ /g, "&nbsp;");
            $(toolbar).find('.w-e-menu').css({ "display": "none" });
            $(toolbar).find('.btn_viewSource').css({ "display": "" });
        } else {
            _source = editor.txt.text().replace(/&lt;/ig, "<").replace(/&gt;/ig, ">").replace(/&nbsp;/ig, " ");
            $(toolbar).find('.w-e-menu').css({ "display": "" });
            editor.change && editor.change();
        }
        editor.txt.html(_source);
    }
};