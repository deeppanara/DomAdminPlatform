<?php

namespace EasyCorp\Bundle\DomAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * The controller used to render all the default EasyAdmin actions.
 *
 * @deprecated since 2.0, use {@see EasyAdminController} instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class AdminController extends Controller
{
    use AdminControllerTrait;
}
