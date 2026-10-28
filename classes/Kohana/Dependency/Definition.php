<?php \defined('SYSPATH') or die('No direct script access.');

class Kohana_Dependency_Definition {

	public static function factory()
	{
		return new Dependency_Definition;
	}

	protected $_class       = NULL;
	protected $_path        = NULL;
	protected $_constructor = NULL;
	protected $_arguments   = array();
	protected $_shared      = FALSE;
	protected $_methods     = array();

    public function getClass()
    {
        return $this->_class;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getConstructor()
    {
        return $this->_constructor;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->_arguments;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->_methods;
    }

	public function from_array(array $settings)
	{
		if (isset($settings['class']))       $this->set_class($settings['class']);
		if (isset($settings['path']))        $this->set_path($settings['path']);
		if (isset($settings['constructor'])) $this->set_constructor($settings['constructor']);
		if (isset($settings['arguments']))   $this->set_arguments((array) $settings['arguments']);
		if (isset($settings['shared']))      $this->set_shared($settings['shared']);
		if (isset($settings['methods']))     $this->set_methods((array) $settings['methods']);

		return $this;
	}

	public function set_class($class)
	{
		$this->_class = $class;

		return $this;
	}

	public function set_path($path)
	{
		// Make sure the path is a string
		if ( ! \is_string($path))
		{
			$path = '';
		}

		// Make sure the path exists
		$file_path = NULL;
		if (\strpos($path, '/') !== FALSE)
		{
			[$directory, $file] = \explode('/', $path, 2);
			$file_path = Kohana::find_file($directory, $file);

			if (empty($file_path)) {
			    throw Dependency_Exception::emptyFilePath();
            }
		}

		$this->_path = $file_path;

		return $this;
	}

	public function set_constructor($method)
	{
		$this->_constructor = $method;

		return $this;
	}

	public function set_arguments(array $arguments)
	{
		$this->_arguments = array();
		foreach ($arguments as $argument)
		{
			$this->add_argument($argument);
		}

		return $this;
	}

	public function set_shared($shared)
	{
		$this->_shared = (bool) $shared;

		return $this;
	}

	public function set_methods(array $methods)
	{
		$this->_methods = array();
		foreach ($methods as $method)
		{
			if (isset($method[0]))
			{
				$this->add_method($method[0], $method[1] ?? array());
			}
		}

		return $this;
	}

	public function add_argument($argument)
	{
		$this->_arguments[] = $this->_handle_reference($argument);

		return $this;
	}

	public function add_method($method, array $arguments = array())
	{
		foreach ($arguments as & $argument)
		{
			$argument = $this->_handle_reference($argument);
		}

		$this->_methods[$method] = $arguments;

		return $this;
	}

	public function is_shared()
	{
		return (bool) $this->_shared;
	}

	public function merge_with(Dependency_Definition $right)
	{
		$left = clone $this;

		if ( ! empty($right->_class))
		{
			$left->_class = $right->_class;
		}

		if ( ! empty($right->_path))
		{
			$left->_path = $right->_path;
		}

		if ( ! empty($right->_constructor))
		{
			$left->_constructor = $right->_constructor;
		}

		if ( ! empty($right->_arguments))
		{
			$left->_arguments = $right->_arguments;
		}

		if ( ! empty($right->_shared))
		{
			$left->_shared = $right->_shared;
		}

		if ( ! empty($right->_methods))
		{
			$left->_methods = $right->_methods;
		}

		return $left;
	}

    /**
     * @deprecated
     * @param $property
     *
     * @return null
     */
    public function __get($property)
	{
		if (\property_exists($this, '_'.$property))
			return $this->{'_'.$property};
		else
			return NULL;
	}

    /**
     * @deprecated
     * @param $property
     *
     * @return bool
     */
	public function __isset($property)
	{
		return (bool) \property_exists($this, '_'.$property);
	}

	public function as_array()
	{
		$properties = array();
		foreach(\get_object_vars($this) as $key => $value)
		{
			$key = \ltrim($key, '_');
			$properties[$key] = $value;
		}

		return $properties;
	}

	protected function _handle_reference($argument)
	{
		if ( ! empty($argument) && \is_string($argument) && ($argument[0] === '%' || $argument[0] === '@'))
		{
			$argument = Dependency_Reference::factory($argument);
		}

		return $argument;
	}
}
