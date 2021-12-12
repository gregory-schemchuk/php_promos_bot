<?php

spl_autoload_register(function ($class_name) {
    $file = 'C:\Development\__SCHOOL__\promocodes_bot_school\\' . $class_name . '.php';
    include $file;
}, false);
