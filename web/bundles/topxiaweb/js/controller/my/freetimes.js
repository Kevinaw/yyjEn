define(function(require, exports, module) {

	var Notify = require('common/bootstrap-notify');

    exports.run = function() {
    	
    	var Notify = require('common/bootstrap-notify');
    	
    	$(document).ready(function(){
    		//默认选中第一个课程
//    		if($("#course-type-ul").length > 0){
//    			$("#course-type-ul li:first-child").addClass("active");
//    		}
//    		
//        	$(".course-type").click(function(){
//	    		$(".course-type").removeClass("active");
//	    		$(this).addClass("active");
//	    		
//	    		//重新选择日期
//	    		$(".course-date").removeClass("active");
//	    		$("#course-time-ul").html("");
//				$("#button-confirm").addClass("hide");
//				$("#button-cancel").addClass("hide");
//	    	});

        	$(".course-date").click(function(){
        		$(".course-date").removeClass("active");
        		$(this).addClass("active");
        		
        		//获取当前日期的时间戳，并以它作为参数向服务器发送数据，查询是否已经预约
        		var courseDate = $(this).data("value");
        		
        		$.post($(this).data('url'), { courseDate: courseDate }, function(response) {
        			$("#course-time-ul").html(response.html);
        			$("#button-confirm").removeClass("disabled");
//        			if(response.booked == true){
//        				$("#button-confirm").addClass("hide");
//        				$("#button-cancel").removeClass("hide");
//        			} else{
//        				$("#button-confirm").removeClass("hide");
//        				$("#button-cancel").addClass("hide");        				
//        			}
        		}, 'json');

        	});    		
        	
        	//可预约多个时间，格式：“123456789|123456989|123465789”
        	//动态生成的li，需要把事件绑定到parent上，用on方法
        	$("#course-time-ul").on("click", "li", function(){
        		if(false == $(this).hasClass("disabled"))
        			$(this).toggleClass("active");
        	});
        	
        	//预约时间提交按钮
        	$("#button-confirm").click(function(){
        		
	        	if (!confirm('are you sure your want to update your available times？')) {
	    			return false;
	    		}
        		
        		//课程及时间
        		var courseDate = $("[class='course-date active']").data("value");
        		var courseTS = new Array();
        		for(var i = 1; i < 13; i++){
        			if($("#course-time-ul li:nth-child(" + i + ")").hasClass("active")){
        				var val = $("#course-time-ul li:nth-child(" + i + ")").data("value");
        				courseTS.push(val);
        			}
        		}
//        		if(courseTS.length == 0){
//        			alert("no time selected！");
//        			return false;
//        		}

        		courseTS = JSON.stringify(courseTS);
        		$.post($(this).data('url'), { courseDate: courseDate, courseTS: courseTS }, function(response) {
        			if(response.status == 'fail'){
        				alert(response.errorMsg);
        			} else{
//        				alert("预约成功！");
        				Notify.success('update succeed!');
        				$("#course-time-ul").html(response.html);
        			}
        		}, 'json');
        	});
//        	
//          	//取消预约按钮
//        	$("#button-cancel").click(function(){
//        		
//	        	if (!confirm('真的要取消预约吗？')) {
//	    			return false;
//	    		}
//	        	
//        		//课程及时间
//        		var courseId = $("[class='course-type active']").data("courseid");
//        		var courseDate = $("[class='course-date active']").data("value");
//
//        		$.post($(this).data('url'), { courseId: courseId, courseDate: courseDate}, function(response) {
//        			if(response.status == 'success'){
//        				$("#course-time-ul").html(response.html);
//        				
////        				alert("取消预约成功！");
//        				Notify.success('取消预约成功!');
//        				$("#button-confirm").removeClass("hide");
//        				$("#button-cancel").addClass("hide"); 
//        			} 
//        		}, 'json');
//        	});
//
//        	$("#orders-table").on('click', '.cancel-refund', function(){
//        		if (!confirm('真的要取消退款吗？')) {
//        			return false;
//        		}
//
//        		$.post($(this).data('url'), function() {
//        			Notify.success('退款申请已取消成功！');
//        			window.location.reload();
//        		});
//        	});    		
    	});
    };
});


