<?php namespace com\amazon\aws\lambda;

use Throwable;
use web\{Application, Environment, Error, InternalServerError, Logging, Request, Response, Routing};

/**
 * AWS Lambda with Amazon HTTP API Gateway
 *
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/services-apigateway.html
 */
abstract class HttpApi extends Handler {

  /**
   * Returns routes. Overwrite this in subclasses!
   * 
   * @param  web.Environment $environment
   * @return web.Application|web.Routing|[:var]
   */
  public abstract function routes($environment);

  /** @return com.amazon.aws.lambda.Lambda|callable */
  public function target() {
    $logging= Logging::of(function($request, $response, $error= null) {
      $query= $request->uri()->query();
      $this->environment->trace(sprintf(
        'TRACE [%s] %d %s %s %s',
        $request->value('context')->traceId,
        $response->status(),
        $request->method(),
        $request->uri()->path().($query ? '?'.$query : ''),
        $error ? $error->toString() : ''
      ));
    });

    // Determine routing
    $routing= Routing::cast($this->routes(new Environment(
      getenv('PROFILE') ?: 'prod',
      $this->environment->root,
      $this->environment->path('static'),
      [$this->environment->properties],
      [],
      $logging
    )));

    // Return event handler
    return function($event, $context) use($routing, $logging) {
      $request= new Request(new FromApiGateway($event));
      $response= new Response(new ResponseDocument());

      try {
        foreach ($routing->service($request->pass('context', $context), $response) ?? [] as $_) { }
        $logging->log($request, $response);
        return $response->output()->document;
      } catch (Throwable $t) {
        $e= $t instanceof Error ? $e : new InternalServerError($t);
        $logging->log($request, $response, $e);
        return $response->output()->error($e);
      }
    };
  }
}