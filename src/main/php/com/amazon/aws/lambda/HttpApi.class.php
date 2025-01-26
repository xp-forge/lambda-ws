<?php namespace com\amazon\aws\lambda;

use Throwable;
use web\{Error, InternalServerError, Request, Response};

/**
 * AWS Lambda with Amazon HTTP API Gateway. Uses buffering as streamed responses
 * are not supported by API Gateway's LAMBDA_PROXY integration
 *
 * @test com.amazon.aws.lambda.unittest.HttpApiTest
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/services-apigateway.html
 */
abstract class HttpApi extends HttpIntegration {

  /** @return callable|com.amazon.aws.lambda.Lambda|com.amazon.aws.lambda.Streaming */
  public function target() {
    $app= $this->application();

    // Return event handler
    return function($event, $context) use($app) {
      $in= new FromApiGateway($event);
      $req= new Request($in);
      $res= new Response(new ResponseDocument());

      try {
        foreach ($app->service($req->pass('context', $context)->pass('request', $in->context()), $res) ?? [] as $_) { }
        $this->tracing->exchange($req, $res, $res->trace + ['traceId' => $context->traceId]);
        $res->end();

        return $res->output()->document;
      } catch (Throwable $t) {
        $this->tracing->exchange($req, $res, $res->trace + ['traceId' => $context->traceId, 'error' => $t]);
        return $res->output()->error($t instanceof Error ? $t : new InternalServerError($t));
      }
    };
  }
}