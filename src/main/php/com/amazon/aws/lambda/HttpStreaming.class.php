<?php namespace com\amazon\aws\lambda;

use Throwable;
use web\{Error, InternalServerError, Request, Response};

/**
 * AWS Lambda with AWS function URLs. Uses streaming as this has lower
 * TTFB and memory consumption.
 *
 * @test com.amazon.aws.lambda.unittest.HttpIntegrationTest
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-features.html#gettingstarted-features-urls
 */
abstract class HttpStreaming extends HttpIntegration {

  /** @return callable|com.amazon.aws.lambda.Lambda|com.amazon.aws.lambda.Streaming */
  public function target() {
    $app= $this->application();

    // Return event handler
    return function($event, $stream, $context) use($app) {
      $in= new FromApiGateway($event);
      $req= new Request($in);
      $res= new Response(new StreamingTo($stream));

      try {
        foreach ($app->service($req->pass('context', $context)->pass('request', $in->context()), $res) ?? [] as $_) { }
        $this->tracing->exchange($req, $res, $res->trace + ['traceId' => $context->traceId]);

        $res->end();
      } catch (Throwable $t) {
        $this->tracing->exchange($req, $res, $res->trace + ['traceId' => $context->traceId, 'error' => $t]);

        $e= $t instanceof Error ? $t : new InternalServerError($t);
        $res->answer($e->status(), $e->getMessage());
        $res->header('x-amzn-ErrorType', nameof($e));
        $res->send($e->compoundMessage(), 'text/plain');
      }
    };
  }
}