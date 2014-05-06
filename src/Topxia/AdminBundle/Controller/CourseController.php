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
    
    	return $this->render('TopxiaAdminBundle:Course:arrange.html.twig', array(
    			'conditions' => $conditions,
    			'courses' => $courses ,
    			'users' => $users,
    			'categories' => $categories,
    			'paginator' => $paginator
    	));
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
}