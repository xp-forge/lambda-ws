<?php namespace com\amazon\aws\lambda;

use web\{Routing, Environment as WebEnv};

/**
 * Base class for HTTP APIs with the following implementations:
 *
 * - `HttpStreaming` for Lambda function URLs with streaming support
 * - `HttpApi` for API Gateway and function URLs with buffering
 *
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/configuration-response-streaming.html
 */
abstract class HttpIntegration extends Handler {
  protected $tracing;

  /** Creates a new handler with a given lambda environment */
  public function __construct(Environment $environment) {
    parent::__construct($environment);
    $this->tracing= new Tracing($environment);
  }

  /**
   * Returns routes. Overwrite this in subclasses!
   * 
   * @param  web.Environment $environment
   * @return web.Application|web.Routing|[:var]
   */
  public abstract function routes($environment);

  /** @return web.Routing */
  protected final function routing() {
    return Routing::cast($this->routes(new WebEnv(
      $this->environment->variable('PROFILE') ?? 'prod',
      $this->environment->root,
      $this->environment->path('static'),
      [$this->environment->properties],
      [],
      $this->tracing
    )));
  }
}