<?php

namespace DomBase\DomAdminBundle\Controller;

use DomBase\DomAdminBundle\Configuration\ConfigManager;
use DomBase\DomAdminBundle\Form\Filter\FilterRegistry;
use DomBase\DomAdminBundle\Search\Autocomplete;
use DomBase\DomAdminBundle\Search\Exporter;
use DomBase\DomAdminBundle\Search\Paginator;
use DomBase\DomAdminBundle\Search\QueryBuilder;
use DomBase\DomAdminBundle\Search\Translator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller used to render all the default EasyAdmin actions.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DomAdminController extends AbstractController
{
    use AdminControllerTrait;

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            'domadmin.autocomplete' => Autocomplete::class,
            'domadmin.config.manager' => ConfigManager::class,
            'domadmin.paginator' => Paginator::class,
            'domadmin.query_builder' => QueryBuilder::class,
            'domadmin.property_accessor' => PropertyAccessorInterface::class,
            'domadmin.filter.registry' => FilterRegistry::class,
            'domadmin.export_service' => Exporter::class,
            'domadmin.translator' => Translator::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }

    /**
     * @Route("/login", name="dom_admin_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('homepage');
         }
//        dump($request);exit;
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@DomAdmin/page/login.html.twig', [
            // parameters usually defined in Symfony login forms
            'error' => $error,
            'last_username' => $lastUsername,

            // OPTIONAL parameters to customize the login form:

            // the string used to generate the CSRF token. If you don't define
            // this parameter, the login form won't include a CSRF token
            'csrf_token_intention' => 'authenticate',
            // the URL users are redirected to after the login (default: path('domadmin'))
            'target_path' => $this->generateUrl('domadmin'),
            // the label displayed for the username form field (the |trans filter is applied to it)
            'username_label' => 'Your username',
            // the label displayed for the password form field (the |trans filter is applied to it)
            'password_label' => 'Your password',
            // the label displayed for the Sign In form button (the |trans filter is applied to it)
            'sign_in_label' => 'Log in',
//            // the 'name' HTML attribute of the <input> used for the username field (default: '_username')
//            'username_parameter' => 'username',
//            // the 'name' HTML attribute of the <input> used for the password field (default: '_password')
//            'password_parameter' => 'password',
        ]);
    }

    /**
     * @Route("/logout", name="dom_admin_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

}
