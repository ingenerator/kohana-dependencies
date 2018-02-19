<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Dependency_Exception extends Kohana_Exception
{
    public static function dependencyInstantiationException($class, ReflectionException $e)
    {
        return new static(
            'Could not create a :class [:message]',
            [':class' => $class, ':message' => $e->getMessage()],
            0,
            $e
        );
    }

    public static function emptyFilePath()
    {
        return new static('Could not construct the dependency definition. An invalid path was provided.');
    }

    public static function invalidDefinitionKey($key)
    {
        return new static(
            'A dependency definition must be identified with a string key - got :type', [
                ':type' => is_object($key) ? get_class($key) : gettype($key),
            ]
        );
    }

    public static function invalidDefinitionSubArray($full_key)
    {
        return new static(
            'Could not load dependency definitions : the value of `:full_key` is not an array',
            [':full_key' => $full_key]
        );
    }

    public static function invalidLookupKey($key)
    {
        return new static(
            'An invalid dependency key was provided : expected string, got :type',
            [
                ':type' => is_object($key) ? get_class($key) : gettype($key),
            ]
        );
    }

    public static function undefinedLookupKey($key)
    {
        return new static(
            'There is no dependency defined with the key `:key`',
            [':key' => $key]
        );
    }
}
