<?php \defined('SYSPATH') or die('No direct script access.');

class Kohana_Dependency_Reference_Container extends Dependency_Reference {

	public function resolve(Dependency_Container $container)
	{
		return $container->get($this->_key);
	}

}

