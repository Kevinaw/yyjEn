<?php

namespace Topxia\Service\Course\Dao;

interface StudentBookLessonsDao
{
	const TABLENAME = 'student_booked_lessons';
	
    public function findByUserCourseDate($userId, $courseId, $dateTS);

}