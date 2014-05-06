<?php
namespace Topxia\Service\Content\Type;

class SuccessContentType extends ContentType
{
	public function getBasicFields()
	{
		return array('title', 'body', 'picture', 'editor');
	}

	public function getAlias()
	{
		return 'success';
	}

	public function getName()
	{
		return '成功例子';
	}

}