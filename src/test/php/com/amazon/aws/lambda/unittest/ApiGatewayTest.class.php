<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\ApiGateway;

class ApiGatewayTest extends InvocationTest {

  /**
   * Performs invocation of a given target
   *
   * @param  web.Application|[:function(web.Request, web.Response)] $routes
   * @param  var $event
   * @return var
   */
  protected function invocation($routes, $event) {
    $integration= new class($this->environment, $routes) extends ApiGateway {
      private $routes;

      public function __construct($environment, $routes) {
        parent::__construct($environment);
        $this->routes= $routes;
      }

      public function routes($env) { return $this->routes; }
    };

    $target= $integration->target();
    return $target($event, $this->context);
  }

  /**
   * Transforms response to abstract format
   *
   * @param  var $response
   * @return var
   */
  protected function transform($response) {
    return $response;
  }
}