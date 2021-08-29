<?php namespace com\amazon\aws\lambda;

use io\streams\MemoryInputStream;
use web\io\{Input, Parts};

/**
 * Input from Amazon AWS API Gateway version 2.0
 *
 * @test com.amazon.aws.lambda.unittest.FromApiGatewayTest
 * @see  https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html
 */
class FromApiGateway implements Input {
  private $event, $input;

  /** @param [:var] $event */
  public function __construct($event) {
    $this->event= $event;

    // Handle input body
    if (!isset($event['body'])) {
      $this->input= '';
    } else if ($event['isBase64Encoded']) {
      $this->input= base64_decode($event['body']);
    } else {
      $this->input= $event['body'];
    }
  }

  /** @return string */
  public function version() {
    sscanf($this->event['requestContext']['http']['protocol'] ?? 'HTTP/1.1', "HTTP/%[^\r]", $version);
    return $version;
  }

  /** @return string */
  public function scheme() { return $this->event['headers']['x-forwarded-proto'] ?? 'http'; }

  /** @return string */
  public function method() { return $this->event['requestContext']['http']['method'] ?? 'GET'; }

  /** @return string */
  public function uri() {
    return $this->event['rawQueryString']
      ? $this->event['rawPath'].'?'.$this->event['rawQueryString']
      : $this->event['rawPath']
    ;
  }

  /** @return iterable */
  public function headers() {
    yield 'remote-addr' => $this->event['requestContext']['http']['sourceIp'] ?? '127.0.0.1';
    yield from $this->event['headers'] ?? [];
    if (!empty($this->event['cookies'])) {
      yield 'cookie' => implode('; ', $this->event['cookies']);
    }
  }

  /** @return ?io.streams.InputStream */
  public function incoming() {
    return isset($this->event['body']) ? new MemoryInputStream($this->input) : null;
  }

  /** @return ?string */
  public function readLine() {
    if ('' === $this->input) return null;

    $p= strpos($this->input, "\n");
    if (false === $p) {
      $line= $this->input;
      $this->input= '';
    } else {
      $line= substr($this->input, 0, $p);
      $this->input= substr($this->input, $p + 1);
    }
    return $line;
  }

  /**
   * Reads a given number of bytes
   *
   * @param  int $length Pass -1 to read all
   * @return string
   */
  public function read($length= -1) {
    if (-1 === $length) {
      $chunk= $this->input;
      $this->input= '';
    } else {
      $chunk= substr($this->input, 0, $length);
      $this->input= substr($this->input, $length);
    }
    return $chunk;
  }

  /**
   * Returns parts from a multipart/form-data request
   *
   * @param  string $boundary
   * @return iterable
   */
  public function parts($boundary) {
    return new Parts($this->incoming(), $boundary);
  }
}