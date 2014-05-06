<?php

namespace Topxia\Service\Course\Dao;

interface CourseScheduleDao
{
    const TABLENAME = 'course_schedule';

    public function getCourseSchedule($id);

    public function findCourseSchedulesByIds(array $ids);

	public function searchCourseSchedules($conditions, $orderBy, $start, $limit);

	public function searchCourseScheduleCount($conditions);

    public function addCourseSchedule($courseschedule);

    public function updateCourseSchedule($id, $fields);

    public function deleteCourseSchedule($id);

}