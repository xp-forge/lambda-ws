<?php namespace com\amazon\aws\lambda;

use web\{Application, Routing, Environment as WebEnv};

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

  /** @return web.Application */
  protected final function application() {
    $env= new WebEnv(
      $this->environment->variable('PROFILE') ?? 'prod',
      $this->environment->root,
      $this->environment->path('static'),
      [$this->environment->properties],
      [],
      $this->tracing
    );
    $routes= $this->routes($env);

    // Check for `xp-forge/web ^4.0` applications, which provide an initializer
    if ($routes instanceof Application) {
      method_exists($routes, 'initialize') && $routes->initialize();
      return $routes;
    }

    // Wrap routes inside an application to ensure application-level functionality
    return new class($env, $routes) extends Application {
      private $routes;

      public function __construct($env, $routes) {
        parent::__construct($env);
        $this->routes= $routes;
      }

      public function routes() { return $this->routes; }
    };
  }
}