<?php namespace com\amazon\aws\lambda;

use Throwable;
use web\{Application, Environment, Error, InternalServerError, Request, Response, Routing};

/**
 * AWS Lambda with Amazon HTTP API Gateway. Uses buffering as streamed responses
 * are not supported by API Gateway's LAMBDA_PROXY integration
 *
 * @test com.amazon.aws.lambda.unittest.ApiGatewayTest
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/services-apigateway.html
 */
abstract class ApiGateway extends HttpApi {

  /** @return callable|com.amazon.aws.lambda.Lambda|com.amazon.aws.lambda.Streaming */
  public function target() {
    $routing= $this->routing();

    // Return event handler
    return function($event, $context) use($routing) {
      $in= new FromApiGateway($event);
      $req= new Request($in);
      $res= new Response(new ResponseDocument());

      try {
        foreach ($routing->service($req->pass('context', $context)->pass('request', $in->context()), $res) ?? [] as $_) { }
        $this->tracing->log($req, $res);
        $res->end();

        return $res->output()->document;
      } catch (Throwable $t) {
        $e= $t instanceof Error ? $t : new InternalServerError($t);
        $this->tracing->log($req, $res, $e);
        return $res->output()->error($e);
      }
    };
  }
}