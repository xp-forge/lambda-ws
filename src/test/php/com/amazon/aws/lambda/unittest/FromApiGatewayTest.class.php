<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\FromApiGateway;
use io\streams\Streams;
use unittest\{Assert, Test, Values};
use util\Bytes;
use web\io\Part;

class FromApiGatewayTest {
  const COOKIE   = 'session=7AABXMPL1AFD9BBF-0643XMPL09956DE2';
  const BOUNDARY = '------------------------899f0c287170dd63f';

  /** Returns a new fixture */
  private function fixture($method= 'GET', $query= '', $headers= [], $body= null) {
    return new FromApiGateway([
      'version'        => '2.0',
      'routeKey'       => 'ANY /test',
      'rawPath'        => '/default/test',
      'rawQueryString' => $query,
      'cookies'        => [self::COOKIE],
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
          'sourceIp'  => '192.168.178.1',
          'userAgent' => 'XP/Test'
        ],
        'requestId'    => 'JKJaXmPLvHcESHA=',
        'routeKey'     => 'ANY /test',
        'stage'        => 'default',
        'time'         => '10/Mar/2020:05:16:23 +0000',
        'timeEpoch'    => 1583817383220
      ],
      'isBase64Encoded' => $body instanceof Bytes,
      'body'            => $body
    ]);
  }

  #[Test]
  public function can_create() {
    $this->fixture();
  }

  #[Test]
  public function version() {
    Assert::equals('1.1', $this->fixture()->version());
  }

  #[Test]
  public function scheme_defaults_to_http() {
    Assert::equals('http', $this->fixture()->scheme());
  }

  #[Test]
  public function https_scheme_determined_via_x_forwarded_proto() {
    Assert::equals('https', $this->fixture('GET', '', ['x-forwarded-proto' => 'https'])->scheme());
  }

  #[Test, Values(['GET', 'GET', 'POST'])]
  public function method($name) {
    Assert::equals($name, $this->fixture($name)->method());
  }

  #[Test]
  public function uri() {
    Assert::equals('/default/test', $this->fixture('GET')->uri());
  }

  #[Test]
  public function uri_including_query() {
    Assert::equals('/default/test?name=Test', $this->fixture('GET', 'name=Test')->uri());
  }

  #[Test, Values([[[]], [['accept' => '*/*', 'user-agent' => 'test']]])]
  public function headers_include_cookies_and_remote_addr($headers) {
    Assert::equals(
      ['remote-addr' => '192.168.178.1', 'cookie' => self::COOKIE] + $headers,
      iterator_to_array($this->fixture('GET', '', $headers)->headers())
    );
  }

  #[Test]
  public function read_without_body() {
    Assert::equals('', $this->fixture('GET')->read());
  }

  #[Test]
  public function read_body() {
    Assert::equals('Test', $this->fixture('POST', '', ['content-length' => '4'], 'Test')->read());
  }

  #[Test]
  public function read_base64_encoded_body() {
    Assert::equals('Test', $this->fixture('POST', '', ['content-length' => '8'], new Bytes('VGVzdA=='))->read());
  }

  #[Test]
  public function read_part_of_body() {
    $fixture= $this->fixture('POST', '', ['content-length' => '4'], 'Test');

    Assert::equals(['Te', 'st'], [$fixture->read(2), $fixture->read()]);
  }

  #[Test]
  public function readLine_without_body() {
    Assert::null($this->fixture('GET')->readLine());
  }

  #[Test, Values([['Test', ['Test']], ["One\nTwo", ['One', 'Two']], ["One\nTwo\n", ['One', 'Two']]])]
  public function readLine($input, $outcome) {
    $fixture= $this->fixture('POST', '', ['content-length' => strlen($input)], $input);
    $lines= [];
    while (null !== ($line= $fixture->readLine())) {
      $lines[]= $line;
    }

    Assert::equals($outcome, $lines);
  }

  #[Test]
  public function stream_without_body() {
    Assert::null($this->fixture('GET')->incoming());
  }

  #[Test]
  public function stream_body() {
    Assert::equals('Test', Streams::readAll($this->fixture('POST', '', ['content-length' => '4'], 'Test')->incoming()));
  }

  #[Test]
  public function stream_base64_encoded_body() {
    Assert::equals('Test', Streams::readAll($this->fixture('POST', '', ['content-length' => '8'], new Bytes('VGVzdA=='))->incoming()));
  }

  #[Test]
  public function file_upload_including_form_value() {
    $payload= sprintf(implode("\r\n", [
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload',
      '--%1$s--',
      ''
    ]), self::BOUNDARY);
    $fixture= $this->fixture('POST', '', ['content-length' => (string)strlen($payload)], $payload);
    $parts= [];
    foreach ($fixture->parts(self::BOUNDARY) as $name => $part) {
      if (Part::FILE === $part->kind()) {
        $parts[]= ['file', $name.':'.$part->name().':'.$part->type(), $part->bytes()];
      } else if (Part::PARAM === $part->kind()) {
        $parts[]= ['param', $name, $part->value()];
      } else {
        $parts[]= ['incomplete', $name, $part->error()];
      }
    }

    Assert::equals([['file', 'upload:test.txt:text/plain', 'Test'], ['param', 'submit', 'Upload']], $parts);
  }
}