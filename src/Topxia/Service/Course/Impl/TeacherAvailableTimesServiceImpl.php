<?php
namespace Topxia\Service\Course\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\Course\TeacherAvailableTimesService;

class TeacherAvailableTimesServiceImpl extends BaseService implements TeacherAvailableTimesService
{
	public function getTeacherCounts($courseTime)
	{
		return $this->getTeacherAvailableTimesDao()->getTeacherCounts($courseTime);
	}
	
	// return value array(array("timestamp", "tag"),...), tag("have course", "registered", "NA")
	public function getFreetimesList($teacherId, $courseDate)
	{
		$freetimesList = $this->getCourseTimesTS($courseDate);
		
		foreach($freetimesList as $key => $freetime){
			//已排课
			$TAT = $this->getTeacherAvailableTimesDao()->searchTATCount(array(
						"availableDateTS" => $courseDate,
						"availableTimeTS" => $freetime['timestamp'],
						"teacherId" => (int) $teacherId,
						"haveCourse" => 1));
// 						), null, 0, 10000);
			if(0 != $TAT){
				$freetime['tag'] = "have course";
			}else{//已登记
				$TAT = $this->getTeacherAvailableTimesDao()->searchTATCount(array(
						"availableDateTS" => $courseDate,
						"availableTimeTS" => $freetime['timestamp'],
						"teacherId" => $teacherId,
						"haveCourse" => 0));
// 				), null, 0, 10000);
				if(!empty($TAT)){
					$freetime['tag'] = "available";
				}else //可选择
					$freetime['tag'] = "NA";
			}
			
			$freetimesList[$key] = $freetime;
		}
		
		return $freetimesList;		
	}

	public function updateTATs($teacherId, $courseDate, $courseTSs)
	{
		// $courseTS 是array
		$returnVal = array("status"=>"success", "error"=>"");
			
		// 先删除当天所有未排课时间，再一个一个的添加
		$this->getTeacherAvailableTimesDao()->deleteByTeacheridDateHavecourse($teacherId, $courseDate, 0);		
		
		if(!empty($courseTSs)){
			foreach ($courseTSs as $timeTS){
				//先看看是否已经排课，若排课则不能删除
				$TAT = $this->getTeacherAvailableTimesDao()->searchTATCount(array(
						"availableDateTS" => $courseDate,
						"availableTimeTS" => $timeTS,
						"teacherId" => $teacherId,
						"haveCourse" => 1));
				if(0 == $TAT){//未排课，未登记则添加
					$this->getTeacherAvailableTimesDao()->addTAT(array(
								"availableDateTS" => $courseDate,
								"availableTimeTS" => $timeTS,
								"teacherId" => $teacherId,
								"createTime" => time()
						));					
				}
			}
		}
		return $returnVal;	
	}
	
	private function getTeacherAvailableTimesDao ()
	{
		return $this->createDao('Course.TeacherAvailableTimesDao');
	}

	private function getCourseScheduleDao ()
	{
		return $this->createDao('Course.CourseScheduleDao');
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