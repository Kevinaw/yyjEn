<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class MyTeachingController extends BaseController
{
    
    public function coursesAction(Request $request)
    {
        $user = $this->getCurrentUser();
       // $paginator = new Paginator(
       //     $this->get('request'),
       //     $this->getCourseService()->findUserTeachCourseCount($user['id'], false),
       //     12
       // );
       // 
       // $courses = $this->getCourseService()->findUserTeachCourses(
       //     $user['id'],
       //     $paginator->getOffsetCount(),
       //     $paginator->getPerPageCount(),
       //     false
       // );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getTeacherAvailableTimesService()->findArrangedTATCount($user['id']),
            30
        );
        // 查找所有已排课课程的历史，并返回。
        // 先取出最近30次已排课课程
        $TATs = $this->getTeacherAvailableTimesService()->findArrangedTATs(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
       );
        
        $students = array();
        $courses = array();
        if(null !== $TATs)
        {
            $students = $this->getUserService()->findUsersByIds(ArrayToolkit::column($TATs, 'studentId'));
            $students = ArrayToolkit::index($students, 'id');
            $courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($TATs, 'courseId'));
            $courses = ArrayToolkit::index($courses, 'id');
        }

        return $this->render('TopxiaWebBundle:MyTeaching:teaching.html.twig', array(
            'TATs'=>$TATs,
            'paginator' => $paginator,
            'students' => $students,
            'courses' => $courses
        ));
    }

    public function freetimesAction(Request $request)
    {
    	// 获取上课日期 上课时间时间戳数组的索引
    	$nthDay = 	$this->getIndexesofTS();
    	// 获取所有上课日期的时间戳
    	$courseDate = $this->getCourseDateTS();
    	    	
    	return $this->render('TopxiaWebBundle:MyTeaching:freetimes.html.twig', array(
    		'nthDay'=>$nthDay,
    		'courseDate'=>$courseDate
    	));
    }

    public function freetimesListAction(Request $request)
    {        
    	if ('POST' == $request->getMethod()) {        	
        	$currentUser = $this->getCurrentUser();
        	
        	$data = $request->request->all();
        	$courseDate = $data['courseDate'];
			
        	// return value array("timestamp" => "status", ...), status: "have course", "registered", "availabe"
        	$tsVal = $this->getTeacherAvailableTimesService()->getFreetimesList($currentUser['id'], $courseDate);	
        	
            $html = $this->renderView('TopxiaWebBundle:MyTeaching:course-time-li.html.twig', array(
                 'para'=>$tsVal
            ));
            
            return $this->createJsonResponse(array('html' => $html));
        }
    }
    
    public function freetimesConfirmAction(Request $request)
    {
    	
    	if ('POST' == $request->getMethod()) {
    		$currentUser = $this->getCurrentUser();
    		 
    		$data = $request->request->all();
    		$courseDate = $data['courseDate'];
    		$courseTS = json_decode($data['courseTS']);
    		 
    		$result = $this->getTeacherAvailableTimesService()->updateTATs($currentUser['id'], $courseDate, $courseTS);
   		
    		// 添加成功
    		$html = "";
    		if($result['status'] == 'success'){
    			$tsVal = $this->getTeacherAvailableTimesService()->getFreetimesList($currentUser['id'], $courseDate);    		 
	    		$html = $this->renderView('TopxiaWebBundle:MyTeaching:course-time-li.html.twig', array(
	    				'para'=>$tsVal
	    		));
    		}
    		 
    		return $this->createJsonResponse(array('status' => $result['status'], 'errorMsg' => $result['error'], 'html' => $html));
    	}
    }
    
    public function freetimesCancelAction(Request $request)
    {
		return;
    }
	public function threadsAction(Request $request, $type)
	{

		$user = $this->getCurrentUser();
		$myTeachingCourseCount = $this->getCourseService()->findUserTeachCourseCount($user['id'], true);
		$myTeachingCourses = $this->getCourseService()->findUserTeachCourses($user['id'], 0, $myTeachingCourseCount, true);

		$conditions = array(
			'courseIds' => ArrayToolkit::column($myTeachingCourses, 'id'),
			'type' => $type);

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->searchThreadCountInCourseIds($conditions),
            20
        );

        $threads = $this->getThreadService()->searchThreadInCourseIds(
            $conditions,
            'createdNotStick',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($threads, 'latestPostUserId'));
        $courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($threads, 'courseId'));
        $lessons = $this->getCourseService()->findLessonsByIds(ArrayToolkit::column($threads, 'lessonId'));

    	return $this->render('TopxiaWebBundle:MyTeaching:threads.html.twig', array(
    		'paginator' => $paginator,
            'threads' => $threads,
            'users'=> $users,
            'courses' => $courses,
            'lessons' => $lessons,
            'type'=>$type
    	));
	}

	protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Course.ThreadService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
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
    
    // 获取上课日期的时间戳和显示内容  "123456789"=>"06-01Mon"
    private function getCourseDateTS()
    {
    	// the timestamp of nth day
    	$nthDay = 	$this->getIndexesofTS();
    
    	return $courseDate = array( 	$nthDay[1] => date("m-d D", $nthDay[1]),
    			$nthDay[2] => date("m-d D", $nthDay[2]),
    			$nthDay[3] => date("m-d D", $nthDay[3]),
    			$nthDay[4] => date("m-d D", $nthDay[4]),
    			$nthDay[5] => date("m-d D", $nthDay[5]),
    			$nthDay[6] => date("m-d D", $nthDay[6]),
    			$nthDay[7] => date("m-d D", $nthDay[7])
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
