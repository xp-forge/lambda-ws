<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\ResponseDocument;
use unittest\{Assert, Test, Values};
use web\Error;

class ResponseDocumentTest {

  #[Test]
  public function can_create() {
    new ResponseDocument();
  }

  #[Test]
  public function no_content() {
    $out= new ResponseDocument();
    $out->begin(204, 'No Content', []);
    $out->close();

    Assert::equals(
      [
        'statusCode'        => 204,
        'statusDescription' => 'No Content',
        'isBase64Encoded'   => false,
        'headers'           => [],
        'body'              => null,
      ],
      $out->document
    );
  }

  #[Test]
  public function with_content() {
    $out= new ResponseDocument();
    $out->begin(200, 'OK', []);
    $out->write('Test');
    $out->close();

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Length' => '4'],
        'body'              => 'Test',
      ],
      $out->document
    );
  }

  #[Test]
  public function write_to_stream() {
    $out= new ResponseDocument();
    $out->begin(200, 'OK', []);
    $out->stream()->write('Test');
    $out->close();

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Length' => '4'],
        'body'              => 'Test',
      ],
      $out->document
    );
  }

  #[Test]
  public function error() {
    $out= new ResponseDocument();

    Assert::equals(
      [
        'statusCode'        => 404,
        'statusDescription' => 'Not Found',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => 'text/plain', 'x-amzn-ErrorType' => 'web.Error'],
        'body'              => 'Error web.Error(#404: Not Found)',
      ],
      $out->error(new Error(404))
    );
  }
}