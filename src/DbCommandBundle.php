<?php

namespace Syjust\SfDbCmd;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Class DbCommandBundle
 *
 * @package Syjust\SfDbCmd
 */
class DbCommandBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
