<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\ResponseDocument;
use test\{Assert, Test, Values};
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

  #[Test, Values(['text/plain', 'text/html', 'text/plain; charset=utf-8'])]
  public function with_text_content($mime) {
    $out= new ResponseDocument();
    $out->begin(200, 'OK', ['Content-Type' => [$mime]]);
    $out->write('Test');
    $out->close();

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => $mime, 'Content-Length' => '4'],
        'body'              => 'Test',
      ],
      $out->document
    );
  }

  #[Test, Values(['application/json', 'application/json; charset=utf-8', 'application/vnd.example.test-v2+json', 'application/vnd.example.test-v2+json; charset=utf-8'])]
  public function with_json_content($mime) {
    $out= new ResponseDocument();
    $out->begin(200, 'OK', ['Content-Type' => [$mime]]);
    $out->write('{"key":"value"}');
    $out->close();

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => false,
        'headers'           => ['Content-Type' => $mime, 'Content-Length' => '15'],
        'body'              => '{"key":"value"}',
      ],
      $out->document
    );
  }

  #[Test]
  public function with_binary_content() {
    $out= new ResponseDocument();
    $out->begin(200, 'OK', ['Content-Type' => ['image/gif']]);
    $out->write('GIF89a...');
    $out->close();

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => true,
        'headers'           => ['Content-Type' => 'image/gif', 'Content-Length' => '9'],
        'body'              => 'R0lGODlhLi4u',
      ],
      $out->document
    );
  }

  #[Test]
  public function with_gzipped_content() {
    $out= new ResponseDocument();
    $out->begin(200, 'OK', ['Content-Type' => ['text/plain'], 'Content-Encoding' => ['gzip']]);
    $out->write("x\234\vI-.\001\000\003\335\001\241");
    $out->close();

    Assert::equals(
      [
        'statusCode'        => 200,
        'statusDescription' => 'OK',
        'isBase64Encoded'   => true,
        'headers'           => ['Content-Type' => 'text/plain', 'Content-Encoding' => 'gzip', 'Content-Length' => '12'],
        'body'              => 'eJwLSS0uAQAD3QGh',
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