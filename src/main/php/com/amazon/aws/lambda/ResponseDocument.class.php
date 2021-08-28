<?php namespace com\amazon\aws\lambda;

use web\io\Output;

class ResponseDocument extends Output {
  public $document;

  /** @return web.io.Output */
  public function stream() { return $this; }

  /**
   * Begins a request
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   */
  public function begin($status, $message, $headers) {
    $this->document= [
      'statusCode'        => $status,
      'statusDescription' => $message,
      'isBase64Encoded'   => false,
      'headers'           => [],
      'body'              => null,
    ];
    foreach ($headers as $name => $values) {
      $this->document['headers'][$name]= implode(',', $values);
    }
  }

  /**
   * Returns an error document for a given error
   *
   * @param  web.Error $e
   * @return [:var]
   */
  public function error($e) {
    return [
      'statusCode'        => $e->status(),
      'statusDescription' => $e->getMessage(),
      'isBase64Encoded'   => false,
      'headers'           => ['Content-Type' => 'text/plain', 'x-amzn-ErrorType' => nameof($e)],
      'body'              => $e->compoundMessage(),
    ];
  }

  /**
   * Writes the bytes (in this case, to the internal buffer which can be
   * access via the `bytes()` method)
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) { 
    $this->document['body'].= $bytes;
  }

  /** @return void */
  public function finish() {
    if (null !== $this->document['body']) {
      $this->document['headers']['Content-Length']= (string)strlen($this->document['body']);
    }
  }
}