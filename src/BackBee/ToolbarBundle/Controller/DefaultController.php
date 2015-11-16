<?php

namespace BackBee\ToolbarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BackBeeToolbarBundle:Default:index.html.twig', array('name' => $name));
    }
}
