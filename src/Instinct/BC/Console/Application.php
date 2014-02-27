<?php

/*
 * (c) Alexandre Quercia <alquerci@email.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Instinct\BC\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Instinct\BC\Console\Command\ConvertCommand;

/**
 * @author Alexandre Quercia <alquerci@email.com>
 */
class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct();

        $this->add(new ConvertCommand());
    }
}
