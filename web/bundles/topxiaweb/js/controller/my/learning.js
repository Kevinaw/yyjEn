define(function(require, exports, module) {

	var Notify = require('common/bootstrap-notify');

    exports.run = function() {
    	
    	var Notify = require('common/bootstrap-notify');

    	$(document).ready(function(){
            $(".classroom-occupied").click(function(){
                alert("其他同学正在使用教室，请提前10分钟进入教室！如不能进入，请刷新本页面！");
            });
    	});
    };
});


