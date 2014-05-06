<?php
namespace Topxia\AdminBundle\Controller;

use Topxia\AdminBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Service\Common\ServiceException;

class ClassroomController extends BaseController
{

	public function indexAction(Request $request)
	{
		//$total = $this->getClassroomService()->getAllTagCount();
		//$paginator = new Paginator($request, $total, 20);
		$classrooms = $this->getClassroomService()->findAllClassrooms();
		return $this->render('TopxiaAdminBundle:Classroom:index.html.twig', array(
			'classrooms' => $classrooms,
		));
	}

	public function createAction(Request $request)
	{
		if ('POST' == $request->getMethod()) {
			$classroom = $this->getClassroomService()->createClassroom($request->request->all());

			return $this->render('TopxiaAdminBundle:Classroom:list-tr.html.twig', array('classroom' => $classroom));
		}

		return $this->render('TopxiaAdminBundle:Classroom:classroom-modal.html.twig', array(
			'classroom' => array('id' => 0, 'name' => '', 'serverAddress'=>'', 'username'=>'', 'password'=>'')
		));
	}

	public function updateAction(Request $request, $id)
	{
		$classroom = $this->getClassroomService()->getClassroom($id);
		if (empty($classroom)) {
			throw $this->createNotFoundException();
		}

		if ('POST' == $request->getMethod()) {
			$classroom = $this->getClassroomService()->updateClassroom($id, $request->request->all());
			return $this->render('TopxiaAdminBundle:Classroom:list-tr.html.twig', array(
				'classroom' => $classroom
			));
		}

		return $this->render('TopxiaAdminBundle:Classroom:classroom-modal.html.twig', array(
			'classroom' => $classroom
		));
	}

	public function deleteAction(Request $request, $id)
	{
		$this->getClassroomService()->deleteClassroom($id);
		return $this->createJsonResponse(true);
	}

	public function checkNameAction(Request $request)
	{
		$name = $request->query->get('value');
		$exclude = $request->query->get('exclude');

		$avaliable = $this->getClassroomService()->isClassroomNameAvalieable($name, $exclude);

        if ($avaliable) {
            $response = array('success' => true, 'message' => '');
        } else {
            $response = array('success' => false, 'message' => '标签已存在');
        }

        return $this->createJsonResponse($response);
	}

	private function getClassroomService()
	{
        return $this->getServiceKernel()->createService('Taxonomy.ClassroomService');
	}

	private function getClassroomWithException($classroomId)
	{
		$classroom = $this->getClassroomService()->getClassroom($classroomId);
		if (empty($classroom)) {
			throw $this->createNotFoundException('教室不存在!');
		}
		return $classroom;
	}

}