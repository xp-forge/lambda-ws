<?php namespace xp\lambda;

use com\amazon\aws\lambda\{Context, Environment, RequestContext};
use lang\{XPClass, IllegalArgumentException};
use util\UUID;
use util\cmd\Console;
use web\{Application, Filters};

/** Runs lambda HTTP APIs via `xp web com.amazon.aws.lambda.Ws [class]` */
class Web extends Application {
  const TRACE= 'Root=1-5bef4de7-ad49b0e87f6ef6c87fc2e700;Parent=9a9197af755a6419;Sampled=1';
  const REGION= 'test-local-1';

  private $app;

  /**
   * Creates a new instance
   *
   * @param  web.Environment $environment
   * @throws lang.IllegalArgumentException
   */
  public function __construct($environment) {
    if (empty($arguments= $environment->arguments())) {
      throw new IllegalArgumentException('Need an argument');
    }

    $this->app= XPClass::forName($arguments[0]);
    parent::__construct($environment);
  }

  /** @return web.Routing */
  public function routes() {

    // Runtime context
    $function= strtolower($this->app->getSimpleName());
    $region= $this->environment->variable('AWS_REGION') ?? self::REGION;
    $functionArn= "arn:aws:lambda:{$region}:123456789012:function:{$function}";
    $headers= [
      'Lambda-Runtime-Aws-Request-Id'       => [UUID::randomUUID()->hashCode()],
      'Lambda-Runtime-Invoked-Function-Arn' => [$functionArn],
      'Lambda-Runtime-Trace-Id'             => [self::TRACE],
      'Lambda-Runtime-Deadline-Ms'          => [(time() + 900) * 1000],
    ];
    $context= new Context($headers, $_ENV + [
      'AWS_LAMBDA_FUNCTION_NAME' => $function,
      'AWS_REGION'               => $region,
      'AWS_LOCAL'                => true,
    ]);

    // See https://github.com/awsdocs/aws-lambda-developer-guide/blob/main/sample-apps/nodejs-apig/event-v2.json
    $lambda= function($req, $res, $inv) use($function, $context) {
      $via= new RequestContext([
        'accountId'    => '123456789012',
        'apiId'        => 'x17bf9mIws',
        'domainName'   => 'x17bf9mIws.execute-api.test-local-1.amazonaws.com',
        'domainPrefix' => 'x17bf9mIws',
        'requestId'    => 'JKJaXmPLvHcESHA=',
        'routeKey'     => "ANY /{$function}-function-1G3XMPLZXVXYI",
        'stage'        => '$default',
        'timeEpoch'    => time() * 1000,
        'http'         => [
          'method'    => $req->method(),
          'path'      => $req->uri()->path(),
          'protocol'  => 'HTTP/1.1',
          'sourceIp'  => $req->header('Remote-Addr'),
          'userAgent' => $req->header('User-Agent'),
        ]
      ]);

      // Add response headers replicating the inconsistent casing AWS uses
      $res->header('x-amzn-RequestId', $context->awsRequestId);
      $res->header('X-Amzn-Trace-Id', $context->traceId);
      return $inv->proceed($req->pass('context', $context)->pass('request', $via), $res);
    };

    return new Filters([$lambda], $this->app
      ->newInstance(new Environment($this->environment->webroot(), Console::$out))
      ->routes($this->enviroment)
    );
  }

  /** @return string */
  public function toString() {
    return nameof($this).'<'.$this->app->getName().'>';
  }
}