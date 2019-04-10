<?php
namespace Simplex;

use Composer\Installer\PackageEvent;

class Installer
{
    public static function postPackageInstall(PackageEvent $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        echo "INSTALLED";
    }
}
