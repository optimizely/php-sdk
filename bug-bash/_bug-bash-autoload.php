<?php

function bug_bash_autoloader($class): void
{
    $class_path = str_replace('\\', '/', $class);

    $file =  dirname(__DIR__) . '/src/' . $class_path . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
}

spl_autoload_register('bug_bash_autoloader');
