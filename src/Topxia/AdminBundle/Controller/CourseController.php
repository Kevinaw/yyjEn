<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class CourseController extends BaseController
{

    public function indexAction (Request $request)
    {
    	// loop every course to update the published and completed course to status 'completed'
    	$courses = $this->getCourseService()->searchCourses(array(
    			'status'=>'published',), null, 0, 10000);
    	foreach ($courses as $course)
    	{
    		if (strtotime($course['startingTime']) + $course['length'] * 60 < time()){
    			$this->getCourseService()->updateCourse($course['id'], array(
	    			'status'=>'completed',
	    		));
    		}    		
    	}

    	
        $conditions = $request->query->all();

        $count = $this->getCourseService()->searchCourseCount($conditions);

        $paginator = new Paginator($this->get('request'), $count, 20);

        $courses = $this->getCourseService()->searchCourses($conditions, null, $paginator->getOffsetCount(),  $paginator->getPerPageCount());

        $categories = $this->getCategoryService()->findCategoriesByIds(ArrayToolkit::column($courses, 'categoryId'));
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($courses, 'userId'));

        return $this->render('TopxiaAdminBundle:Course:index.html.twig', array(
            'conditions' => $conditions,
            'courses' => $courses ,
            'users' => $users,
            'categories' => $categories,
            'paginator' => $paginator
        ));
    }
    
    public function arrangeAction (Request $request)
    {
    	// 获取上课日期 上课时间时间戳数组的索引
    	$nthDay = $this->getIndexesofTS();
    	// 获取所有上课日期的时间戳
    	$courseDate = $this->getCourseDateTS();
    	// get the timeStamps
        $timeStamps = $this->getCourseTimesTS($nthDay[1]);    	

    	return $this->render('TopxiaAdminBundle:Course:arrange.html.twig', array(
    			'nthDay' => $nthDay,
    			'courseDate' => $courseDate,
    			'timeStamps' => $timeStamps,
    	));
    }
    
    //每次点击日期，执行本函数。
    //返回时间列表供选择。
    public function getTimesAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {        	
        	$currentUser = $this->getCurrentUser();
        	
        	$data = $request->request->all();
        	$courseDate = $data['courseDate'];

            $timeStamps = $this->getCourseTimesTS($courseDate);    	
        	
            $html = $this->renderView('TopxiaAdminBundle:Course:course-time-li.html.twig', array(
                 'timeStamps'=>$timeStamps
            ));
            
            return $this->createJsonResponse(array('html' => $html));
        }
    }
    
    //返回course bookings and course schedules , result-div
    private function getInfo($courseTime)
    {
        $tats = $this->getTeacherAvailableTimesService()->searchTATs(array(
                        "availableTimeTS"=>$courseTime), null, 0, 10000);
        $teachers = $this->getUserService()->findUsersByIds(ArrayToolkit::column($tats, 'teacherId'));
        $tats = ArrayToolkit::index($tats, 'id');
        
        // students
        $sbls = $this->getStudentBookLessonsService()->searchSBLs(array(
                        "timeTS"=>$courseTime), null, 0, 10000);
        $students = $this->getUserService()->findUsersByIds(ArrayToolkit::column($sbls, 'studentId'));
        $courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($sbls, 'courseId'));
        $sbls = ArrayToolkit::index($sbls, 'id');

        // schedules
        $schedules = $this->getTeacherAvailableTimesService()->searchSchedules(array(
                        "lessonTS"=>$courseTime), null, 0, 10000);
        $classrooms = $this->getClassroomService()->findClassroomsByIds(ArrayToolkit::column($schedules, 'classroomId'));
        
        $schedulesIndexedByBookId = ArrayToolkit::index($schedules, 'studentbookId');
        $schedulesIndexedByAvaiId = ArrayToolkit::index($schedules, 'teacheravaliableId');

        // find not scheduled bookings
        // array("bookid"=>"", "nickname"=>"", "coursetitle"=>"")
        $bookingOptions = array();
        foreach($sbls as $sbl){
            if(empty($schedulesIndexedByBookId[$sbl['id']])){
               $bookingOption['bookingId'] = $sbl['id'];
               $bookingOption['nickname'] = $students[$sbl['studentId']]['nickname'];
               $bookingOption['course'] = $courses[$sbl['courseId']]['title'];
               
               array_push($bookingOptions, $bookingOption);
            }
        }

        // free classroom
        $allClassrooms = $this->getClassroomService()->findAllClassrooms();
        $freeClassrooms = array();
        foreach($allClassrooms as $classroom){
            if(empty($classrooms[$classroom['id']]))
                array_push($freeClassrooms, $classroom);
        }

        $totalBookings = count($sbls);
        $totalAvails = count($tats) - count($sbls);
        
        $Html = $this->renderView('TopxiaAdminBundle:Course:result-div.html.twig', array(
                 'bookingCount'=>$totalBookings,
                 'availCount'=>$totalAvails,
                 'sbls'=>$sbls,
                 'students'=>$students,
                 'tats'=>$tats,
                 'teachers'=>$teachers,
                 'schedules1'=>$schedulesIndexedByBookId,
                 'schedules2'=>$schedulesIndexedByAvaiId,
                 'classrooms'=>$classrooms,
                 'courses'=>$courses,
                 'bookingOptions'=>$bookingOptions,
                 'freeClassrooms'=>$freeClassrooms
            ));

        return $Html;
    }

    //每次点击time，执行本函数。
    //返回course bookings and course schedules
    public function getScheduleInfoAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {        	
        	$currentUser = $this->getCurrentUser();
        	
        	$data = $request->request->all();
        	$courseDate = $data['courseDate'];
        	$courseTime = $data['courseTime'];

            $Html = $this->getInfo($courseTime);

            return $this->createJsonResponse(array('html'=>$Html));
        }
    }

    //
    public function addScheduleAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {        	
        	$data = $request->request->all();

            $result = $this->getTeacherAvailableTimesService()->addSchedule($data);

            $Html = "";
            if($result['status'] == "success")
                $Html = $this->getInfo($data['lessonTS']);

            return $this->createJsonResponse(array('status'=>$result['status'], 'errorMsg'=>$result['errorMsg'], 'html'=>$Html));
        }
    }

    public function deleteScheduleAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {        	
        	$data = $request->request->all();
            $id = $data['id'];
            $courseTime = $data['courseTime'];

            $this->getTeacherAvailableTimesService()->deleteSchedule($id);

            $Html = $this->getInfo($courseTime);

            return $this->createJsonResponse(array('html'=>$Html));
        }
    }

    public function deleteAction(Request $request, $id)
    {
        $result = $this->getCourseService()->deleteCourse($id);
        return $this->createJsonResponse(true);
    }

    public function publishAction(Request $request, $id)
    {
        $this->getCourseService()->publishCourse($id);
        return $this->renderCourseTr($id);
    }

    public function closeAction(Request $request, $id)
    {
        $this->getCourseService()->closeCourse($id);
        return $this->renderCourseTr($id);
    }

    public function recommendAction(Request $request, $id)
    {
        $course = $this->getCourseService()->recommendCourse($id);
        return $this->renderCourseTr($id);
    }

    public function cancelRecommendAction(Request $request, $id)
    {
        $course = $this->getCourseService()->cancelRecommendCourse($id);
        return $this->renderCourseTr($id);
    }


    public function categoryAction(Request $request)
    {
        return $this->forward('TopxiaAdminBundle:Category:embed', array(
            'group' => 'course',
            'layout' => 'TopxiaAdminBundle:Course:layout.html.twig',
        ));
    }

    private function renderCourseTr($courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);

        return $this->render('TopxiaAdminBundle:Course:tr.html.twig', array(
            'user' => $this->getUserService()->getUser($course['userId']),
            'category' => $this->getCategoryService()->getCategory($course['categoryId']),
            'course' => $course ,
        ));
    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    private function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    private function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }
    
    protected function getTeacherAvailableTimesService()
    {
    	return $this->getServiceKernel()->createService('Course.TeacherAvailableTimesService');
    }

    protected function getStudentBookLessonsService()
    {
        return $this->getServiceKernel()->createService('Course.StudentBookLessonsService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    private function getClassroomService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.ClassroomService');
    }

    // 获取课程日期和课程时间 时间戳数组的索引
    protected  function getIndexesofTS()
    {
    	return	$nthDay = array(1 => mktime(0, 0, 0, date("m", strtotime("+1 day")), date("d", strtotime("+1 day")), date("Y", strtotime("+1 day"))),
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

    	return $courseDate = array( $nthDay[1] => date("m-d", $nthDay[1]) .  $weekDayArray[date("D", $nthDay[1])],
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
    	$timeStamps = array(
    					mktime(7, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(7, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(8, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(8, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(19, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(19, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(20, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(20, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(21, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(21, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(22, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)),
    					mktime(22, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey))
    			    );

    	return $timeStamps;
    } 

}

