<?php namespace com\amazon\aws\lambda;

use util\Objects;
use web\Logging;
use web\logging\ToFunction;

class Tracing extends Logging {

  public function __construct(Environment $environment) {
    parent::__construct(new ToFunction(function($status, $method, $resource, $hints) use($environment) {
      $traceId= $hints['traceId'];
      unset($hints['traceId']);
      $environment->trace(sprintf(
        'TRACE [%s] %d %s %s %s',
        $traceId,
        $status,
        $method,
        $resource,
        Objects::stringOf($hints)
      ));
    }));
  }
}
