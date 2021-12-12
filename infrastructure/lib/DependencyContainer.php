<?php

namespace infrastructure\lib;

include('vendor/autoload.php');

use Telegram\Bot\Api;


class DependencyContainer {

    private Api $telegram;

    private static ?DependencyContainer $dependencyContainer = null;


    private function __construct() {
        $this->telegram = new Api('1804256700:AAGYF4yRsRKKdP0B0O8UBBTDm62KeRKFOAo');
    }

    public static function getInstance(): DependencyContainer {
        if (self::$dependencyContainer === null) {
            self::$dependencyContainer = new DependencyContainer();
        }
        return self::$dependencyContainer;
    }

    public function getTelegram(): Api {
        return $this->telegram;
    }

}