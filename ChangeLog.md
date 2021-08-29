AWS Lambda Webservices change log
=================================

## ?.?.? / ????-??-??

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