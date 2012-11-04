<?php

namespace Codeways\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CodewaysUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
