<?php \defined('SYSPATH') or die('No direct script access.');

class Kohana_Dependency_Definition_List implements Iterator, Countable, ArrayAccess {

	public static function factory(): Dependency_Definition_List
    {
		return new Dependency_Definition_List;
	}

	protected array $_definitions = array();

	public function add($key, Dependency_Definition $definition): static
    {
		if ( ! \is_string($key)) {
		    throw Dependency_Exception::invalidDefinitionKey($key);
        }

		$this->_definitions[$key] = $definition;

		return $this;
	}

	public function get($key)
	{
		if ( ! \is_string($key)) {
		    throw Dependency_Exception::invalidLookupKey($key);
        }

		// Get all of the relevant definitions
		$relevant_definitions = array();
		$current_path = '';
		foreach (\explode('.', $key) as $sub_key)
		{
			$current_path = \trim($current_path.'.'.$sub_key, '.');
			if ($definition = Arr::path($this->_definitions, $current_path))
			{
				$relevant_definitions[] = $definition;
			}
		}

		if (empty($relevant_definitions)) {
		    throw Dependency_Exception::undefinedLookupKey($key);
        }

		// Merge the relevant definitions into a single definition that will be used to construct the object
		$definition = \array_shift($relevant_definitions);
		foreach ($relevant_definitions as $relevant_definition)
		{
			$definition = $definition->merge_with($relevant_definition);
		}

		return $definition;
	}

	public function from_array(array $array, $parent_key = ''): static
    {
		foreach ($array as $key => $sub_array)
		{
			$full_key = \trim($parent_key.'.'.$key, '.');

			if ( ! \is_array($sub_array)) {
			    throw Dependency_Exception::invalidDefinitionSubArray($full_key);
            }

			if ($settings = Arr::get($sub_array, '_settings'))
			{
				// Create the definition and add it to the list
				$definition = new Dependency_Definition;
				$this->add($full_key, $definition->from_array($settings));

				// Remove the settings from the array so we can look at the sub arrays only
				unset($sub_array['_settings']);
			}

			// Recursively call this method with the sub array (if not empty) to get more definitions
			if ( ! empty($sub_array))
			{
				$this->from_array($sub_array, $full_key);
			}
		}

		return $this;
	}

	public function as_array(): array
    {
		return $this->_definitions;
	}

	public function count(): int
	{
		return \count($this->_definitions);
	}

	public function current(): mixed
	{
		return \current($this->_definitions);
	}

	public function key(): mixed
	{
		return \key($this->_definitions);
	}

	public function next(): void
	{
		\next($this->_definitions);
	}

	public function rewind(): void
	{
		\reset($this->_definitions);
	}

	public function valid(): bool
	{
		return (\current($this->_definitions) !== FALSE);
	}

	public function offsetExists($offset): bool
	{
		return isset($this->_definitions[$offset]);
	}

	public function offsetGet($offset): mixed
	{
		return $this->get($offset);
	}

	public function offsetSet($offset, $value): void
	{
		$this->add($offset, $value);
	}

	public function offsetUnset($offset): void
	{
		unset($this->_definitions[$offset]);
	}

}
