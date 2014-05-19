<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class MyCourseController extends BaseController
{

    public function indexAction (Request $request)
    {
        if ($this->getCurrentUser()->isTeacher()) {
            return $this->redirect($this->generateUrl('my_teaching_courses')); 
        } else {
            return $this->redirect($this->generateUrl('my_courses_learning'));
        }
    }

    public function learningAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseService()->findUserLeaningCourseCount($currentUser['id']),
            12
        );

        $courses = $this->getCourseService()->findUserLeaningCourses(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        // array('id'=>array('dateTS'=>'', 'timeTS'=>'', ),....)
        $courseBookings = array();
        // 查找所有正在学习课程的选课历史，并返回。
        if(!empty($courses))
        {
            foreach($courses as $course)
            {
                // 先取出最近30次已排课的预约的id
                $bookings = $this->getStudentBookLessonsService()->searchArrangedSBLs($course['id'], $currentUser['id']);

                $courseBookings[$course['id']] = $bookings;
            }
        }

        return $this->render('TopxiaWebBundle:MyCourse:learning.html.twig', array(
            'courses'=>$courses,
            'paginator' => $paginator,
            'courseBookings' => $courseBookings
        ));
    }

    public function enterClassroomAction(Request $request, $bid, $cid, $cts)
    {
        $url = $this->getStudentBookLessonsService()->formClassroomUrl($bid, $cid, $cts);

        return $this->render('TopxiaWebBundle:MyCourse:classroom.html.twig', array(
            'url'=>$url
        ));
    }

    public function bookLessonsAction(Request $request)
    {
    	// 获取上课日期 上课时间时间戳数组的索引
    	$nthDay = 	$this->getIndexesofTS();
    	// 获取所有上课日期的时间戳
    	$courseDate = $this->getCourseDateTS();

    	$currentUser = $this->getCurrentUser();
    	$courses = $this->getCourseService()->findUserLeaningCourses(
    			$currentUser['id'],
    			0,
    			$this->getCourseService()->findUserLeaningCourseCount($currentUser['id'])
    	);
    
    	return $this->render('TopxiaWebBundle:MyCourse:book-lessons.html.twig', array(
    			'courses'=>$courses,
    			'nthDay'=>$nthDay,
    			'courseDate'=>$courseDate
    	));
    }

    private function getTSandStatus($userId, $dateTS, $courseId )
    {
        // return value array("timestamp" => "status", ...), status: "arranged", "booked", "free", "NA"
        $tsList = $this->getStudentBookLessonsService()->getTSandStatus($userId, $dateTS, $courseId);	

        $html = $this->renderView('TopxiaWebBundle:MyCourse:course-time-li.html.twig', array(
             'para'=>$tsList 
        ));

        return $html;
    }

    //每次点击日期，执行本函数。
    //若已预约，返回我的预约情况；若未预约，返回时间列表供选择。
    public function bookedTimesAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {        	
        	$currentUser = $this->getCurrentUser();
        	
        	$data = $request->request->all();
        	$courseId = $data['courseId'];
        	$courseDate = $data['courseDate'];

            //剩余课时数
            $remainingNum = $this->getStudentBookLessonsService()->findRemainingNum($currentUser['id'], $courseId); 

        	// return value array("timestamp" => "status", ...), status: "arranged", "booked", "free", "NA"
        	$html = $this->getTSandStatus($currentUser['id'], $courseDate, $courseId);	

            return $this->createJsonResponse(array('booked' => '', 'html' => $html, 'remainingNum' => $remainingNum));
        }
    }
    
    // 提交预约时间
    public function confirmBookingAction(Request $request)
    {
    	if ('POST' == $request->getMethod()) {        	
        	$currentUser = $this->getCurrentUser();
        	
        	$data = $request->request->all();
        	$courseId = $data['courseId'];
        	$courseDate = $data['courseDate'];
        	$courseTS = json_decode($data['courseTS']);
        	
        	$result = $this->getStudentBookLessonsService()->addBookings($currentUser['id'], $courseId, $courseDate, $courseTS);

            //剩余课时数
            $remainingNum = $this->getStudentBookLessonsService()->findRemainingNum($currentUser['id'], $courseId); 

            $html = $this->getTSandStatus($currentUser['id'], $courseDate, $courseId);
			
        	return $this->createJsonResponse(array('status' => $result['status'], 'errorMsg' => $result['error'], 'html' => $html, 'remainingNum'=>$remainingNum));
        }
    }
    
    public function cancelBookingAction(Request $request)
    {
   		if ('POST' == $request->getMethod()) {        	
        	$currentUser = $this->getCurrentUser();
        	
        	$data = $request->request->all();
        	$courseId = $data['courseId'];
        	$courseDate = $data['courseDate'];
        	
//         	$courseId = 12;
//         	$courseDate = 1399737600;
        	
        	$this->getStudentBookLessonsService()->removeBookingsByCourseAndDate($currentUser['id'], $courseId, $courseDate);
        	
        	$courseTimes = $this->getCourseTimesTS($courseDate);
        	foreach($courseTimes as $key => $courseTime)
        	{
        		//根据TS判断预约数
        		$studentCount = $this->getStudentBookLessonsService()->getBookingCounts($courseTime["TS"]);
        		$teacherCount = $this->getTeacherAvailableTimesService()->getTeacherCounts($courseTime["TS"]);
        		 
        		if($studentCount < $teacherCount)
        		{
        			$courseTimes[$key]["AV"] = "1";
        		}
        	}
        	
        	$para = $courseTimes;
        	 
        	$html = $this->renderView('TopxiaWebBundle:MyCourse:course-time-li.html.twig', array(
        			'booked' => false, 'para'=>$para
        	));
        	
        	return $this->createJsonResponse(array('status' => 'success', 'html' => $html));
        }
    } 
       
    public function remainingAction(Request $request)
    {
    	$currentUser = $this->getCurrentUser();
    	$paginator = new Paginator(
    			$this->get('request'),
    			$this->getCourseService()->findUserLeaningCourseCount($currentUser['id']),
    			12
    	);
    
    	$courses = $this->getCourseService()->findUserLeaningCourses(
    			$currentUser['id'],
    			$paginator->getOffsetCount(),
    			$paginator->getPerPageCount()
    	);
    
    	return $this->render('TopxiaWebBundle:MyCourse:learning.html.twig', array(
    			'courses'=>$courses,
    			'paginator' => $paginator
    	));
    }
    
    public function learnedAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseService()->findUserLeanedCourseCount($currentUser['id']),
            12
        );

        $courses = $this->getCourseService()->findUserLeanedCourses(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = array();
        foreach ($courses as $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('TopxiaWebBundle:MyCourse:learned.html.twig', array(
            'courses'=>$courses,
            'users'=>$users,
            'paginator' => $paginator
        ));
    }

    public function favoritedAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseService()->findUserFavoritedCourseCount($currentUser['id']),
            12
        );
        
        $courses = $this->getCourseService()->findUserFavoritedCourses(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = array();
        foreach ($courses as $favoriteCourse) {
            $userIds = array_merge($userIds, $favoriteCourse['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('TopxiaWebBundle:MyCourse:favorited.html.twig', array(
            'courses'=>$courses,
            'users'=>$users,
            'paginator' => $paginator
        ));
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }
    
    protected function getStudentBookLessonsService()
    {
        return $this->getServiceKernel()->createService('Course.StudentBookLessonsService');
    }

    protected function getTeacherAvailableTimesService()
    {
    	return $this->getServiceKernel()->createService('Course.TeacherAvailableTimesService');
    }
    // 获取课程日期和课程时间 时间戳数组的索引
    protected  function getIndexesofTS()
    {
    	return	$nthDay = 	array(	1 => mktime(0, 0, 0, date("m", strtotime("+1 day")), date("d", strtotime("+1 day")), date("Y", strtotime("+1 day"))),
			    			2 => mktime(0, 0, 0, date("m", strtotime("+2 day")), date("d", strtotime("+2 day")), date("Y", strtotime("+2 day"))),
			    			3 => mktime(0, 0, 0, date("m", strtotime("+3 day")), date("d", strtotime("+3 day")), date("Y", strtotime("+3 day"))),
			    			4 => mktime(0, 0, 0, date("m", strtotime("+4 day")), date("d", strtotime("+4 day")), date("Y", strtotime("+4 day"))),
			    			5 => mktime(0, 0, 0, date("m", strtotime("+5 day")), date("d", strtotime("+5 day")), date("Y", strtotime("+5 day"))),
			    			6 => mktime(0, 0, 0, date("m", strtotime("+6 day")), date("d", strtotime("+6 day")), date("Y", strtotime("+6 day"))),
			    			7 => mktime(0, 0, 0, date("m", strtotime("+7 day")), date("d", strtotime("+7 day")), date("Y", strtotime("+7 day")))
			    	);
    }
    
    // 获取上课日期的时间戳和显示内容  "123456789"=>"06-01星期一"
    private function getCourseDateTS()
    {
    	// the timestamp of nth day
    	$nthDay = 	$this->getIndexesofTS();
    	
    	// array to get weekdays
    	$weekDayArray = array(	"Mon" => "星期一",
    			"Tue" => "星期二",
    			"Wed" => "星期三",
    			"Thu" => "星期四",
    			"Fri" => "星期五",
    			"Sat" => "星期六",
    			"Sun" => "星期日");

    	return $courseDate = array( 	$nthDay[1] => date("m-d", $nthDay[1]) .  $weekDayArray[date("D", $nthDay[1])],
    			$nthDay[2] => date("m-d", $nthDay[2]) .  $weekDayArray[date("D", $nthDay[2])],
    			$nthDay[3] => date("m-d", $nthDay[3]) .  $weekDayArray[date("D", $nthDay[3])],
    			$nthDay[4] => date("m-d", $nthDay[4]) .  $weekDayArray[date("D", $nthDay[4])],
    			$nthDay[5] => date("m-d", $nthDay[5]) .  $weekDayArray[date("D", $nthDay[5])],
    			$nthDay[6] => date("m-d", $nthDay[6]) .  $weekDayArray[date("D", $nthDay[6])],
    			$nthDay[7] => date("m-d", $nthDay[7]) .  $weekDayArray[date("D", $nthDay[7])]
    	);
    }
    
    //获取上课时间的时间戳,根据上课日期的时间戳
    private function getCourseTimesTS($courseDateTs)
    {
    	$dayKey = $courseDateTs;
    	
    	// array("TS"=>"AV"), AV = 0 "已约满"， AV = 1 "可预约"
    	$timeArray = array(
    					array( "TS" => mktime(7, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(7, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(8, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(8, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(19, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(19, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(20, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(20, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(21, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(21, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(22, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0"),
    					array( "TS" => mktime(22, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "AV" => "0")
    			);

    	return $timeArray;
    } 

}
