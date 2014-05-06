<?php

namespace Topxia\Service\Course;

interface StudentBookLessonsService
{
	// 判断是否已经预约课程
	public function isBooked($userId, $courseId, $dateTS);
	
	// 取出我的预约
	public function findByUserCourseDate($userId, $courseId, $dateTS);

	// 获取预约总数
	public function getBookingCounts($courseTime);
}