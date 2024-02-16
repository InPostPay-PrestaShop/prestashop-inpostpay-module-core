<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

if (class_exists(BaseController::class)) {
    abstract class AbstractController extends BaseController
    {
    }
} else {
    abstract class AbstractController extends Controller
    {
    }
}
