<?php

namespace App\Component\Controller;

use App\Entity\User\User;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

trait ControllerTrait
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $template = '';

    /**
     * @var array
     */
    protected $variables = [];

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'request_stack' => '?' . RequestStack::class,
            'security.csrf.token_manager' => '?' . CsrfTokenManagerInterface::class,
            'swiftmailer.mailer' => '?' . Swift_Mailer::class,
        ]);
    }

    /*
     * Inits the controller.
     * Called before evey action of the controller.
     */

    /**
     * checks if a domain name is valid
     * @param string $domain_name
     * @return bool
     */
    public static function checkDomain($domain_name)
    {
        //FILTER_VALIDATE_URL checks length but..why not? so we dont move forward with more expensive operations
        $domain_len = strlen($domain_name);
        if ($domain_len < 3 or $domain_len > 253)
            return FALSE;
        //getting rid of HTTP/S just in case was passed.
        if (stripos($domain_name, 'http://') === 0)
            $domain_name = substr($domain_name, 7);
        elseif (stripos($domain_name, 'https://') === 0)
            $domain_name = substr($domain_name, 8);

        //we dont need the www either
        if (stripos($domain_name, 'www.') === 0)
            $domain_name = substr($domain_name, 4);
        //Checking for a '.' at least, not in the beginning nor end, since http://.abcd. is reported valid
        if (strpos($domain_name, '.') === FALSE or $domain_name[strlen($domain_name) - 1] == '.' or $domain_name[0] == '.')
            return FALSE;

        //now we use the FILTER_VALIDATE_URL, concatenating http so we can use it, and return BOOL
        return (filter_var('http://' . $domain_name, FILTER_VALIDATE_URL) === FALSE) ? FALSE : TRUE;
    }

    public function preDispatch()
    {
        $this->request = $this->get('request_stack')->getCurrentRequest();

        $this->assign([
            'user' => $this->getUser()
        ]);
    }

    /**
     * Assigns a template variable
     *
     * @param array|string $name the template variable name(s)
     * @param mixed $value the value to assign
     * @return array
     */
    public function assign($name, $value = null)
    {
        $variables = is_array($name) ? $name : [$name => $value];
        $this->addVariables($variables);

        return $this->variables;
    }

    /**
     * @param array $variables
     * @return array
     */
    protected function addVariables($variables = [])
    {
        foreach ($variables as $name => $value) {
            if ($name) {
                $this->variables[$name] = $value;
            }
        }

        return $this->variables;
    }

    /**
     * @param string $template
     * @param array $parameters
     * @param Response|null $response
     * @return Response
     */
    public function render(string $template = '', array $parameters = array(), Response $response = null): Response
    {
        $parameters = array_merge($this->getAssign(), $parameters);
        $template = $template ?: $this->getTemplate();
        return parent::render($template, $parameters, $response);
    }

    /**
     * @param null $name
     *
     * @return array|mixed
     */
    public function getAssign($name = null)
    {
        return !is_null($name) ? $this->variables[$name] : $this->variables;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Sets the template for rendering.
     *
     * @param string $template
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    public function existURL($url)
    {
        $headers = @get_headers('https://' . $url);

        if (!$headers) {
            if (stripos($url, 'www.') === 0)
                $url = substr($url, 4);
            else
                $url = 'www.' . $url;

            $headers = @get_headers('https://' . $url);
        }

        if (!$headers) {
            return false;
        }

        if (strpos($headers[0], '200')) {
            return true;
        }

        foreach ($headers as $header) {
            if (substr($header, 0, 10) == "Location: ") {
                $target = substr($header, 10);

                if (strpos($target, $url)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    protected function getProjectDir()
    {
        return $this->getParameter('kernel.project_dir');
    }

    /**
     * @return mixed
     */
    protected function getLogsDir()
    {
        return $this->getParameter('kernel.logs_dir');
    }

    /**
     * @return mixed
     */
    protected function getEnv()
    {
        return $this->getParameter('kernel.environment');
    }

    /**
     * @param User $user
     */
    protected function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'frontend', $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        $this->container->get('session')->set('_security_frontend', serialize($token));
    }
}