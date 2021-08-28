<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\FromApiGateway;
use io\streams\Streams;
use unittest\{Assert, Test, Values};
use util\Bytes;

class FromApiGatewayTest {
  const COOKIE = 'session=7AABXMPL1AFD9BBF-0643XMPL09956DE2';

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
          'sourceIp'  => '127.0.0.1',
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
  public function headers_include_cookies($headers) {
    Assert::equals(
      ['cookie' => self::COOKIE] + $headers,
      iterator_to_array($this->fixture('GET', '', $headers)->headers())
    );
  }

  #[Test]
  public function without_body() {
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
}