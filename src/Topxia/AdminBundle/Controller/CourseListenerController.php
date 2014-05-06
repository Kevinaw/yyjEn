<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class CourseListenerController extends BaseController
{

    public function indexAction (Request $request)
    {
    	$lockedListeners = $this->getCourseService()->findLockedListeners();
		$users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($lockedListeners, 'userId'));
		$courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($lockedListeners, 'courseId'));

        return $this->render('TopxiaAdminBundle:CourseListener:index.html.twig', array(
            'courses' => $courses ,
            'users' => $users,
            'lockedListeners' => $lockedListeners
        ));
    }

    public function approveAction(Request $request, $id, $userId)
    {
    	$user = $this->getUserService()->getUser($userId);
    	if (empty($user)) {
    		throw $this->createNotFoundException();
    	}
    	
    	$this->sendEmail(
    		$user['email'],
    		"审核通过",
			"你的课程试听申请已经通过，请登录网站查看课程信息。"
    	);
    	
    	$result = $this->getCourseService()->updateCourseMember($id, array('locked' => 0));

    	return $this->redirect($this->generateUrl('admin_course_listener'));
    }
    
    public function denyAction(Request $request, $id, $userId)
    {    	
    	$user = $this->getUserService()->getUser($userId);
    	if (empty($user)) {
    		throw $this->createNotFoundException();
    	}
    	 
    	$this->sendEmail(
    			$user['email'],
    			"课程试听被拒绝",
    			"你的课程试听申请被拒绝，请登录网站查看其他课程。"
    	);
    	
    	$result = $this->getCourseService()->deleteCourseMember($id);
    	
		return $this->redirect($this->generateUrl('admin_course_listener'));;
    }
    
    public function deleteAction(Request $request, $id)
    {
    	$result = $this->getCourseService()->deleteCourseMember($id);
    	return $this->redirect($this->generateUrl('admin_course_listener'));
    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }
}