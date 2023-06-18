<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{HttpIntegration, Stream, StreamingTo};
use test\{Assert, Test};

class HttpIntegrationTest extends InvocationTest {

  /**
   * Stream calls to testing stream, then return this stream
   *
   * @param  web.Application|[:function(web.Request, web.Response)] $routes
   * @param  var $event
   * @return var
   */
  protected function invocation($routes, $event) {
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

    $integration= new class($this->environment, $routes) extends HttpIntegration {
      private $routes;

      public function __construct($environment, $routes) {
        parent::__construct($environment);
        $this->routes= $routes;
      }

      public function routes($env) { return $this->routes; }
    };

    $target= $integration->target();
    $target($event, $stream, $this->context);
    return $stream;
  }

  /**
   * Transforms response to abstract format
   *
   * @param  var $stream
   * @return var
   */
  protected function transform($stream) {
    $result= $stream->unmarshal();
    return $result['meta'] + ['isBase64Encoded' => false] + ('' === $result['body']
      ? []
      : ['body' => $result['body']
    ]);
  }

  #[Test]
  public function sets_awslambda_http_integration_response_vendor_mimetype() {
    $handler= function($req, $res) { $res->answer(204); };

    Assert::equals(
      StreamingTo::MIME_TYPE,
      $this->invoke($handler, 'GET')->mime
    );
  }

  #[Test]
  public function adds_must_use_streaming_hint() {
    $handler= function($req, $res) { $res->answer(204); };

    Assert::equals(
      StreamingTo::USESTREAM,
      $this->invoke($handler, 'GET')->unmarshal(true)['meta']['body']
    );
  }
}