<?php

namespace App\Component\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

abstract class AbstractController extends SymfonyAbstractController implements ControllerInterface
{
    use ControllerTrait;
}