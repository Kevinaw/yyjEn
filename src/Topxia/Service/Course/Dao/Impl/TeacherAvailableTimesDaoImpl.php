<?php

namespace Topxia\Service\Course\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Course\Dao\TeacherAvailableTimesDao;

class TeacherAvailableTimesDaoImpl extends BaseDao implements TeacherAvailableTimesDao
{
	// TeacherAvailableTimes, TAT for short
	
	public function getTeacherCounts($courseTime)
	{
        $sql = "SELECT COUNT( m.id ) FROM {$this->getTablename()} m WHERE availableTimeTS = ?";

        return $this->getConnection()->fetchColumn($sql,array($courseTime));
		
	}
	
	public function getTAT($id)
	{
		$sql = "SELECT * FROM {$this->getTablename()} WHERE id = ? LIMIT 1";
		return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
	}

	public function getTATByTeacheridDateTime($teacherId, $courseDate, $courseTS)
	{
		$sql = "SELECT * FROM {$this->getTablename()} WHERE teacherId = ? AND availableDateTS = ? AND availableTimeTS = ? LIMIT 1";
		return $this->getConnection()->fetchAssoc($sql, array($teacherId, $courseDate, $courseTS)) ? : null;
	}

	public function deleteByTeacheridDateHavecourse($teacherId, $courseDate, $haveCourse)
	{
		$sql = "delete FROM {$this->getTablename()} WHERE teacherId = ? AND availableDateTS = ? AND haveCourse = ?";
		return $this->getConnection()->executeUpdate($sql, array($teacherId, $courseDate, $haveCourse));
	}
	
	public function findTATByIds(array $ids)
	{
		if(empty($ids)){
			return array();
		}
		$marks = str_repeat('?,', count($ids) - 1) . '?';
		$sql ="SELECT * FROM {$this->getTablename()} WHERE id IN ({$marks});";
		return $this->getConnection()->fetchAll($sql, $ids);
	}
	
	public function searchTAT($conditions, $orderBy, $start, $limit)
	{
		$builder = $this->_createSearchQueryBuilder($conditions)
		->select('*')
		->orderBy($orderBy[0], $orderBy[1])
		->setFirstResult($start)
		->setMaxResults($limit);
		return $builder->execute()->fetchAll() ? : array();
	}
	
	public function searchTATCount($conditions)
	{
		$builder = $this->_createSearchQueryBuilder($conditions)
		->select('COUNT(id)');
		return $builder->execute()->fetchColumn(0);
	}
	
	public function searchJoinedTATs($userId, $start, $limit)
	{
		$sql = "SELECT * FROM teacher_available_times " . 
                "LEFT JOIN course_schedule ON teacher_available_times.id = course_schedule.teacheravaliableId " .
                "LEFT JOIN student_booked_lessons ON student_booked_lessons.id = course_schedule.studentbookId " . 
                "WHERE teacher_available_times.teacherId = ? AND teacher_available_times.haveCourse = 1 " . 
                "ORDER BY teacher_available_times.availableTimeTS DESC LIMIT 30 OFFSET 0";
		return $this->getConnection()->fetchAll($sql, array($userId/*, $limit, $start*/)) ? : null;
	}

	public function addTAT($tat)
	{
		$affected = $this->getConnection()->insert(self::TABLENAME, $tat);
		if ($affected <= 0) {
			throw $this->createDaoException('Insert course error.');
		}
		return $this->getTAT($this->getConnection()->lastInsertId());
	}
	
	public function updateTAT($id, $fields)
	{
		$this->getConnection()->update(self::TABLENAME, $fields, array('id' => $id));
		return $this->getTAT($id);
	}
	
	public function deleteTAT($id)
	{
		return $this->getConnection()->delete(self::TABLENAME, array('id' => $id));
	}
	
	private function _createSearchQueryBuilder($conditions)
	{	
		$builder = $this->createDynamicQueryBuilder($conditions)
		->from(self::TABLENAME, 'teacher_available_times')
		->andWhere('availableDateTS = :availableDateTS')
		->andWhere('availableTimeTS = :availableTimeTS')
		->andWhere('teacherId = :teacherId')
		->andWhere('haveCourse = :haveCourse');
	
		return $builder;
	}
	
	private function getTablename()
	{
		return self::TABLENAME;
	}
}
