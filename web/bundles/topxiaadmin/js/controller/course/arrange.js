define(function(require, exports, module) {

	var Notify = require('common/bootstrap-notify');

    exports.run = function() {
    	
    	var Notify = require('common/bootstrap-notify');
    	
    	$(document).ready(function(){

        	$(".course-date").click(function(){
        		$(".course-date").removeClass("active");
        		$(this).addClass("active");
                $("#result-div").html("");
        		
        		//获取当前日期的时间戳，并以它作为参数向服务器发送数据，查询是否已经预约
        		var courseDate = $(this).data("value");
        		
        		$.post($(this).data('url'), { courseDate: courseDate }, function(response) {
        			$("#course-time-ul").html(response.html);
        		}, 'json');

        	});    		
        	
        	$("#course-time-ul").on("click", "li", function(){
        		$(".course-time").removeClass("active");
        		$(this).addClass("active");

        		//获取当前日期的时间戳，并以它作为参数向服务器发送数据，get the bookings and arrangement
        		var courseDate = $("[class='course-date active']").data("value");
        		var courseTime = $(this).data("value");
        		
        		$.post($(this).data('url'), { courseDate: courseDate, courseTime: courseTime }, function(response) {
                    // change booking count and available count
                    //$("#total-num").html(response.bookingCount);
                    //$("#total-num").html('1');
                    //$("#available-num").html(response.availCount);
                    //$("#available-num").html('2');
                    // change student table 
                    $("#result-div").html(response.html);

                    // change teacher table
                    //$("#teacher-tbl tbody").html(response.teaHtml);
        		}, 'json');
        	});
        	
        	$("#result-div").on('click', '.cancel-button', function(){
        		if (!confirm('真的要取消课程安排吗？')) {
        			return false;
        		}
                // schedule id
        		var courseTime = $("[class='course-time active']").data("value");

        		$.post($(this).data('url'), {id: $(this).data('id'), courseTime:courseTime }, function(response) {
        			Notify.success('课程安排已取消成功！');
                    $("#result-div").html(response.html);
        		});
        	});    		

        	$("#result-div").on('click', '.submit-button', function(){
                // teacherAvaliableId
                var teacherAvailableId = $(this).data("id");
                // bookingId
                var studentbookId = $("#booking-slt-" + teacherAvailableId + " option:selected").val();
                // classroomId 
                var classroomId = $("#classroom-slt-" + teacherAvailableId + " option:selected").val();
                // lessonTS
                var lessonTS = $("[class='course-time active']").data("value");

        		$.post($(this).data('url'), {teacheravaliableId: teacherAvailableId, studentbookId: studentbookId, classroomId: classroomId, lessonTS: lessonTS}, function(response) {
                    if(response.status == "fail"){
        			    alert(response.errorMsg);
                    } else {
        			    Notify.success('课程安排已成功！');
                        $("#result-div").html(response.html);
                    }
        		});
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


