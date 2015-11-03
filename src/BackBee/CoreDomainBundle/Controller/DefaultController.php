<?php

namespace BackBee\CoreDomainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BackBeeCoreDomainBundle:Default:index.html.twig', array('name' => $name));
    }
}
