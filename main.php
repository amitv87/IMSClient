<?php

namespace imsclient {
    switch (php_uname('s')) {
        case 'Linux':
            break;
        default:
            echo "Unsupport OS platform";
            die();
            break;
    }

    if (php_sapi_name() !== "cli") {
        echo "CLI exec mode required." . PHP_EOL;
        die();
    }

    // TODO: extension requirement check.

    if (posix_getuid() !== 0) {
        echo "root access required." . PHP_EOL;
        echo "maybe false-positive when no posix extension" . PHP_EOL;
        die();
    }

    pcntl_async_signals(true);
    gc_enable();
    if (!defined('SO_MARK'))
        define('SO_MARK', 36);
    if (!defined('IPPROTO_UDP'))
        define('IPPROTO_UDP', 17);
    if (!defined('UDP_ENCAP'))
        define('UDP_ENCAP', 100);
    if (!defined('UDP_ENCAP_ESPINUDP'))
        define('UDP_ENCAP_ESPINUDP', 2);
    require_once __DIR__ . DIRECTORY_SEPARATOR . "imsclient" . DIRECTORY_SEPARATOR . "ClassLoader.php";
    $loader = new ClassLoader();
    $loader->addpath(__DIR__);
    $loader->register();
    (new IMSClient())->run();
}
