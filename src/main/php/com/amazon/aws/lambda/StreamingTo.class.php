<?php namespace com\amazon\aws\lambda;

use text\json\{Json, StreamOutput};
use web\io\Output;

/**
 * Response streaming with HTTP integration
 *
 * @test com.amazon.aws.lambda.unittest.HttpApiTest
 * @see  https://github.com/xp-forge/lambda/pull/23#issuecomment-1595720377
 */
class StreamingTo extends Output {
  const DELIMITER= "\0\0\0\0\0\0\0\0";
  const MIME_TYPE= 'application/vnd.awslambda.http-integration-response';

  private $stream;

  /** Creates a new streaming response */
  public function __construct(Stream $stream) {
    $this->stream= $stream;
    $this->stream->use(self::MIME_TYPE);
  }

  /** @return web.io.Output */
  public function stream() { return $this; }

  /**
   * Begins a request
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string[]] $headers
   */
  public function begin($status, $message, $headers) {
    $meta= [
      'statusCode'        => $status,
      'statusDescription' => $message,
      'headers'           => [],
    ];
    foreach ($headers as $name => $values) {
      if ('Set-Cookie' === $name) {
        $meta['cookies']= $values;
      } else {
        $meta['headers'][$name]= current($values);
      }
    }

    $this->stream->write(json_encode($meta));
    $this->stream->write(self::DELIMITER);
  }

  /**
   * Writes the bytes
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) {
    $this->stream->write($bytes);
  }

  /** @return void */
  public function finish() {
    $this->stream->end();
  }
}