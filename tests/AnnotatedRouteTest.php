<?php
require_once __DIR__.'/controllers/TestController.php';

use Illuminate\Support\Facades\App;

class AnnotatedRouteTest extends TestCase
{
    protected function refreshApplication()
    {
        parent::refreshApplication();

        // add test controller to routing stack
        $router = App::make('router');
        $router->addController(new TestController());
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

    public function testCallSimpleRoute()
    {
        $response = $this->call('GET', '/hello');
        $this->assertEquals("world", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException       Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function testCallSimpleRouteWithIncorrectVerb()
    {
        $this->call('POST', '/hello');
    }

}