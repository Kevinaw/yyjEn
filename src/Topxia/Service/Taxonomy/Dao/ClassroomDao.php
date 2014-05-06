<?php

namespace Topxia\Service\Taxonomy\Dao;

interface ClassroomDao {

	public function addClassroom($classroom);

	public function deleteClassroom($id);

	public function getClassroom($id);

	public function updateClassroom($id, $classroom);

	public function findAllClassrooms();

}