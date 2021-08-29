<?php namespace com\amazon\aws\lambda;

use lang\Value;
use util\{Date, Objects};

/**
 * Request context
 *
 * @see  https://github.com/awsdocs/aws-lambda-developer-guide/blob/main/sample-apps/nodejs-apig/event-v2.json
 * @test  com.amazon.aws.lambda.unittest.RequestContextTest
 */
class RequestContext implements Value {
  public $accountId, $apiId, $domainName, $domainPrefix, $requestId, $routeKey, $stage, $time, $http;

  /** Creates an instance from a given event.requestContext */
  public function __construct(array $context) {
    $this->accountId= $context['accountId'];
    $this->apiId= $context['apiId'];
    $this->domainName= $context['domainName'];
    $this->domainPrefix= $context['domainPrefix'];
    $this->requestId= $context['requestId'];
    $this->routeKey= $context['routeKey'];
    $this->stage= $context['stage'];
    $this->time= new Date((int)($context['timeEpoch'] / 1000));
    $this->http= $context['http'];
  }

  /** @return string */
  public function toString() {
    return sprintf(
      "%s(%s -> %s::%s)@{\n".
      "  [accountId   ] %s\n".
      "  [domainName  ] %s\n".
      "  [domainPrefix] %s\n".
      "  [stage       ] %s\n".
      "  [time        ] %s\n".
      "  [http        ] %s\n".
      "}\n",
      nameof($this),
      $this->requestId,
      $this->apiId,
      $this->routeKey,
      $this->accountId,
      $this->domainName,
      $this->domainPrefix,
      $this->stage,
      $this->time->toString(),
      Objects::stringOf($this->http, '  ')
    );
  }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $this <=> $value;
  }
}