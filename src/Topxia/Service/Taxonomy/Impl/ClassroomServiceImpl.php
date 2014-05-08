<?php
namespace Topxia\Service\Taxonomy\Impl;

use Topxia\Service\Taxonomy\ClassroomService;
use Topxia\Service\Common\BaseService;
use Topxia\Common\ArrayToolkit;

class ClassroomServiceImpl extends BaseService implements ClassroomService
{
	public function findClassroomsByIds(array $ids)
	{
		$classrooms = $this->getClassroomDao()->findClassroomsByIds($ids);
        return ArrayToolkit::index($classrooms, 'id');
	}
    
    public function getClassroom($id)
    {
        if (empty($id)) {
            return null;
        }
        return $this->getClassroomDao()->getClassroom($id);
    }

    public function findAllClassrooms()
    {
        return $this->getClassroomDao()->findAllClassrooms();
    }

    public function createClassroom(array $classroom)
    {
        $classroom = ArrayToolkit::parts($classroom, array('name', 'serverAddress', 'username', 'password'));

        if (!ArrayToolkit::requireds($classroom, array('name', 'serverAddress', 'username', 'password'))) {
            throw $this->createServiceException("缺少必要参数，，添加分类失败");
        }

        $this->filterClassroomFields($classroom);
		
        $classroom['createTime'] = time();
        $classroom = $this->getClassroomDao()->addClassroom($classroom);

        $this->getLogService()->info('classroom', 'create', "添加教室 {$classroom['name']}(#{$classroom['id']})", $classroom);

        return $classroom;
    }

    public function updateClassroom($id, array $fields)
    {
        $classroom = $this->getClassroom($id);
        if (empty($classroom)) {
            throw $this->createNoteFoundException("教室(#{$id})不存在，更新教室失败！");
        }

        $fields = ArrayToolkit::parts($fields, array('name', 'serverAddress', 'username', 'password'));
        if (empty($fields)) {
            throw $this->createServiceException('参数不正确，更新教室失败！');
        }

        $this->filterClassroomFields($fields);

        $this->getLogService()->info('classroom', 'update', "编辑教室 {$fields['name']}(#{$id})", $fields);

        return $this->getClassroomDao()->updateClassroom($id, $fields);
    }

    public function deleteClassroom($id)
    {
        $classroom = $this->getClassroom($id);
        if (empty($classroom)) {
            throw $this->createNotFoundException();
        }

        $this->getClassroomDao()->deleteClassroom($id);

        $this->getLogService()->info('classroom', 'delete', "删除教室{$classroom['name']}(#{$id})");
    }

    private function filterClassroomFields(&$classroom, $releatedClassroom = null)
    {
        foreach (array_keys($classroom) as $key) {
            switch ($key) {
                case 'name':
                    $classroom['name'] = (string) $classroom['name'];
                    if (empty($classroom['name'])) {
                        throw $this->createServiceException("名称不能为空，保存教室失败");
                    }
                    break;
                case 'serverAddress':
                    if (empty($classroom['serverAddress'])) {
                        throw $this->createServiceException("服务器地址不能为空，保存教室失败");
                    } else {
                        if (!preg_match("/^[a-zA-Z0-9_|:|\/|.]+$/", $classroom['serverAddress'])) {
                            throw $this->createServiceException("服务器地址({$classroom['serverAddress']})含有非法字符，保存教室失败");
                        }
                    }
                    break;
                case 'username':
                    $classroom['username'] = (string) $classroom['username'];
                    if (empty($classroom['username'])) {
                        throw $this->createServiceException("用户名不能为空，保存教室失败");
                    }
                    break;
                case 'password':
                    $classroom['password'] = (string) $classroom['password'];
                    if (empty($classroom['password'])) {
                        throw $this->createServiceException("密码不能为空，保存教室失败");
                    }
                    break;
            }
        }

        return $classroom;
    }

    private function getClassroomDao ()
    {
        return $this->createDao('Taxonomy.ClassroomDao');
    }

    private function getLogService()
    {
        return $this->createService('System.LogService');
    }
    
    public function isClassroomNameAvalieable($name, $exclude=null)
    {
    	if (empty($name)) {
    		return false;
    	}
    
    	if ($name == $exclude) {
    		return true;
    	}
    
    	$tag = $this->getClassroomByName($name);
    
    	return $tag ? false : true;
    }

    public function getClassroomByName($name)
    {
    	if (empty($name)) {
    		return null;
    	}
    	return $this->getClassroomDao()->getClassroomByName($name);
    }
    
}
