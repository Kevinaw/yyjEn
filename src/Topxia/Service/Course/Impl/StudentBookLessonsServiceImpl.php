<?php
namespace Topxia\Service\Course\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\Course\StudentBookLessonsService;

class StudentBookLessonsServiceImpl extends BaseService implements StudentBookLessonsService
{
	public function isBooked($userId, $courseId, $dateTS)
	{
		$studentBookings = $this->getStudentBookLessonsDao()->findByUserCourseDate($userId, $courseId, $dateTS);

		return empty($studentBookings) ? false : true;
	}

	public function findByUserCourseDate($userId, $courseId, $dateTS)
	{
		return $studentBookings = $this->getStudentBookLessonsDao()->findByUserCourseDate($userId, $courseId, $dateTS);
	}
	
	public function getBookingCounts($courseTime)
	{
		return $this->getStudentBookLessonsDao()->getBookingCounts($courseTime);
	}
	
	//
	public function addBookings($userId, $courseId, $dateTS, $timeTSs)
	{
		$returnVal = array("status"=>"fail", "error"=>"");
		
		if(empty($timeTSs)){
			$returnVal["error"] = "上课时间未选择，预约课程失败";
			
		}else {			
			$member = $this->getMemberDao()->findMembersByUserIdAndCourseIdAndRoleAndIsLearned($userId, $courseId, 'student', '0');
			if($member['remainingNum'] < count($timeTSs)){
				$returnVal["error"] = "您只有" . $this->getAvailableBookingsCount() . "次课可预约，请重新选择！";
				return $returnVal;
			}
			
			foreach ($timeTSs as $timeTS){
				$this->getStudentBookLessonsDao()->addBooking($userId, $courseId, $dateTS, $timeTS);
				$member['remainingNum'] = $member['remainingNum'] - 1;
				$this->getMemberDao()->updateMember($member['id'], $member);
			}
			$returnVal['status'] = "success";
		}
		return $returnVal;
	}
	
	//
	public function removeBookingsByCourseAndDate($userId, $courseId, $dateTS)
	{
		$affectedNum = $this->getStudentBookLessonsDao()->removeBookingsByCourseAndDate($userId, $courseId, $dateTS);
		
		if($affectedNum > 0){
			$member = $this->getMemberDao()->findMembersByUserIdAndCourseIdAndRoleAndIsLearned($userId, $courseId, 'student', '0');
			$member['remainingNum'] = $member['remainingNum'] + $affectedNum;
			$this->getMemberDao()->updateMember($member['id'], $member);
		}
		return true;
	}		

	public function searchSBLs($conditions, $sort = '', $start, $limit)
	{
		$orderBy = array('studentId', 'ASC');
		
		return $this->getStudentBookLessonsDao()->searchSBLs($conditions, $orderBy, $start, $limit);
	}

	private function getStudentBookLessonsDao ()
	{
		return $this->createDao('Course.StudentBookLessonsDao');
	}
	
	private function getMemberDao ()
	{
		return $this->createDao('Course.CourseMemberDao');
	}
}
