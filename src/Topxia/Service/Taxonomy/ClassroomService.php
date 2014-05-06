<?php
namespace Topxia\Service\Taxonomy;

interface ClassroomService
{
    public function getClassroom($id);

    public function findAllClassrooms();

    public function createClassroom(array $classroom);

    public function updateClassroom($id, array $fields);

    public function deleteClassroom($id);

}