<?php namespace com\amazon\aws\lambda;

use Throwable;
use web\{Application, Environment, Error, InternalServerError, Request, Response, Routing};

/**
 * AWS Lambda with AWS function URLs. Uses streaming as this has lower
 * TTFB and memory consumption.
 *
 * @test com.amazon.aws.lambda.unittest.HttpIntegrationTest
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-features.html#gettingstarted-features-urls
 */
abstract class HttpIntegration extends HttpApi {

  /** @return com.amazon.aws.lambda.Lambda|callable */
  public function target() {
    $routing= $this->routing();

    // Return event handler
    return function($event, $stream, $context) use($routing) {
      $in= new FromApiGateway($event);
      $req= new Request($in);
      $res= new Response(new StreamingTo($stream));

      try {
        foreach ($routing->service($req->pass('context', $context)->pass('request', $in->context()), $res) ?? [] as $_) { }
        $this->tracing->log($req, $res);

        $res->end();
      } catch (Throwable $t) {
        $e= $t instanceof Error ? $t : new InternalServerError($t);
        $this->tracing->log($req, $res, $e);

        $res->answer($e->status(), $e->getMessage());
        $res->header('x-amzn-ErrorType', nameof($e));
        $res->send($e->compoundMessage(), 'text/plain');
      }
    };
  }
}