var document_title = document.title;
var browser={
    versions:function(){
        var u = navigator.userAgent, app = navigator.appVersion;
        return {
            trident: u.indexOf('Trident') > -1, //IE内核
            presto: u.indexOf('Presto') > -1, //opera内核
            webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
            gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1,//火狐内核
            mobile: !!u.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
            ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
            android: u.indexOf('Android') > -1 || u.indexOf('Adr') > -1, //android终端
            iPhone: u.indexOf('iPhone') > -1 , //是否为iPhone或者QQHD浏览器
            iPad: u.indexOf('iPad') > -1, //是否iPad
            webApp: u.indexOf('Safari') == -1, //是否web应该程序，没有头部与底部
            weixin: u.indexOf('MicroMessenger') > -1, //是否微信 （2015-01-22新增）
            qq: u.match(/\sQQ/i) == " qq" //是否QQ
        };
    }(),
    language:(navigator.browserLanguage || navigator.language).toLowerCase()
}
$(document).ready(function () {

    /*点击空白地方关闭弹窗*/
    $(document).on('click', '.aw-bg', function () {
        $(this).parent('.aw-toast').hide();
        $('#aw-ajax-box').empty();
    });
    /*关闭弹窗*/
    $(document).on('click', '.close-toast', function () {
        $(this).parents().find('.aw-toast').hide();
        $('#aw-ajax-box').empty();
    });
    /*打开顶部弹窗*/
    $(document).on('click', '.aw-top-div', function () {
        $(this).parents().find('.aw-top-toast').toggle();
    });

    /*打开回复弹窗*/
    $(document).on('click', '.aw-answer-more', function () {
        $(this).parents('.aw-x-h-t').find('.aw-answer-toast').toggle();
    });

    /*确认对话框取消*/
    $(document).on('click', '.aw-toast-confirm .aw-toast-no', function () {
        $(this).parents().find('.aw-toast-confirm').hide();
        $('#aw-ajax-box').empty();
    });

    /*发起按钮*/
    $(document).on('click', '.publish-box', function () {
        if($(this).hasClass("rotate-before")){
            $(this).removeClass("rotate-before").addClass("rotate-after");
        }else{
            $(this).removeClass("rotate-after").addClass("rotate-before");
        }
        $(this).parents().find('.aw-common-publish-box').toggle();
    });

    // 手动保存草稿
    $(document).on('click', '#saveDraft', function ()
    {
        var itemId = $(this).attr('data-item-id');
        var draftType = $(this).attr('data-type');
        if(zxEditor)
        {
            var textarea = zxEditor.getContent();
        }else{
            var textarea = $('#edit-content').val();
        }
        if(textarea != '' && draftType != '')
        {
            $.post(G_BASE_URL + '/account/ajax/save_draft/item_id-'+itemId+'__type-' + draftType, 'message=' + textarea, function (result) {
                layer.msg(result.err);
            }, 'json');
        }
    });

    // 话题编辑box
    AWS.Init.init_topic_edit_box('.aw-edit-topic');

});

function encrypt_pass(pass) {
    var key = CryptoJS.enc.Utf8.parse(G_PRIVATEKEY);// 秘钥
    var iv= CryptoJS.enc.Utf8.parse(G_IV);//向量iv
    var encrypted = CryptoJS.AES.encrypt(pass, key, { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7});
    return encrypted.toString();
}

