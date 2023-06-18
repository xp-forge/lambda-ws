<?php namespace com\amazon\aws\lambda;

use Throwable;
use web\{Application, Environment, Error, InternalServerError, Request, Response, Routing};

/**
 * AWS Lambda with AWS function URLs. Uses streaming as this has lower
 * TTFB and memory consumption.
 *
 * @test com.amazon.aws.lambda.unittest.HttpApiTest
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/configuration-response-streaming.html
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-features.html#gettingstarted-features-urls
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
    $logging= new Tracing($this->environment);
    $routing= Routing::cast($this->routes(new Environment(
      getenv('PROFILE') ?: 'prod',
      $this->environment->root,
      $this->environment->path('static'),
      [$this->environment->properties],
      [],
      $logging
    )));

    // Return event handler
    return function($event, $stream, $context) use($routing, $logging) {
      $in= new FromApiGateway($event);
      $req= new Request($in);
      $res= new Response(new StreamingTo($stream));

      try {
        foreach ($routing->service($req->pass('context', $context)->pass('request', $in->context()), $res) ?? [] as $_) { }
        $logging->log($req, $res);

        $res->end();
      } catch (Throwable $t) {
        $e= $t instanceof Error ? $t : new InternalServerError($t);
        $logging->log($req, $res, $e);

        $res->answer($e->status(), $e->getMessage());
        $res->header('x-amzn-ErrorType', nameof($e));
        $res->send($e->compoundMessage(), 'text/plain');
      }
    };
  }
}