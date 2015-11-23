<?php

namespace BackBee\StandardBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BackBeeStandardBundle extends Bundle
{
    public function getParent()
    {
        return 'BackBeeWebBundle';
    }
}
