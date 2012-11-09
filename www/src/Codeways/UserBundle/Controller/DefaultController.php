<?php

namespace Codeways\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

class DefaultController extends Controller
{
    public function indexAction()
    {

        $request = new Request();
        $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        return $this->render('CodewaysUserBundle:Default:index.html.twig',
            array('error' => $error));
    }
}
