<?php

namespace Topxia\Service\Course\Dao;

interface TeacherAvailableTimesDao
{
	const TABLENAME = 'teacher_available_times';
	
    public function getTeacherCounts($courseTime);

}