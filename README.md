AWS Lambda Webservices for the XP Framework
========================================================================

[![Build status on GitHub](https://github.com/xp-forge/lambda-ws/workflows/Tests/badge.svg)](https://github.com/xp-forge/lambda-ws/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/lambda-ws/version.svg)](https://packagist.org/packages/xp-forge/lambda-ws)

Run [XP web applications](https://github.com/xp-forge/web) on AWS lambda using [API Gateway](https://docs.aws.amazon.com/lambda/latest/dg/services-apigateway.html) or [Lambda Function URLs](https://aws.amazon.com/de/blogs/aws/announcing-aws-lambda-function-urls-built-in-https-endpoints-for-single-function-microservices/). Unlike other implementations, this library does not spawn PHP-FPM but runs requests directly, resulting in an overhead of less than 1ms.

Example
-------
Put this code in a file called *Greet.class.php*:

```php
use com\amazon\aws\lambda\HttpIntegration;

class Greet extends HttpIntegration {

  /**
   * Returns routes
   *
   * @param  web.Environment $env
   * @return web.Application|web.Routing|[:var]
   */
  public function routes($env) {
    return ['/' => function($req, $res) {
      $greeting= sprintf(
        'Hello %s from PHP %s on stage %s @ %s',
        $req->param('name') ?? $req->header('User-Agent') ?? 'Guest',
        PHP_VERSION,
        $req->value('request')->stage,
        $req->value('context')->region
      );

      $res->answer(200);
      $res->send($greeting, 'text/plain');
    }];
  }
}
```

The request context is passed into a request value named *request* and contains a [RequestContext instance](https://github.com/xp-forge/lambda-ws#request-context). The [lambda context](https://github.com/xp-forge/lambda#context) is passed in *context*.

To run existing web applications, return an instance of your `web.Application` subclass from the *routes()* method. 

Development & testing
---------------------
To run the HTTP APIs locally, this library integrates with [xp-forge/web](https://github.com/xp-forge/web) via a wrapper:

```bash
$ xp web lambda Greet
@xp.web.srv.Standalone(HTTP @ peer.ServerSocket(Resource id #124 -> tcp://127.0.0.1:8080))
Serving prod:Lambda<Greet>[] > web.logging.ToConsole
════════════════════════════════════════════════════════════════════════
> Server started: http://localhost:8080 in 0.057 seconds
  Sat, 18 Nov 2023 12:19:32 +0100 - PID 18668; press Ctrl+C to exit

# ...
```

By adding `-m develop`, these can be run in the development webserver.

Setup and deployment
--------------------
Follow the steps shown on the [xp-forge/lambda README](https://github.com/xp-forge/lambda) to create the runtime layer, the service role and the lambda function itself. Next, create the function URL as follows:

```bash
$ aws lambda create-function-url-config \
  --function-name greet \
  --auth-type NONE \
  --invoke-mode RESPONSE_STREAM
```

The URL will be returned by this command.

Invocation
----------
You can either open the HTTP endpoint in your browser or by using *curl*:

```bash
$ curl -i https://XXXXXXXXXX.lambda-url.eu-central-1.on.aws/?name=$USER
Date: Sun, 18 Jun 2023 20:00:55 GMT
Content-Type: text/plain
Transfer-Encoding: chunked
Connection: keep-alive
x-amzn-RequestId: 3505bbff-e39e-42d3-98d7-9827fb3eb093
x-amzn-Remapped-content-length: 59
Set-Cookie: visited=1687118455; SameSite=Lax; HttpOnly
X-Amzn-Trace-Id: root=1-648f6276-672c96fe6230795d23453441;sampled=0;lineage=83e616e2:0

Hello timmf from PHP 8.2.7 on stage $default @ eu-central-1
```

Deploying changes
-----------------
After having initially created your lambda, you can update its code as follows:

```bash
$ xp lambda package Greet.class.php
$ aws lambda update-function-code \
  --function-name greet \
  --zip-file fileb://./function.zip \
  --publish
```

Streaming
---------
This library implements HTTP response streaming as [announced by AWS in April 2023](https://aws.amazon.com/de/blogs/compute/introducing-aws-lambda-response-streaming/), improving TTFB and memory consumption of web applications. Response streaming is available for lambda function URLs which have their invoke mode set to *RESPONSE_STREAM*.

Inherit from the *HttpStreaming* base class instead of *HttpApi*:

```php
use com\amazon\aws\lambda\HttpStreaming;

class Greet extends HttpStreaming {

  public function routes($env) {
    /* Shortened for brevity */
  }
}
```

Next, deploy the change, then update the function configuration:

```bash
$ aws lambda update-function-url-config --function-name greet --invoke-mode RESPONSE_STREAM
```

Request context
---------------
The request context passed via the *request* value is defined as follows:

```php
public class com.amazon.aws.lambda.RequestContext implements lang.Value {
  public string $accountId
  public string $apiId
  public string $domainName
  public string $domainPrefix
  public string $requestId
  public string $routeKey
  public string $stage
  public util.Date $time
  public [:string] $http

  public function __construct(array $context)

  public function toString(): string
  public function hashCode(): string
  public function compareTo(var $value): int
}
```

See also
--------
* [Developing an HTTP API in API Gateway](https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop.html)
* [Matthieu Napoli :: A journey toward serverless on a ship called PHP](https://www.youtube.com/watch?v=VfoNUUJggIA)