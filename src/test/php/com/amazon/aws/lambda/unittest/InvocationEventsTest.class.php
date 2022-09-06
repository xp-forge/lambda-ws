<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\FromApiGateway;
use unittest\{Assert, Test, Values};
use util\Bytes;
use web\Request;

class InvocationEventsTest {

  /** Returns a new fixture */
  private function fromLambdaFunctionUrl($method= 'GET', $query= '', $headers= [], $body= null) {

    // Lambda URLs contain parsed cookies *as* well as the raw cookie header
    // See https://github.com/xp-forge/lambda-ws/issues/8
    if (isset($headers['cookie'])) {
      $cookies= explode('; ', $headers['cookie']);
    } else {
      $cookies= [];
    }

    return new FromApiGateway([
      'version'        => '2.0',
      'routeKey'       => '$default',
      'rawPath'        => '/',
      'rawQueryString' => $query,
      'cookies'        => $cookies,
      'headers'        => $headers,
      'requestContext' => [
        'accountId'    => 'anonymous',
        'apiId'        => 'r3vzu5yn3irlwjl4z1ves4w2ne0vkzeg',
        'domainName'   => 'r3vzu5yn3irlwjl4z1ves4w2ne0vkzeg.lambda-url.eu-central-1.on.aws',
        'domainPrefix' => 'r3vzu5yn3irlwjl4z1ves4w2ne0vkzeg',
        'http'         => [ 
          'method'    => $method,
          'path'      => '/',
          'protocol'  => 'HTTP/1.1',
          'sourceIp'  => '192.168.178.1',
          'userAgent' => 'XP/Test'
        ],
        'requestId'    => '93c8108b-7d54-49ff-9b52-23e350b7df63=',
        'routeKey'     => '$default',
        'stage'        => '$default',
        'time'         => '06/Sep/2022:20:45:07 +0000',
        'timeEpoch'    => 1662497107430
      ],
      'isBase64Encoded' => $body instanceof Bytes,
      'body'            => $body
    ]);
  }

  /** Returns a new fixture */
  private function fromHttpApiGateway($method= 'GET', $query= '', $headers= [], $body= null) {

    // HTTP API gateway invocations only contain parsed cookies
    if (isset($headers['cookie'])) {
      $cookies= explode('; ', $headers['cookie']);
      unset($headers['cookie']);
    } else {
      $cookies= [];
    }

    return new FromApiGateway([
      'version'        => '2.0',
      'routeKey'       => 'ANY /test',
      'rawPath'        => '/default/test',
      'rawQueryString' => $query,
      'cookies'        => $cookies,
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

  #[Test, Values(['fromLambdaFunctionUrl', 'fromHttpApiGateway'])]
  public function one_cookie($source) {
    $req= new Request($this->{$source}('GET', '', ['cookie' => 'session=6317aaa1de197746d20fadc1']));
    Assert::equals(['session' => '6317aaa1de197746d20fadc1'], $req->cookies());
  }

  #[Test, Values(['fromLambdaFunctionUrl', 'fromHttpApiGateway'])]
  public function two_cookies($source) {
    $req= new Request($this->{$source}('GET', '', ['cookie' => 'session=6317aaa1de197746d20fadc1; lang=en']));
    Assert::equals(['session' => '6317aaa1de197746d20fadc1', 'lang' => 'en'], $req->cookies());
  }
}