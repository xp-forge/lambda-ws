AWS Lambda Webservices for the XP Framework
========================================================================

[![Build status on GitHub](https://github.com/xp-forge/lambda-ws/workflows/Tests/badge.svg)](https://github.com/xp-forge/lambda-ws/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/lambda-ws/version.png)](https://packagist.org/packages/xp-forge/lambda-ws)

Run [XP web applications](https://github.com/xp-forge/web) on AWS lambda using [API Gateway](https://docs.aws.amazon.com/lambda/latest/dg/services-apigateway.html).

Example
-------
Put this code in a file called *Greet.class.php*:

```php
use com\amazon\aws\lambda\HttpApi;

class Greet extends HttpApi {

  /**
   * Returns routes
   *
   * @param  web.Environment $env
   * @return web.Application|web.Routing|[:var]
   */
  public function routes($env) {
    return ['/' => function($req, $res) {
      $greeting= sprintf(
        'Hello %s from PHP %s @ %s',
        $req->param('name') ?? $req->header('User-Agent') ?? 'Guest',
        PHP_VERSION,
        $req->value('context')->region
      );

      $res->answer(200);
      $res->send($greeting, 'text/plain');
    }];
  }
}
```

To run existing web applications, return an instance of your application subclass from the *routes()* method.

Setup and deployment
--------------------
Follow the steps shown on the [xp-forge/lambda README](https://github.com/xp-forge/lambda) to create the runtime layer, the service role and the lambda function itself. Next, create the API gateway as follows:

```bash
$ aws apigatewayv2 create-api \
  --name hello-api \
  --protocol-type HTTP \
  --target "arn:aws:lambda:us-east-1:XXXXXXXXXXXX:function:hello"
```

The API's HTTP endpoint will be returned by this command.

Invocation
----------
You can either open the HTTP endpoint in your browser or by using *curl*:

```bash
$ curl -i https://XXXXXXXXXX.execute-api.us-east-1.amazonaws.com/hello?name=$USER
HTTP/2 200
date: Sat, 28 Aug 2021 21:26:13 GMT
content-type: text/plain
content-length: 42
apigw-requestid: Ey9-Xg_UliAEPKQ=

Hello timmf from PHP 8.0.10 @ eu-central-1
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

See also
--------
* [Developing an HTTP API in API Gateway](https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop.html)