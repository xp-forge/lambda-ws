<?php namespace com\amazon\aws\lambda;

use web\io\Output;

/**
 * Response document
 *
 * @test  com.amazon.aws.lambda.unittest.ResponseDocumentTest
 */
class ResponseDocument extends Output {
  public $document;

  /** @return web.io.Output */
  public function stream() { return $this; }

  /**
   * Returns whether a mimetype can safely be regarded as purely textual:
   * Any mimetype beginning with "text/" as well as JSON and XML types,
   * including (potentially versioned) vendor mimetypes.
   *
   * @param  string $mime May include parameters such as `charset=utf-8`
   * @return bool
   */
  private function isText($mime) {
    return (
      0 === strncmp($mime, 'text/', 5) ||
      0 === strncmp($mime, 'application/xml', 15) ||
      0 === strncmp($mime, 'application/json', 16) ||
      (0 === strncmp($mime, 'application/', 12) && ($p= strcspn($mime, ';')) && (
        0 === substr_compare($mime, '+json', $p - 5, 5) ||
        0 === substr_compare($mime, '+xml', $p - 4, 4)
      ))
    );
  }

  /**
   * Begins a request
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string[]] $headers
   */
  public function begin($status, $message, $headers) {
    $this->document= [
      'statusCode'        => $status,
      'statusDescription' => $message,
      'isBase64Encoded'   => true,
      'headers'           => [],
      'body'              => null,
    ];

    // If no content type is given or it's definitely (unencoded) text, pass
    // through content without encoding to base64 to be more efficient.
    //
    // Note: It's not necessary to check for `identity`, this is only valid
    // in `Accept-Encoding`, see https://github.com/mdn/content/issues/1964
    $mime= $headers['Content-Type'][0] ?? null;
    if (null === $mime || !isset($headers['Content-Encoding']) && $this->isText($mime)) {
      $this->document['isBase64Encoded']= false;
    }

    foreach ($headers as $name => $values) {
      if ('Set-Cookie' === $name) {
        $this->document['cookies']= $values;
      } else {
        $this->document['headers'][$name]= current($values);
      }
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
    if (null === $this->document['body']) return;

    // Report unencoded length in headers
    $this->document['headers']['Content-Length']= (string)strlen($this->document['body']);
    if ($this->document['isBase64Encoded']) {
      $this->document['body']= base64_encode($this->document['body']);
    }
  }
}