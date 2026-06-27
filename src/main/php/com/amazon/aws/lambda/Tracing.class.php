<?php namespace com\amazon\aws\lambda;

class Tracing {
  private $environment;

  public function __construct(Environment $environment) {
    $this->environment= $environment;
  }

  public function log($request, $response, $error= null) {
    $query= $request->uri()->query();
    $this->environment->trace(sprintf(
      'TRACE [%s] %d %s %s %s',
      $request->value('context')->traceId,
      $response->status(),
      $request->method(),
      $request->uri()->path().($query ? '?'.$query : ''),
      $error ? $error->toString() : ''
    ));
  }
}
