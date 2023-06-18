<?php namespace com\amazon\aws\lambda;

use lang\MethodNotImplementedException;
use web\{Routing, Environment as WebEnv};

/**
 * Base class for HTTP APIs with the following implementations:
 *
 * - `HttpIntegration` for Lambda function URLs with streaming support
 * - `ApiGateway` for HTTP and REST API Gateway
 *
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/configuration-response-streaming.html
 */
abstract class HttpApi extends Handler {
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

  /** @return callable|com.amazon.aws.lambda.Lambda|com.amazon.aws.lambda.Streaming */
  public function target() {
    throw new MethodNotImplementedException('Extend either HttpIntegration or ApiGateway', __FUNCTION__);
  }
}