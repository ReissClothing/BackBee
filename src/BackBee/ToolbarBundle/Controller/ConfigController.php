<?php

namespace BackBee\ToolbarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConfigController extends Controller
{
    public function getAction()
    {
        return new JsonResponse($this->getParameter('back_bee_toolbar'));
    }
}
