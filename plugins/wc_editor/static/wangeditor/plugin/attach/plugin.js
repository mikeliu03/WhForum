window.wangEditor.attach = {
    init: function(editorSelector,cat){
        $(editorSelector + " .w-e-toolbar").append('<div class="w-e-menu"><input id="_upfile" type="file" style="display:none;" onchange="window.wangEditor.attach.callback(this.files[0],\''+cat+'\')" /><a class="_wangEditor_btn_fullscreen" href="javascript:;" onclick="window.wangEditor.attach.upfile()"><i title="上传附件" class="w-e-icon-upload2"></i></a></div>');
    },
    upfile:function(){
        if(!upload_ass){
            layer.msg('没有上传权限');
        }else{

        $("#_upfile").click();
        }
    },
    callback:function(files,cat){
        const $file = $('#_upfile');
        const fileElem = $file[0];
        const fileList = fileElem.files;
        const uploadImg = editor.uploadImg;

        editor.customConfig.uploadImgServer= "/?/explore/doact/?p=editor&a=upload_file&cat="+cat;
        uploadImg.uploadFile(fileList);

    }

};