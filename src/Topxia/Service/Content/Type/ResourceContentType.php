<?php
namespace Topxia\Service\Content\Type;

class ResourceContentType extends ContentType
{
	public function getBasicFields()
	{
		return array('title', 'body', 'picture', 'editor');
	}

	public function getAlias()
	{
		return 'resource';
	}

	public function getName()
	{
		return '学习资源';
	}

}