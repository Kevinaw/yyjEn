<?php

namespace Topxia\Service\Course\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Course\Dao\StudentBookLessonsDao;

class StudentBookLessonsDaoImpl extends BaseDao implements StudentBookLessonsDao
{
	public function getBooking($id)
	{
		$sql = "SELECT * FROM {$this->getTablename()} WHERE id = ?";
		return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
	}
	
	public function findByUserCourseDate($userId, $courseId, $dateTS)
	{
		$sql = "SELECT * FROM {$this->getTablename()} WHERE studentId = ? AND courseId = ? AND dateTS = ?";
		return $this->getConnection()->fetchAll($sql, array($userId, $courseId, $dateTS));
		
	}
	
	public function getBookingCounts($courseTime)
	{
        $sql = "SELECT COUNT( m.id ) FROM {$this->getTablename()} m WHERE timeTS = ?";

        return $this->getConnection()->fetchColumn($sql,array($courseTime));
		
	}

	public function addBooking($userId, $courseId, $dateTS, $timeTS)
	{
		$booking = array(
				"studentId" => $userId,
				"courseId" => $courseId,
				"dateTS" => $dateTS,
				"timeTS" =>	$timeTS,
				"createTime" => time()
				);
				
		$affected = $this->getConnection()->insert(self::TABLENAME, $booking);
		if ($affected <= 0) {
			throw $this->createDaoException('Insert student_booked_lessons error.');
		}
		return $this->getBooking($this->getConnection()->lastInsertId());
	}
	
	public function removeBookingsByCourseAndDate($userId, $courseId, $dateTS)
	{
        // 已排课的不能删除
		$sql = "DELETE FROM {$this->getTablename()} WHERE studentId = ? AND courseId = ? AND dateTS = ? AND isArranged = ?";
		return $this->getConnection()->executeUpdate($sql, array($userId, $courseId, $dateTS, 0));
	}
	
	public function searchSBLCount($conditions)
	{
		$builder = $this->_createSearchQueryBuilder($conditions)
		->select('COUNT(id)');

		return $builder->execute()->fetchColumn(0);
	}

	public function updateSBL($id, $fields)
	{
		$this->getConnection()->update(self::TABLENAME, $fields, array('id' => $id));
		return $this->getBooking($id);
	}

	public function searchSBLs($conditions, $orderBy, $start, $limit)
	{
		//$this->filterStartLimit($start, $limit);
		$builder = $this->_createSearchQueryBuilder($conditions)
		->select('*')
		->orderBy($orderBy[0], $orderBy[1])
		->setFirstResult($start)
		->setMaxResults($limit);

		return $builder->execute()->fetchAll() ? : array();
	}

	private function _createSearchQueryBuilder($conditions)
	{	
		$builder = $this->createDynamicQueryBuilder($conditions)
		->from(self::TABLENAME, 'sbl')
		->andWhere('dateTS = :dateTS')
		->andWhere('timeTS = :timeTS')
		->andWhere('courseId = :courseId')
		->andWhere('studentId = :studentId')
		->andWhere('isArranged = :isArranged');
	
		return $builder;
	}

    private function getTablename()
    {
        return self::TABLENAME;
    }
}
