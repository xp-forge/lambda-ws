AWS Lambda Webservices change log
=================================

## ?.?.? / ????-??-??

## 3.0.0 / 2025-05-05

* **Heads up**: Dropped support for PHP < 7.4, see xp-framework/rfc#343
  (@thekid)
* Added PHP 8.5 to test matrix - @thekid

## 2.3.0 / 2024-05-20

* Added support for dispatching on application level, introduced in
  https://github.com/xp-forge/web/releases/tag/v4.2.0
  (@thekid)

## 2.2.0 / 2024-03-24

* Made compatible with XP 12 - @thekid

## 2.1.0 / 2024-01-30

* Merged PR #12: Check for `xp-forge/web ^4.0` applications, which
  provide an initializer (see https://github.com/xp-forge/web/pull/91)
  (@thekid)

## 2.0.0 / 2023-11-20

* **Heads up:** Dropped support for XP <= 9, see xp-framework/rfc#341
  (@thekid)
* **Heads up:** Bumped minimum version requirement for `xp-forge/web`
  to version 3.0.0
  (@thekid)
* Merged PR #10: Implement HTTP response streaming. This bumps the
  minimum version requirement for `xp-forge/lambda` to version 5.0.0
  (@thekid)

## 1.3.0 / 2023-11-20

* Merged PR #11: Run lambda HTTP APIs via `xp web lambda [class]`. This
  requires https://github.com/xp-forge/web/releases/tag/v3.10.0
  (@thekid)

## 1.2.0 / 2023-10-15

* Made this library compatible with `xp-forge/lambda` 5.0.0 - @thekid
* Added PHP 8.4 to the test matrix - @thekid

## 1.1.4 / 2023-04-03

* Fixed case with the string "null" being sent back when using streaming
  and never writing to the response stream by removing response `body`
  (@thekid)
* Merged PR #9: Migrate to new testing library - @thekid

## 1.1.3 / 2022-10-14

* Fixed responses with `Content-Encoding: gzip` (*or `br`*) not being
  base64-encoded. This leads to unparseable lambda responses.
  (@thekid)

## 1.1.2 / 2022-09-06

* Fixed issue #8: Cookies error in Lambda Function URLs - @thekid

## 1.1.1 / 2022-04-12

* Merged PR #7: Fix redirects yielding "null" - @thekid

## 1.1.0 / 2022-04-12

* Merged PR #5: Encode binary responses using base 64 - @thekid

## 1.0.3 / 2022-02-26

* Made library compatible with `xp-forge/lambda` version 4.0.0
  (@thekid)

## 1.0.2 / 2021-10-21

* Made library compatible with XP 11, `xp-forge/lambda` version 3.0.0
  (@thekid)

## 1.0.1 / 2021-09-26

* Made compatible with XP web 3.0, see xp-forge/web#83 - @thekid

## 1.0.0 / 2021-08-29

* Made lambda handler raise errors for missing or unhandled versions
  (@thekid)
* Implemented gateway payload version 2.0 cookies correctly - @thekid

## 0.7.0 / 2021-08-29

* Wrapped requestContext in `com.amazon.aws.lambda.RequestContext`
  (@thekid)

## 0.6.0 / 2021-08-29

* Made compatible with `xp-forge/lambda` version 2.0.0 - @thekid

## 0.5.0 / 2021-08-29

* Made events' requestContext member accessible via *request* value
  (@thekid)
* Made remote address available in HTTP headers, populated via events'
  requestContext.http.sourceIp member
  (@thekid)

## 0.4.1 / 2021-08-29

* Added various tests, increasing test coverage to 100%. See #3 - @thekid
* Fixed error handling for `web.Error` instances - @thekid
* Fixed `FromApiGateway::readLine()` not returning EOF correctly - @thekid

## 0.4.0 / 2021-08-29

* Fixed issue #2: Return cookies in multiValueHeaders - @thekid

## 0.3.0 / 2021-08-29

* Bumped dependency on `xp-forge/web` to 2.13.0+, simplifying routing
  setup via `Routing::cast()`.
  (@thekid)

## 0.2.0 / 2021-08-28

* Implemented issue #1: File uploads - @thekid
* Fixed POST requests - @thekid

## 0.1.0 / 2021-08-28

* Hello World! First release - @thekid