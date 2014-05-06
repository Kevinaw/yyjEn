<?php

namespace Topxia\Service\Taxonomy\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Taxonomy\Dao\ClassroomDao;

class ClassroomDaoImpl extends BaseDao implements ClassroomDao 
{

	protected $table = 'classroom';

	public function addClassroom($classroom) 
    {
		$affected = $this->getConnection()->insert($this->table, $classroom);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert classroom error.');
        }
        return $this->getClassroom($this->getConnection()->lastInsertId());
	}

	public function deleteClassroom($id) 
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
	}

	public function getClassroom($id) 
    {
		$sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id));
	}

	public function updateClassroom($id, $classroom) 
    {
        $this->getConnection()->update($this->table, $classroom, array('id' => $id));
        return $this->getClassroom($id);
	}

    public function findAllClassrooms()
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->getConnection()->fetchAll($sql) ? : array();
    }
    
    public function getClassroomByName($name)
    {
    	$sql = "SELECT * FROM {$this->table} WHERE name = ?";
    	return $this->getConnection()->fetchAssoc($sql, array($name));
    }

}