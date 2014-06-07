<?php

namespace Utilities;

class AutoloaderStats
{
    public static $autoloaderCallCount;
}

spl_autoload_register(
    function ($className) {
        AutoloaderStats::$autoloaderCallCount++;
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        $fileName = dirname(__DIR__) . DIRECTORY_SEPARATOR . $fileName;
		
        if (!file_exists($fileName))
        {
            return false;
        }
        
        include_once $fileName;
    }
);
