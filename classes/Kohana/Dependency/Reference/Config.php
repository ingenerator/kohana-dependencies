<?php \defined('SYSPATH') or die('No direct script access.');

class Kohana_Dependency_Reference_Config extends Dependency_Reference
{
    protected bool $is_required;

    public function __construct(string $key, bool $is_required = FALSE)
    {
        parent::__construct($key);
        $this->is_required = $is_required;
    }

    public function resolve(Dependency_Container $container)
    {
        $value = Kohana::$config->load($this->_key);
        if ($this->is_required and ($value === NULL)) {
            throw Dependency_Exception::missingConfig($this->_key);
        }
        return $value;
    }

}