var com={elfvision:{}};com.elfvision.kit={};com.elfvision.kit.LocationSelect={};com.elfvision.ajax={};com.elfvision.DEBUG=!1;(function(){var f,k,m,g,n,w,x,q,r,s,t,c,y,u,l,z,h;if(com.elfvision.DEBUG){var B=(new Date).getTime();c=function(){for(var a=0,b=arguments.length,d=["[DEBUG at ",(new Date).getTime()-B," ] : "];a<b;a++)d.push(arguments[a]);void 0!==window.console&&"function"==typeof window.console.log&&console.log.apply(console,d)}}else c=function(){};u=function(a,b){return function(){b.apply(a,arguments)}};y=function(a,b,d,e){c("attaching event",b,"on the object",a);var j=function(b){d.apply(e||a,[b])};void 0!==window.jQuery?jQuery(a).bind(b,j):document.addEventListener?a.addEventListener(b,j,!1):document.attachEvent&&(j=function(b){b||(b=window.event);d.apply(e||a,[{_event:b,type:b.type,target:b.srcElement,currentTarget:a,relatedTarget:b.fromElement?b.fromElement:b.toElement,eventPhase:b.srcElement==a?2:3,clientX:b.clientX,clientY:b.clientY,screenX:b.screenX,screenY:b.screenY,altKey:b.altKey,ctrlKey:b.ctrlKey,shiftKey:b.shiftKey,charCode:b.keyCode,stopPropagation:function(){this._event.cancelBubble=!0},preventDefault:function(){this._event.returnValue=!1}}])},a.attachEvent("on"+b,j))};var p,A=[function(){return new XMLHttpRequest},function(){return new ActiveXObject("Msxml2.XMLHTTP")},function(){return new ActiveXObject("Msxml3.XMLHTTP")},function(){return new ActiveXObject("Microsoft.XMLHTTP")}];q={create:function(){if(p)return p();for(var a=0;a<A.length;a++)try{var b=A[a],d=b();if(d)return p=b,d}catch(c){}p=function(){throw Error("XMLHttpRequest not supported");};p()}};r=function(a){if(!a.url)throw Error("getJson : Must provide url for the request!");var b=q.create();b.onreadystatechange=function(){c("Request Object ",b);if(4==b.readyState&&(200==b.status||0===b.status))if(c("JSON is successfully retrived according to ",a),a.callback){c("about to parse json");var jsons = b.responseText;var d;if (typeof(JSON) == 'undefined'){d = eval("("+jsons+")");}else{d= JSON.parse(jsons);}c("parsed ",d);a.callback.call(this,d)}};b.open("GET",a.url,!0);b.setRequestHeader("Cache-Control","max-age=0,no-cache,no-store,post-check=0,pre-check=0");b.setRequestHeader("Expires","Mon, 26 Jul 1997 05:00:00 GMT");b.send(null)};s=function(a,b){var d=document.createElement("script"),c,j;b&&(c=/callback=(\w+)&*/,j=c.exec(a)[1],window[j]=function(a){b(a);window[j]=null});d.src=a;document.getElementsByTagName("head")[0].appendChild(d)};t=function(a,b){var d=document.createElement("script");d.src=a;c("getting script",a);b&&(d.onload=b,d.onreadystatechange=function(){(4==d.readyState||"loaded"==d.readyState||"complete"==d.readyState)&&b()});document.getElementsByTagName("head")[0].appendChild(d)};h=function(a,b){if(Array.prototype.forEach)return Array.prototype.forEach.call(a,b);var d=a.length>>>0;if("function"!=typeof b)throw new TypeError;for(var c=0;c<d;c++)c in a&&b.call(b,a[c],c,this)};z=function(a){if(a&&"object"===typeof a&&a.constructor===Array)return!0};l=function(){this.observers=[];this.guid=0};l.prototype.subscribe=function(a){var b=this.guid++;this.observers[b]=a;return b};l.prototype.unSubscribe=function(a){delete this.observers[a]};l.prototype.notify=function(a){for(var b in this.observers){var d=this.observers[b];d instanceof Function?d.call(this,a):d.update.call(this,a)}};f=function(a){this.onRowsInserted=new l;this.onRowsRemoved=new l;this.onRowsUpdated=new l;this.onSelectedIndexChanged=new l;this.items=[];this.selectedIndex=0;this.level=a.level||0;this.label=a.label||"Select..."};f.prototype.read=function(a){return a?(c("reading items["+a+"]:",this.items[a]),this.items[a]):this.items};f.prototype.insert=function(a){z(a)?(a=[a],this.items=this.items.concat(a)):this.items.push(a);this.onRowsInserted.notify({source:this,items:a})};f.prototype.remove=function(a){var b;a?h(this.items,function(d,c){d.id===a&&(b=d,this.items.splice(c,1))}):this.items=[];c("notifying removing");this.onRowsRemoved.notify({source:this,items:[b]})};f.prototype.update=function(a){a=a||[];c("updating list model with ",a);this.items=[{id:0,text:this.label}].concat(a);c("notifying updating");this.onRowsUpdated.notify({source:this,items:a})};f.prototype.getSelectedIndex=function(){return this.selectedIndex};f.prototype.setSelectedIndex=function(a){var b=this.getSelectedIndex();b!==a&&(this.selectedIndex=a,c("notifying index changed",a),this.onSelectedIndexChanged.notify({source:this,previous:b,present:a,previousItem:this.read(b),presentItem:this.read(a),level:this.level}))};k=function(a){this.model=a.model;this.controller=a.controller;this.element=a.element;a=u(this,this.rebuildList);var b=u(this.controller.parent,this.controller.parent.update);this.model.onRowsInserted.subscribe(a);this.model.onRowsRemoved.subscribe(a);this.model.onRowsUpdated.subscribe(a);this.model.onSelectedIndexChanged.subscribe(b);c("this list item",this);y(this.element,"change",this.controller.updateSelectedIndex,this.controller)};k.prototype.show=function(){this.element.style.display="inline-block"};k.prototype.hide=function(){this.element.style.display="none"};k.prototype.rebuildList=function(a){if(a&&a.present&&0===a.present)this.elements.list.selectedIndex=0;else{c("Rebuilding list ",this);var b=this.element;a=this.model.read();var d;b.innerHTML="";c(a.length);h(a,function(a){d=new Option;d.setAttribute("value",a.id?a.text:"");d.appendChild(document.createTextNode(a.text));b.appendChild(d)});this.model.setSelectedIndex(0)}};m=function(a){this.parent=a.parent;this.model=new f({level:a.level,label:a.label});this.view=new k({model:this.model,controller:this,element:a.element})};m.prototype.refresh=function(a){c("refresh data with ",a);this.model.update(a)};m.prototype.updateSelectedIndex=function(a){this.model.setSelectedIndex(a.target.selectedIndex)};m.prototype.selectByText=function(a){var b=this;h(this.model.read(),function(d,e){d.text.match("^"+a)==a&&(c("auto detected ",d,e),b.model.setSelectedIndex(e),b.view.element.selectedIndex=e)})};m.prototype.selectByID=function(a){var b=this;h(this.model.read(),function(d,e){d.id.toString().match("^"+a)==a&&(c("auto detected ",d,e),b.model.setSelectedIndex(e),b.view.element.selectedIndex=e)})};m.prototype.getValue=function(){return this.model.read(this.model.getSelectedIndex()).text};g=function(a){this.labels=a.labels;this.currentGeo={};this.lists=[];this.elements=a.elements;this.parent=a.parent};g.prototype.init=function(){c("init select group");for(var a=0,b=this.labels.length;a<b;a++)this.lists.push(new m({label:this.labels[a],element:this.elements[a],level:a,parent:this}));c("lists built ",this);c(this.parent.listHelper.find(-1));this.lists[0].refresh(this.parent.listHelper.find(-1));this.lists[0].view.show()};g.prototype.update=function(a){if(a.level!=this.lists.length-1)if(c("Updating SelectGroup contents",this),0===a.present){a=a.level+1;for(var b=this.lists.length;a<b;a++)this.lists[a].refresh(),this.lists[a].view.hide()}else{switch(a.level){case 0:this.currentGeo.province=a.presentItem.text;break;case 1:this.currentGeo.city=a.presentItem.text;break;case 2:this.currentGeo.district=a.presentItem.text}this.lists[a.level+1].refresh(this.parent.listHelper.find(a.level,a.presentItem.id));this.lists[a.level+1].view.show()}};g.prototype.setValues=function(a){c("setting group values",a);var b=this;h(a,function(a,c){a&&b.lists[c].selectByText(a)})};g.prototype.setValuesID=function(a){c("setting group values",a);var b=this;h(a,function(a,c){a&&b.lists[c].selectByID(a)})};g.prototype.setValuesCode=function(a){c("setting group values",a);var b=this;h(a,function(a,c){a&&b.lists[c].selectByText(a)})};g.prototype.getValues=function(){var a=[];h(this.lists,function(b){a.push(b.getValue())});return a};n=function(a){this.detectGeoLocation=void 0===a.detectGeoLocation?!0:a.detectGeoLocation;this.detector=a.detector||w;this.listHelper=a.listHelper||x.getInstance({dataUrl:a.dataUrl});this.selectGroup=new g({parent:this,labels:a.labels,elements:a.elements});var b=this;this.listHelper.fetch(function(){c("exec fetech callback");b.selectGroup.init();b.detectGeoLocation&&b.detector()})};n.prototype.report=function(){return this.selectGroup.getValues()};n.prototype.select=function(a){this.selectGroup.setValues(a)};n.prototype.selectID=function(a){this.selectGroup.setValuesID(a)};w=function(){c("Detect!!!!");var a=this,b;t("http://j.maxmind.com/app/geoip.js",function(){c("Maxmind API Loaded!");b="http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20json%20where%0A%20%20url%3D%22http%3A%2F%2Fmaps.google.com%2Fmaps%2Fapi%2Fgeocode%2Fjson%3Flatlng%3D"+geoip_latitude()+"%2C"+geoip_longitude()+"%26sensor%3Dfalse%26language%3Dzh-CN%22&format=json&diagnostics=true&callback=locationselectcb";s(b,function(b){c("Geocoder Request Completed through YQL ",b);if("OK"===b.query.results.json.status){b=b.query.results.json.results[0].address_components;var e,j;c("Geocoder statuts ok",b);h(b,function(a){var b=a.types[0];"locality"===b?e=a.long_name:"administrative_area_level_1"===b&&(j=a.long_name)});a.select([j,e])}})})};var v;x={getInstance:function(a){if(!v){var b=a.dataUrl||"js/areas.js",d,e={};d={get:function(a){return e[a]},set:function(a,b){e[a]=b}};v={fetch:function(a){c("feteching areas data");r({url:b,callback:function(b){c("area data : ",b);d.set("province",b.province);d.set("city",b.city);d.set("district",b.district);a()}})},find:function(a,b){var e=[];c("querying by record id : ",b,"by list in level : ",a);if(d.get(b))c("lucky! we have it cached"),e=d.get(b);else if(c("finding it in areas data"),-1===a)c("this is a query for province data"),e=d.get("province");else{var f=b.toString().substring(0,2*(a+1)),k=RegExp("^"+f+"\\d*"),g=0===a?d.get("city"):d.get("district");h(g,function(a,b){k.test(a.id)&&e.push(g[b])})}c("Return results : ",e);return e}}}return v}};com.elfvision.kit.LocationSelect=n;com.elfvision.ajax.XhrFactory=q;com.elfvision.ajax.getJson=r;com.elfvision.ajax.jsonp=s;com.elfvision.ajax.getScript=t})();void 0!==window.jQuery&&($.LocationSelect={build:function(f){var k;f.elements=this.get();k=new com.elfvision.kit.LocationSelect(f);$.LocationSelect.all[f.name]=k;return this}},$.LocationSelect.all={},$.fn.LocationSelect=$.LocationSelect.build);
