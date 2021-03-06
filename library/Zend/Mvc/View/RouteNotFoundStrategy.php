<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Mvc
 * @subpackage View
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Mvc\View;

use Zend\EventManager\EventCollection,
    Zend\EventManager\ListenerAggregate,
    Zend\Http\Response as HttpResponse,
    Zend\Mvc\Application,
    Zend\Mvc\MvcEvent,
    Zend\Stdlib\ResponseDescription as Response,
    Zend\View\Model as ViewModel;

/**
 * @category   Zend
 * @package    Zend_Mvc
 * @subpackage View
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class RouteNotFoundStrategy implements ListenerAggregate
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Template to use to report page not found conditions
     * 
     * @var string
     */
    protected $notFoundTemplate = 'error';

    /**
     * Attach the aggregate to the specified event manager
     * 
     * @param  EventCollection $events 
     * @return void
     */
    public function attach(EventCollection $events)
    {
        $this->listeners[] = $events->attach('dispatch', array($this, 'prepareNotFoundViewModel'), -90);
        $this->listeners[] = $events->attach('dispatch.error', array($this, 'detectNotFoundError'));
        $this->listeners[] = $events->attach('dispatch.error', array($this, 'prepareNotFoundViewModel'));
    }

    /**
     * Detach aggregate listeners from the specified event manager
     * 
     * @param  EventCollection $events 
     * @return void
     */
    public function detach(EventCollection $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Get template for not found conditions
     *
     * @param  string $notFoundTemplate
     * @return RouteNotFoundStrategy
     */
    public function setNotFoundTemplate($notFoundTemplate)
    {
        $this->notFoundTemplate = (string) $notFoundTemplate;
        return $this;
    }
    
    /**
     * Get template for not found conditions
     *
     * @return string
     */
    public function getNotFoundTemplate()
    {
        return $this->notFoundTemplate;
    }

    /**
     * Detect if an error is a 404 condition
     *
     * If a "controller not found" or "invalid controller" error type is
     * encountered, sets the response status code to 404.
     * 
     * @param  MvcEvent $e 
     * @return void
     */
    public function detectNotFoundError(MvcEvent $e)
    {
        $error = $e->getError();
        if (empty($error)) {
            return;
        }

        switch ($error) {
            case Application::ERROR_CONTROLLER_NOT_FOUND:
            case Application::ERROR_CONTROLLER_INVALID:
                $response = $e->getResponse();
                if (!$response) {
                    $response = new HttpResponse();
                    $e->setResponse($response);
                }
                $response->setStatusCode(404);
                break;
            default:
                return;
        }
    }

    /**
     * Create and return a 404 view model
     * 
     * @param  MvcEvent $e 
     * @return void
     */
    public function prepareNotFoundViewModel(MvcEvent $e)
    {
        $vars = $e->getResult();
        if ($vars instanceof Response) {
            // Already have a response as the result
            return;
        }

        $response = $e->getResponse();
        if ($response->getStatusCode() != 404) {
            // Only handle 404 responses
            return;
        }

        $model = new ViewModel\ViewModel();
        $model->setVariable('message', 'Page not found.');
        $model->setTemplate($this->getNotFoundTemplate());
        $e->setResult($model);
    }
}
