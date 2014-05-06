<?php
namespace Topxia\Service\Content\Type;

class ReviewContentType extends ContentType
{
	public function getBasicFields()
	{
		return array('title', 'body', 'picture', 'editor');
	}

	public function getAlias()
	{
		return 'review';
	}

	public function getName()
	{
		return '地道点评';
	}

}