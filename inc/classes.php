<?php
/**
 * Include Classes
 */

$registeredClasses = [];
foreach (glob(CLASSES_PATH.'/*.php') as $classFile) {
    require_once $classFile;
    $allClasses = get_declared_classes();
    $lastClass  = array_pop($allClasses);
    $registeredClasses[] = $lastClass;
}

foreach ($registeredClasses as $class) {
    if (class_exists($class) && method_exists($class, 'init')) {
        $class::init();
    }
}
