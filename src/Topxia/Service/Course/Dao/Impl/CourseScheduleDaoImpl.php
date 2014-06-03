<?php

namespace Topxia\Service\Course\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Course\Dao\CourseScheduleDao;

class CourseScheduleDaoImpl extends BaseDao implements CourseScheduleDao
{

    public function getCourseSchedule($id)
    {
        $sql = "SELECT * FROM {$this->getTablename()} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function getCourseScheduleByBookingId($id)
    {
        $sql = "SELECT * FROM {$this->getTablename()} WHERE studentbookId = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }
    
    public function findCourseSchedulesByIds(array $ids)
    {
        if(empty($ids)){
            return array();
        }
        $marks = str_repeat('?,', count($ids) - 1) . '?';
        $sql ="SELECT * FROM {$this->getTablename()} WHERE id IN ({$marks});";
        return $this->getConnection()->fetchAll($sql, $ids);
    }

    public function searchAllJoinedSchedules($start, $limit)
    {
        $this->filterStartLimit($start, $limit);
		$sql = "SELECT course_schedule.*, teacher_available_times.teacherId, " .
                "student_booked_lessons.studentId, student_booked_lessons.dateTS, student_booked_lessons.courseId " .
                "FROM course_schedule " . 
                "LEFT JOIN teacher_available_times ON teacher_available_times.id = course_schedule.teacheravaliableId " .
                "LEFT JOIN student_booked_lessons ON student_booked_lessons.id = course_schedule.studentbookId " . 
                "ORDER BY course_schedule.lessonTS DESC LIMIT $limit OFFSET $start";
		return $this->getConnection()->fetchAll($sql) ? : null;
    }

    public function searchCourseSchedules($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);
        return $builder->execute()->fetchAll() ? : array(); 
    }

    public function searchCourseScheduleCount($conditions)
    {
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('COUNT(id)');
        return $builder->execute()->fetchColumn(0);
    }

    public function addCourseSchedule($courseschedule)
    {
        $affected = $this->getConnection()->insert(self::TABLENAME, $courseschedule);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert course error.');
        }
        return $this->getCourseSchedule($this->getConnection()->lastInsertId());
    }

    public function updateCourseSchedule($id, $fields)
    {
        $this->getConnection()->update(self::TABLENAME, $fields, array('id' => $id));
        return $this->getCourseSchedule($id);
    }

    public function deleteCourseSchedule($id)
    {
        return $this->getConnection()->delete(self::TABLENAME, array('id' => $id));
    }

    private function _createSearchQueryBuilder($conditions)
    {

        $builder = $this->createDynamicQueryBuilder($conditions)
            ->from(self::TABLENAME, 'f')
            ->andWhere('studentbookId = :studentbookId')
            ->andWhere('teacheravailableId = :teacheravailableId')
            ->andWhere('classroomId = :classroomId')
            ->andWhere('lessonTS = :lessonTS');

        return $builder;
    }

    private function getTablename()
    {
        return self::TABLENAME;
    }
}
