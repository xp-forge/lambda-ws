<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Context, Environment, ApiGateway};
use io\streams\{MemoryOutputStream, StringWriter};
use lang\MethodNotImplementedException;
use test\{Assert, Before, Test, Values};
use web\{Application, Cookie, Error, Environment as WebEnvironment};

abstract class InvocationTest {
  protected $context, $environment, $trace;

  /**
   * Performs invocation of a given target
   *
   * @param  callable $target
   * @param  var $event
   * @return var
   */
  protected abstract function invocation($target, $event);

  /**
   * Transforms expected response
   *
   * @param  var $response
   * @return var
   */
  protected abstract function transform($response);

  /** Returns a new event */
  protected function invoke($target, $method= 'GET', $query= '', $headers= [], $body= null) {
    $event= [
      'version'        => '2.0',
      'routeKey'       => 'ANY /test',
      'rawPath'        => '/default/test',
      'rawQueryString' => $query,
      'cookies'        => ['name=Test'],
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

    // Reset trace to beginning
    $this->trace->truncate(0);
    $this->trace->seek(0);

    return $this->invocation($target, $event, $this->context);
  }

  #[Before]
  public function context() {
    $this->trace= new MemoryOutputStream();
    $this->environment= new Environment('test', new StringWriter($this->trace));
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
    $fixture= ['/' => function($req, $res) {
      $res->answer(200);
      $res->send('Hello '.$req->param('name'), 'text/plain');
    }];

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 10],
        'body'              => 'Hello Test',
      ],
      $this->transform($this->invoke($fixture, 'GET', 'name=Test'))
    );
  }

  #[Test]
  public function with_web_application() {
    $fixture= new class(new WebEnvironment('test')) extends Application {
      public function routes() {
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
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 10],
        'body'              => 'Hello Test',
      ],
      $this->transform($this->invoke($fixture, 'GET', 'name=Test'))
    );
  }

  #[Test]
  public function sending_redirect() {
    $fixture= ['/' => function($req, $res) {
      $res->answer(302);
      $res->header('Location', 'https://example.com');
    }];

    Assert::equals(
      [
        'statusCode'        => 302,
        'statusDescription' => 'Found',
        'isBase64Encoded'   => false,
        'headers'           => ['Location' => 'https://example.com', 'Content-Length' => 0],
      ],
      $this->transform($this->invoke($fixture, 'GET'))
    );
  }

  #[Test]
  public function sending_dispatch() {
    $fixture= [
      '/target' => function($req, $res) {
        $res->answer(200);
        $res->send('Hello '.$req->param('name'), 'text/plain');
      },
      '/' => function($req, $res) {
        return $req->dispatch('/target', ['name' => 'Test']);
      },
    ];

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 10],
        'body'              => 'Hello Test',
      ],
      $this->transform($this->invoke($fixture, 'GET'))
    );
  }

  #[Test]
  public function throwing_error() {
    $fixture= ['/' => function($req, $res) {
      throw new Error(404, 'Not Found');
    }];

    Assert::equals(
      [
        'statusCode'        => 404,
        'statusDescription' => 'Not Found',
        'isBase64Encoded'   => false,
        'headers'           => [
          'Content-Type'     => 'text/plain',
          'Content-Length'   => 32,
          'x-amzn-ErrorType' => 'web.Error'
        ],
        'body'              => 'Error web.Error(#404: Not Found)',
      ],
      $this->transform($this->invoke($fixture, 'GET'))
    );
  }

  #[Test]
  public function throwing_exception() {
    $fixture= ['/' => function($req, $res) {
      throw new MethodNotImplementedException('Not implemented', '/');
    }];

    Assert::equals(
      [
        'statusCode'        => 500,
        'statusDescription' => 'Not implemented',
        'isBase64Encoded'   => false,
        'headers'           => [
          'Content-Type'     => 'text/plain',
          'Content-Length'   => 52,
          'x-amzn-ErrorType' => 'web.InternalServerError'
        ],
        'body'              => 'Error web.InternalServerError(#500: Not implemented)',
      ],
      $this->transform($this->invoke($fixture, 'GET'))
    );
  }

  #[Test]
  public function has_access_to_request() {
    $fixture= ['/' => function($req, $res) {
      $res->answer(200);
      $res->send('Hello '.$req->param('name').' from '.$req->value('request')->apiId, 'text/plain');
    }];

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 26],
        'body'              => 'Hello Test from r3pmxmplak',
      ],
      $this->transform($this->invoke($fixture, 'GET', 'name=Test'))
    );
  }

  #[Test]
  public function has_access_to_context() {
    $fixture= ['/' => function($req, $res) {
      $res->answer(200);
      $res->send('Hello '.$req->param('name').' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
    }];

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 65],
        'body'              => 'Hello Test from arn:aws:lambda:us-east-1:1185465369:function:test',
      ],
      $this->transform($this->invoke($fixture, 'GET', 'name=Test'))
    );
  }

  #[Test]
  public function reads_cookies() {
    $fixture= ['/' => function($req, $res) {
      $name= $req->cookie('name');

      $res->answer(200);
      $res->send('Hello '.$name.' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
    }];

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 65],
        'body'              => 'Hello Test from arn:aws:lambda:us-east-1:1185465369:function:test',
      ],
      $this->transform($this->invoke($fixture, 'GET', 'name=Test'))
    );
  }

  #[Test]
  public function sets_cookies() {
    $fixture= ['/' => function($req, $res) {
      $name= $req->param('name');

      $res->answer(200);
      $res->cookie(new Cookie('name', $name));
      $res->send('Hello '.$name.' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
    }];

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 65],
        'cookies'           => ['name=Test; SameSite=Lax; HttpOnly'],
        'body'              => 'Hello Test from arn:aws:lambda:us-east-1:1185465369:function:test',
      ],
      $this->transform($this->invoke($fixture, 'GET', 'name=Test'))
    );
  }

  #[Test]
  public function writes_trace_message() {
    $fixture= ['/' => function($req, $res) {
      $res->answer(200);
      $res->send('Hello '.$req->param('name').' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
    }];
    $this->invoke($fixture, 'GET', 'name=Test');

    Assert::equals(
      "TRACE [Root=1-dc99d00f-c079a84d433534434534ef0d;Parent=91ed514f1e5c03b2;Sampled=1] 200 GET /default/test?name=Test \n",
      $this->trace->bytes()
    );
  }

  #[Test]
  public function calls_web_application_initializers() {
    $fixture= new class(new WebEnvironment('test')) extends Application {
      private $initialized= 0;

      public function initialize() {
        $this->initialized++;
      }

      public function routes() {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send("Initialized {$this->initialized}", 'text/plain');
        }];
      }
    };

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 13],
        'body'              => 'Initialized 1',
      ],
      $this->transform($this->invoke($fixture, 'GET', 'name=Test'))
    );
  }
}