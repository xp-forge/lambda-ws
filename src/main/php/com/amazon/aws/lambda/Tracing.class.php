<?php namespace com\amazon\aws\lambda;

use web\Logging;
use web\logging\ToFunction;

class Tracing extends Logging {

  public function __construct(Environment $environment) {
    parent::__construct(new ToFunction(function($request, $response, $error= null) use($environment) {
      $query= $request->uri()->query();
      $environment->trace(sprintf(
        'TRACE [%s] %d %s %s %s',
        $request->value('context')->traceId,
        $response->status(),
        $request->method(),
        $request->uri()->path().($query ? '?'.$query : ''),
        $error ? $error->toString() : ''
      ));
    }));
  }
}
