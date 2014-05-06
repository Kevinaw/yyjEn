<?php
namespace Topxia\WebBundle\Util;

use Topxia\Service\Common\ServiceKernel;

class ClassroomBuilder
{
	public function buildChoices()
	{
        $choices = array();
        $classrooms = $this->getClassroomService()->findAllClassrooms();

        foreach ($classrooms as $classroom) {
            $choices[$classroom['id']] = $classroom['name'];
        }

        return $choices;
	}

    private function getClassroomService()
    {
        return ServiceKernel::instance()->createService('Taxonomy.ClassroomService');
    }
}
    