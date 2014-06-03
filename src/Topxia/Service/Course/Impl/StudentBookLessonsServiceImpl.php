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
		
        // 取出这一天的预定时间，如果已经预约的时间，且在这次提交的时间列表内，则不更新；只对其他时间进行删除和添加操作。
        $sbls = $this->searchSBLs(array(
                'studentId'=>$userId,
                'courseId'=>$courseId,
                'dateTS'=>$dateTS), null, 0, 10000);
        foreach($sbls as $sbl)
        {
            // 在本次提交的时间列表里，则从列表中删除,之后对他不做任何操作
            if( false !== ($key = array_search($sbl['timeTS'], $timeTSs)))
            {
                unset($timeTSs[$key]);
            }
            // 不在本次提交的时间列表里，则删除
            else
            {
                //如果已经排课，则删除排课，同时更新老师的排课标签
                $schedule = $this->getCourseScheduleDao()->searchCourseSchedules(array(
                                    'studentbookId'=>$sbl['id'],
                                    'lessonTS'=>$sbl['timeTS']), array('id', 'ASC'), 0, 10);
                // 删除预定的同时删除排课
                $this->getStudentBookLessonsDao()->removeBooking($sbl['id']);
               
                if (! empty($schedule))
                    $this->getTeacherAvailableTimesDao()->updateTAT($schedule[0]['teacheravaliableId'], array('haveCourse'=>0));

			    $member = $this->getMemberDao()->findMembersByUserIdAndCourseIdAndRoleAndIsLearned($userId, $courseId, 'student', '0');
			    $member['remainingNum'] = $member['remainingNum'] + 1;
			    $this->getMemberDao()->updateMember($member['id'], $member);
            }
        }

		if(!empty($timeTSs)){
			$member = $this->getMemberDao()->findMembersByUserIdAndCourseIdAndRoleAndIsLearned($userId, $courseId, 'student', '0');
			if($member['remainingNum'] < count($timeTSs)){
				$returnVal["error"] = "您只有" . $member['remainingNum'] . "次课可预约，请重新选择！";
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

    public function findRemainingNum($userId, $courseId)
    {
	    $member = $this->getMemberDao()->findMembersByUserIdAndCourseIdAndRoleAndIsLearned($userId, $courseId, 'student', '0');
        if(!empty($member))
	        return $member['remainingNum'];
        else
            return 0;
    }
	
	// return value array(array("timestamp", "tag"),...), tag("arranged", "booked", "free", "NA")
	public function getTSandStatus($studentId, $courseDate, $courseId)
	{
		$tsList = $this->getCourseTimesTS($courseDate);
		
		foreach($tsList as $key => $freetime){
			//已排课
			if(0 != $this->getStudentBookLessonsDao()->searchSBLCount(array(
						"dateTS" => $courseDate,
						"timeTS" => $freetime['timestamp'],
						"studentId" => $studentId,
                        "courseId" => $courseId,
						"isArranged" => '1'))){
				$freetime['tag'] = "arranged";
			}//已预约
            elseif(0 != $this->getStudentBookLessonsDao()->searchSBLCount(array(
						"dateTS" => $courseDate,
						"timeTS" => $freetime['timestamp'],
						"studentId" => $studentId,
						"courseId" => $courseId,
						"isArranged" => '0'))){
				$freetime['tag'] = "booked";
			} //已被其他课选择
            elseif(0 != $this->getStudentBookLessonsDao()->searchSBLCount(array(
						"dateTS" => $courseDate,
						"timeTS" => $freetime['timestamp'],
						"studentId" => $studentId))){
                $freetime['tag'] = "NA";
            } //看看是否可预约
            else{
            	$studentCount = $this->getBookingCounts($freetime["timestamp"]);
            	$teacherCount = $this->getTeacherAvailableTimesDao()->getTeacherCounts($freetime["timestamp"]);
            	
            	if($studentCount < $teacherCount) { //可预约
                    $freetime['tag'] = "free";
                } else{//已约满
                    $freetime['tag'] = "NA";
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
        if( $sort !== '' AND $sort !== null)
		    $orderBy = $sort;
		
		return $this->getStudentBookLessonsDao()->searchSBLs($conditions, $orderBy, $start, $limit);
	}

	public function searchArrangedSBLs($courseId, $studentId)
    {
		$sbls = $this->getStudentBookLessonsDao()->searchArrangedSBLs($courseId, $studentId);
        // 查看课程时间, 根据课程时间决定按钮状态
        if(null !== $sbls)
        {
            foreach($sbls as $key => $value)
            {
                $tnow = time();
                // 距课程开始大于10分钟，教室状态为被占用
                if($tnow < $value['timeTS'] - 600)
                {
                    $value['crStatus'] = 'occupied';
                }
                // 开课前10分钟和开课后30分钟，可进入教室
                elseif($value['timeTS'] - 600 <= $tnow and $tnow < $value['timeTS'] + 1800)
                {
                    $value['crStatus'] = 'enabled';
                }
                // 开课30分钟后，不能进入教室
                elseif($tnow >= $value['timeTS'] + 1800)
                {
                    $value['crStatus'] = 'disabled';
                }
                $sbls[$key] = $value;
            }
        }

        return $sbls;
    }

    public function formClassroomUrl($bookingId, $classroomId, $courseTS)
    {
		$booking = $this->getStudentBookLessonsDao()->getBooking($bookingId);
		if (empty($booking)) {
			throw $this->createNotFoundException();
		}

		$classroom = $this->getClassroomDao()->getClassroom($classroomId);
		if (empty($classroom)) {
			throw $this->createNotFoundException();
		}

		$user = $this->getCurrentUser();
		if (empty($user->id)) {
			throw $this->createAccessDeniedException('未登录用户，无权操作！');
		}
/*
        if( $courseTS - 600 > time() or $courseTS + 1800 < time()){
			throw $this->createAccessDeniedException('不是上课时间，无权操作！');
        }

        if( $booking['timeTS'] !== $courseTS ){
			throw $this->createAccessDeniedException('不是预约时间，无权操作！');
        }
*/
        return $classroom['serverAddress'] . "/as/wapi/goto_downloader?role=attendee" . 
                "&name=" .  $user['nickname'] .
                "&meeting_id=" . $classroom['meetingId'] . 
                "&password=" . $classroom['password'] .
                "&sumbit=submit";
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

	private function getCourseScheduleDao ()
	{
		return $this->createDao('Course.CourseScheduleDao');
	}

	private function getClassroomDao ()
	{
		return $this->createDao('Taxonomy.ClassroomDao');
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
