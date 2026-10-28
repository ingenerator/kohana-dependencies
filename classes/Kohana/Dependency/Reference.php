<?php \defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_Dependency_Reference {

	const KEY_FORMAT = '[0-9a-zA-Z_-]+(\.[0-9a-zA-Z_-]+)*';

	public static function factory($argument)
	{
		if ( ! \is_string($argument) || \strlen($argument) < 3)
		{
			return $argument;
		}

		if ($argument[0] === '%' && $argument[-1] === '%')
		{
			if (\preg_match('/^\%'.self::KEY_FORMAT.'\%$/D', $argument))
			{
				$argument = new Dependency_Reference_Container(\substr($argument, 1, -1));
			}
		}
		elseif ($argument[0] === '@' && $argument[-1] === '@')
		{
			if (\str_starts_with($argument, '@!') && \str_ends_with($argument, '!@'))
			{
				if (\preg_match('/^\@!'.self::KEY_FORMAT.'\!@$/D', $argument))
				{
					$argument = new Dependency_Reference_Config(\substr($argument, 2, -2), TRUE);
				}
			}
			elseif (\preg_match('/^\@'.self::KEY_FORMAT.'\@$/D', $argument))
			{
				$argument = new Dependency_Reference_Config(\substr($argument, 1, -1), FALSE);
			}
		}

		return $argument;
	}

	protected $_key;

	public function __construct($key)
	{
		$this->_key = $key;
	}

	abstract public function resolve(Dependency_Container $container);

}
