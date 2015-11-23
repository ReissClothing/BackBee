<?php

namespace BackBee\WebBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BackBeeWebBundle extends Bundle
{
    public function getParent()
    {
        return 'BackBeeCoreDomainBundle';
    }
}
