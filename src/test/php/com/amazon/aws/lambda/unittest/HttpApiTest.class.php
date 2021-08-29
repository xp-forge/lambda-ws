<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{HttpApi, Environment, Context};
use lang\MethodNotImplementedException;
use unittest\{Assert, Before, Test, Values};
use web\{Application, Error};

class HttpApiTest {
  private $context;

  /** Returns a new event */
  private function invoke($target, $method= 'GET', $query= '', $headers= [], $body= null) {
    $event= [
      'version'        => '2.0',
      'routeKey'       => 'ANY /test',
      'rawPath'        => '/default/test',
      'rawQueryString' => $query,
      'cookies'        => [],
      'headers'        => $headers,
      'requestContext' => [
        'accountId'    => '123456789012',
        'apiId'        => 'r3pmxmplak',
        'domainName'   => 'r3pmxmplak.execute-api.us-east-2.amazonaws.com',
        'domainPrefix' => 'r3pmxmplak',
        'http'         => [ 
          'method'    => $method,
          'path'      => '/default/test',
          'protocol'  => 'HTTP/1.1',
          'sourceIp'  => '127.0.0.1',
          'userAgent' => 'XP/Test'
        ],
        'requestId'    => 'JKJaXmPLvHcESHA=',
        'routeKey'     => 'ANY /test',
        'stage'        => 'default',
        'time'         => '10/Mar/2020:05:16:23 +0000',
        'timeEpoch'    => 1583817383220
      ],
      'isBase64Encoded' => false,
      'body'            => $body
    ];
    return $target($event, $this->context);
  }

  #[Before]
  public function context() {
    $this->context= new Context([
      'Lambda-Runtime-Aws-Request-Id'       => ['3e1afeb0-cde4-1d0e-c3c0-66b15046bb88'],
      'Lambda-Runtime-Invoked-Function-Arn' => ['arn:aws:lambda:us-east-1:1185465369:function:test'],
      'Lambda-Runtime-Trace-Id'             => ['Root=1-dc99d00f-c079a84d433534434534ef0d;Parent=91ed514f1e5c03b2;Sampled=1'],
      'Lambda-Runtime-Client-Context'       => null,
      'Lambda-Runtime-Cognito-Identity'     => null,
      'Lambda-Runtime-Deadline-Ms'          => ['1629390182479'],
    ]);
  }

  #[Test]
  public function with_handler_function() {
    $fixture= new class(new Environment('test')) extends HttpApi {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send('Hello '.$req->param('name'), 'text/plain');
        }];
      }
    };

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'multiValueHeaders' => ['Content-Type' => ['text/plain'], 'Content-Length' => ['10']],
        'body'              => 'Hello Test',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')
    );
  }

  #[Test]
  public function with_web_application() {
    $fixture= new class(new Environment('test')) extends HttpApi {
      public function routes($env) {
        return new class($env) extends Application {
          public function routes() {
            return ['/' => function($req, $res) {
              $res->answer(200);
              $res->send('Hello '.$req->param('name'), 'text/plain');
            }];
          }
        };
      }
    };

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'multiValueHeaders' => ['Content-Type' => ['text/plain'], 'Content-Length' => ['10']],
        'body'              => 'Hello Test',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')
    );
  }

  #[Test]
  public function throwing_error() {
    $fixture= new class(new Environment('test')) extends HttpApi {
      public function routes($env) {
        return ['/' => function($req, $res) {
          throw new Error(404, 'Not Found');
        }];
      }
    };

    Assert::equals(
      [
        'statusCode'        => 404,
        'statusDescription' => 'Not Found',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'x-amzn-ErrorType' => 'web.Error'],
        'body'              => 'Error web.Error(#404: Not Found)',
      ],
      $this->invoke($fixture->target(), 'GET')
    );
  }

  #[Test]
  public function throwing_exception() {
    $fixture= new class(new Environment('test')) extends HttpApi {
      public function routes($env) {
        return ['/' => function($req, $res) {
          throw new MethodNotImplementedException('Not implemented', '/');
        }];
      }
    };

    Assert::equals(
      [
        'statusCode'        => 500,
        'statusDescription' => 'Not implemented',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'x-amzn-ErrorType' => 'web.InternalServerError'],
        'body'              => 'Error web.InternalServerError(#500: Not implemented)',
      ],
      $this->invoke($fixture->target(), 'GET')
    );
  }
}