<?php namespace com\amazon\aws\lambda;

use Throwable;
use web\{Application, Environment, Error, InternalServerError, Logging, Request, Response, Routing};

/**
 * AWS Lambda with Amazon HTTP API Gateway
 *
 * @test com.amazon.aws.lambda.unittest.HttpApiTest
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
      $in= new FromApiGateway($event);
      $req= new Request($in);
      $res= new Response(new ResponseDocument());

      try {
        foreach ($routing->service($req->pass('context', $context)->pass('request', $in->context()), $res) ?? [] as $_) { }
        $logging->log($req, $res);
        $res->end();

        return $res->output()->document;
      } catch (Throwable $t) {
        $e= $t instanceof Error ? $t : new InternalServerError($t);
        $logging->log($req, $res, $e);
        return $res->output()->error($e);
      }
    };
  }
}