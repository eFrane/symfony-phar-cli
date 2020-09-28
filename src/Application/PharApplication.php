<?php
/**
 * @copyright 2020
 * @author Stefan "eFrane" Graupner <stefan.graupner@gmail.com>
 */

namespace EFrane\PharTest\Application;


use EFrane\PharBuilder\Application\PharKernel;

class PharApplication extends \EFrane\PharBuilder\Application\PharApplication
{
    public function __construct(PharKernel $kernel)
    {
        parent::__construct($kernel);

        $this->setName('test');
    }
}
