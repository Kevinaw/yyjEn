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
		$returnVal = array("status"=>"success", "error"=>"");
		
        $this->removeBookingsByCourseAndDate($userId, $courseId, $dateTS);

		if(!empty($timeTSs)){
			$member = $this->getMemberDao()->findMembersByUserIdAndCourseIdAndRoleAndIsLearned($userId, $courseId, 'student', '0');
			if($member['remainingNum'] < count($timeTSs)){
				$returnVal["error"] = "您只有" . $this->getAvailableBookingsCount() . "次课可预约，请重新选择！";
				$returnVal["status"] = "fail";
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
	
	// return value array(array("timestamp", "tag"),...), tag("arranged", "booked", "free", "NA")
	public function getTSandStatus($studentId, $courseDate)
	{
		$tsList = $this->getCourseTimesTS($courseDate);
		
		foreach($tsList as $key => $freetime){
			//已排课
			$sblCount = $this->getStudentBookLessonsDao()->searchSBLCount(array(
						"dateTS" => $courseDate,
						"timeTS" => $freetime['timestamp'],
						"studentId" => $studentId,
						"isArranged" => '1'));

			if(0 != $sblCount){//已排课
				$freetime['tag'] = "arranged";
			}else{
				$sblCount = $this->getStudentBookLessonsDao()->searchSBLCount(array(
						"dateTS" => $courseDate,
						"timeTS" => $freetime['timestamp'],
						"studentId" => $studentId,
						"isArranged" => 0));
				if(!empty($sblCount)){//已预约
					$freetime['tag'] = "booked";
				}else {
        			$studentCount = $this->getBookingCounts($freetime["timestamp"]);
        			$teacherCount = $this->getTeacherAvailableTimesDao()->getTeacherCounts($freetime["timestamp"]);
        			
        			if($studentCount < $teacherCount) { //可预约
                        $freetime['tag'] = "free";
                    } else{//已约满
                        $freetime['tag'] = "NA";
                    }
                }
			}
			
			$tsList[$key] = $freetime;
		}
		
		return $tsList;		
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

    protected function getTeacherAvailableTimesDao()
    {
    	return $this->createDao('Course.TeacherAvailableTimesDao');
    }

	//获取上课时间的时间戳,根据上课日期的时间戳
	private function getCourseTimesTS($courseDateTs)
	{
		$dayKey = $courseDateTs;

		$timeArray = array(
				array( "timestamp" => mktime(7, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(7, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(8, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(8, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(19, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(19, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(20, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(20, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(21, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(21, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(22, 00, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0"),
				array( "timestamp" => mktime(22, 30, 00, date("m", $dayKey), date("d", $dayKey), date("Y", $dayKey)), "tag" => "0")
		);
	
		return $timeArray;
	}

}
