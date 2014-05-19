define(function(require, exports, module) {

	var Notify = require('common/bootstrap-notify');

    exports.run = function() {
    	
    	var Notify = require('common/bootstrap-notify');

    	$(document).ready(function(){
            $(".classroom-occupied").click(function(){
                alert("The classroom is not available now, Please enter the classroom 10 minutes earlier! Refresh the current page if needed!");
            });
    	});
    };
});


