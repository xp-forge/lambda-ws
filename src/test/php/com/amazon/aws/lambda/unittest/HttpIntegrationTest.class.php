<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Context, Environment, HttpIntegration, Stream, StreamingTo};
use io\streams\{MemoryOutputStream, StringWriter};
use lang\MethodNotImplementedException;
use test\{Assert, Before, Test, Values};
use web\{Application, Cookie, Error};

class HttpIntegrationTest {
  private $context, $environment, $stream, $trace;

  /** Returns a new event */
  private function invoke($target, $method= 'GET', $query= '', $headers= [], $body= null) {
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

    // Stream calls to testing stream, then return this stream
    $stream= new class() implements Stream {
      public $mime= null;
      private $bytes= '';

      public function unmarshal($full= false) {
        $p= strpos($this->bytes, StreamingTo::DELIMITER);
        $meta= json_decode(substr($this->bytes, 0, $p), true);
        if (!$full) unset($meta['body']);

        return [
          'meta' => $meta,
          'body' => substr($this->bytes, $p + strlen(StreamingTo::DELIMITER))
        ];
      }

      public function transmit($source, $mimeType= null) { /* Untested */ }

      public function use($mimeType) { $this->mime.= $mimeType; }

      public function write($bytes) { $this->bytes.= $bytes; }

      public function end() { /* NOOP */ }

      public function flush() { /* NOOP */ }

      public function close() { /* NOOP */ }
    };
    $target($event, $stream, $this->context);
    return $stream;
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
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send('Hello '.$req->param('name'), 'text/plain');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 200,
          'statusDescription' => 'OK',
          'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 10],
        ],
        'body' => 'Hello Test',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')->unmarshal()
    );
  }

  #[Test]
  public function with_web_application() {
    $fixture= new class($this->environment) extends HttpIntegration {
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
        'meta' => [
          'statusCode'        => 200,
          'statusDescription' => 'OK',
          'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 10],
        ],
        'body' => 'Hello Test',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')->unmarshal()
    );
  }

  #[Test]
  public function sending_redirect() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(302);
          $res->header('Location', 'https://example.com');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 302,
          'statusDescription' => 'Found',
          'headers'           => ['Location' => 'https://example.com', 'Content-Length' => 0],
        ],
        'body' => '',
      ],
      $this->invoke($fixture->target(), 'GET')->unmarshal()
    );
  }

  #[Test]
  public function throwing_error() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          throw new Error(404, 'Not Found');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 404,
          'statusDescription' => 'Not Found',
          'headers'           => [
            'Content-Type'     => 'text/plain',
            'Content-Length'   => 32,
            'x-amzn-ErrorType' => 'web.Error'
          ],
        ],
        'body'              => 'Error web.Error(#404: Not Found)',
      ],
      $this->invoke($fixture->target(), 'GET')->unmarshal()
    );
  }

  #[Test]
  public function throwing_exception() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          throw new MethodNotImplementedException('Not implemented', '/');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 500,
          'statusDescription' => 'Not implemented',
          'headers'           => [
            'Content-Type'     => 'text/plain',
            'Content-Length'   => 52,
            'x-amzn-ErrorType' => 'web.InternalServerError'
          ],
        ],
        'body'              => 'Error web.InternalServerError(#500: Not implemented)',
      ],
      $this->invoke($fixture->target(), 'GET')->unmarshal()
    );
  }

  #[Test]
  public function has_access_to_request() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send('Hello '.$req->param('name').' from '.$req->value('request')->apiId, 'text/plain');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 200,
          'statusDescription' => 'OK',
          'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 26],
        ],
        'body' => 'Hello Test from r3pmxmplak',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')->unmarshal()
    );
  }

  #[Test]
  public function has_access_to_context() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send('Hello '.$req->param('name').' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 200,
          'statusDescription' => 'OK',
          'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 65],
        ],
        'body'              => 'Hello Test from arn:aws:lambda:us-east-1:1185465369:function:test',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')->unmarshal()
    );
  }

  #[Test]
  public function reads_cookies() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $name= $req->cookie('name');

          $res->answer(200);
          $res->send('Hello '.$name.' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 200,
          'statusDescription' => 'OK',
          'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 65],
        ],
        'body'              => 'Hello Test from arn:aws:lambda:us-east-1:1185465369:function:test',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')->unmarshal()
    );
  }

  #[Test]
  public function sets_cookies() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $name= $req->param('name');

          $res->answer(200);
          $res->cookie(new Cookie('name', $name));
          $res->send('Hello '.$name.' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
        }];
      }
    };

    Assert::equals(
      [
        'meta' => [
          'statusCode'        => 200,
          'statusDescription' => 'OK',
          'headers'           => ['Content-Type' => 'text/plain', 'Content-Length' => 65],
          'cookies'           => ['name=Test; SameSite=Lax; HttpOnly'],
        ],
        'body'              => 'Hello Test from arn:aws:lambda:us-east-1:1185465369:function:test',
      ],
      $this->invoke($fixture->target(), 'GET', 'name=Test')->unmarshal()
    );
  }

  #[Test]
  public function sets_awslambda_http_integration_response_vendor_mimetype() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send('Hello '.$req->param('name'), 'text/plain');
        }];
      }
    };

    Assert::equals(
      StreamingTo::MIME_TYPE,
      $this->invoke($fixture->target(), 'GET', 'name=Test')->mime
    );
  }

  #[Test]
  public function adds_must_use_streaming_hint() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send('Hello '.$req->param('name'), 'text/plain');
        }];
      }
    };

    Assert::equals(
      StreamingTo::USESTREAM,
      $this->invoke($fixture->target(), 'GET', 'name=Test')->unmarshal(true)['meta']['body']
    );
  }

  #[Test]
  public function writes_trace_message() {
    $fixture= new class($this->environment) extends HttpIntegration {
      public function routes($env) {
        return ['/' => function($req, $res) {
          $res->answer(200);
          $res->send('Hello '.$req->param('name').' from '.$req->value('context')->invokedFunctionArn, 'text/plain');
        }];
      }
    };
    $this->invoke($fixture->target(), 'GET', 'name=Test');

    Assert::equals(
      "TRACE [Root=1-dc99d00f-c079a84d433534434534ef0d;Parent=91ed514f1e5c03b2;Sampled=1] 200 GET /default/test?name=Test \n",
      $this->trace->bytes()
    );
  }
}