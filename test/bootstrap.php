<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2017 inGenerator Ltd
 */

$_SERVER['KOHANA_ENV'] = 'DEVELOPMENT';
require_once(__DIR__.'/../koharness_bootstrap.php');

// Hacky workaround to show a simple text exception on fatal errors
// Otherwise Kohana's shutdown function catches it and shows a huge HTML trace that's horrible to follow
\file_put_contents(
    APPPATH.'/views/text-error.php',
    '<?php echo "\n\nUnhandled error: ".\Kohana_Exception::text($e)."\n";'
);
Kohana_Exception::$error_view = 'text-error';

// Require fake session implementation from Koharness to avoid errors in unit tests
require_once __DIR__.'/../vendor/kohana/koharness/helper_classes/Session/Fake.php';
\Session::$default           = 'fake';
\Session::$instances['fake'] = new Session_Fake;

// Autoload mocks and test-support helpers that should not autoload in the main app
$mock_loader = new \Composer\Autoload\ClassLoader;
$mock_loader->addPsr4('test\\mock\\Ingenerator\\KohanaDependencies\\', [__DIR__.'/mock/']);
$mock_loader->addPsr4('test\\unit\\Ingenerator\\KohanaDependencies\\', [__DIR__.'/unit/']);
$mock_loader->register();
