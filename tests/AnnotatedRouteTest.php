<?php
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

require_once __DIR__ . '/controllers/TestController.php';
require_once __DIR__ . '/controllers/SecureController.php';
require_once __DIR__ . '/controllers/BaseURIController.php';

class AnnotatedRouteTest extends TestCase
{
    protected function refreshApplication()
    {
        parent::refreshApplication();

        // add test controller to routing stack
        $router = App::make('router');
        $router->addController(new TestController());
        $router->addController(new SecureController());
        $router->addController(new BaseURIController());
        $router->addController(new BaseURIWithParamController());
    }

    public function testRouterReplacement()
    {
        $this->assertInstanceOf('Infomaniac\\Spore\\Illuminate\\Routing\\Router', App::make('router'));
    }

    /**
     * @expectedException       Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRouteNotFound()
    {
        $this->call('GET', '/non-existent');
    }

    public function testUnauthorizedRoute()
    {
        $response = $this->call('GET', '/unauthorized');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testSimpleRoute()
    {
        $response = $this->call('GET', '/hello');
        $this->assertEquals("world", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException       Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function testSimpleRouteWithIncorrectVerb()
    {
        $this->call('POST', '/hello');
    }

    public function testRouteWithNoDefinedVerbs()
    {
        // these will not throw an exception because any HTTP verb will be accepted
        $this->call('GET', '/any-verb');
        $this->call('POST', '/any-verb');

        // however, non-standard HTTP verbs will be rejected
        $exception = null;
        try {
            $this->call('CUSTOM', '/any-verb');
        } catch (MethodNotAllowedHttpException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
    }

    public function testRouteWithCustomVerb()
    {
        $this->call('MY-FANCY-VERB', '/custom-verb');
    }

    public function testRouteWithParams()
    {
        $response = $this->call('GET', '/hello/danny');
        $this->assertEquals('danny', $response->getContent());
    }

    /**
     * @expectedException           \Infomaniac\Spore\Exception\SecurityException
     * @expectedExceptionMessage    Route is only accessible via HTTPS
     */
    public function testSecureRouteWithInsecureCall()
    {
        $this->call('GET', '/secure/hello');
    }

    public function testSecureRouteWithSecureCall()
    {
        $this->assertNotNull($this->callSecure('GET', '/secure/hello'));
    }

    public function testBaseURI()
    {
        $response = $this->call('GET', '/base-uri/hello');
        $this->assertEquals('base world', $response->getContent());
    }

    public function testBaseURIWithParam()
    {
        $response = $this->call('GET', '/base-uri/basey/hello');
        $this->assertEquals('base basey world', $response->getContent());
    }

    public function testNamedRoute()
    {
        $this->call('GET', '/named-route');
        $this->assertEquals('named', Route::currentRouteName());
        $this->assertEquals($this->getRequest()->getUri(), URL::route(Route::currentRouteName()));
    }

    public function testGetViewRenderIfBrowser()
    {
        $response = $this->call('GET', '/view-browser/Laravel');
        $this->assertEquals('<h1>Laravel</h1>', $response->getContent());
    }

    public function testGetViewRenderAlways()
    {
        $response = $this->call('GET', '/view-always/Laravel');
        $this->assertEquals('<h1>Laravel</h1>', $response->getContent());
    }

    public function testGetViewRenderNever()
    {
        $response = $this->call('GET', '/view-never/Laravel');
        $this->assertEquals(json_encode(array(
            'page' => array(
                'title' => 'Laravel'
            ))),
            $response->getContent()
        );
    }

    private function getRequest()
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->getRequest();
    }
}