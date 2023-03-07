<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\RequestContext;
use test\{Assert, Test};

class RequestContextTest {
  const CONTEXT = [
    'accountId'    => '123456789012',
    'apiId'        => 'r3pmxmplak',
    'domainName'   => 'r3pmxmplak.execute-api.us-east-2.amazonaws.com',
    'domainPrefix' => 'r3pmxmplak',
    'http'         => [ 
      'method'    => 'GET',
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
  ];

  #[Test]
  public function can_create() {
    new RequestContext(self::CONTEXT);
  }

  #[Test]
  public function date_instance() {
    Assert::equals(
      (int)(self::CONTEXT['timeEpoch'] / 1000),
      (new RequestContext(self::CONTEXT))->time->getTime()
    );
  }

  #[Test]
  public function string_representation_not_empty() {
    Assert::notEquals('', (new RequestContext(self::CONTEXT))->toString());
  }

  #[Test]
  public function hashcode_not_empty() {
    Assert::notEquals('', (new RequestContext(self::CONTEXT))->hashCode());
  }

  #[Test]
  public function equal_to_another_instance_with_same_context() {
    Assert::equals(
      new RequestContext(self::CONTEXT),
      new RequestContext(self::CONTEXT)
    );
  }

  #[Test]
  public function does_not_equal_different_context() {
    Assert::notEquals(
      new RequestContext(self::CONTEXT),
      new RequestContext(['accountId' => '610056789012'] + self::CONTEXT)
    );
  }

  #[Test]
  public function comparing_to_null() {
    Assert::equals(1, (new RequestContext(self::CONTEXT))->compareTo(null));
  }
}