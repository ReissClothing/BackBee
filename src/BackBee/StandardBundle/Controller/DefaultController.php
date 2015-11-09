<?php

namespace BackBee\StandardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BackBeeStandardBundle:Default:index.html.twig', array('name' => $name));
    }
}
