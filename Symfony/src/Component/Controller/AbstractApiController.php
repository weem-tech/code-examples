<?php

namespace App\Component\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

abstract class AbstractApiController extends SymfonyAbstractController implements ApiControllerInterface
{
    use ControllerTrait;
}