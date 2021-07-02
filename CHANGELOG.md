## [1.5.0]
* add support for `hmac_valid` which verifies a HMAC from a push notification using the client's secret.

## [1.4.0]
* add support for `recurrence` key on upsertEvent (this is currently pre-release and not generally available yet)

## [1.3.0]
* add AvailablePeriod create, read, delete and bulkDelete [#105] [#106]
* add support for empty descriptions [#103]
* add support for subscriptions on baseUpsertEvent [#104]

## [1.2.0]

* add support for the Batch endpoint [#97]
* add description for HTTP 429 (Too many requests) responses
* add an SDK-dev script for manually testing the SDK against the API

## [1.1.10]

* add missing exception `use` [#86]

## [1.1.9]

* add support for `provider_name` to `conferencingServiceAuthorization()` [#94]

## [1.1.8]

* add `conferencingServiceAuthorization()`

## [1.1.7]

* support revoking subs

## [1.1.6]

* less restrictive options for `conferencing`

## [1.1.5]

* add `requestElementToken()`

## [1.1.4]

* add `color` support to `upsertEvent`

## [1.1.3]

* allow serialization of booleans in params

## [1.1.2]

* disable libcurl verbose output

## [1.1.1]

* add support for `provider_name` on `getAuthorizationURL()`

## [1.1.0]

* add support for Conferencing Services

## [1.0.1]

* documentation updates for v1 release

## [1.0.0]

* namespacing and updated naming [#33]

## [0.29.0]

* cancel_smart_invite recipients [#32]

[0.29.0]: https://github.com/cronofy/cronofy-php/releases/tag/v0.29.0
[1.0.0]: https://github.com/cronofy/cronofy-php/releases/tag/v1.0.0
[1.0.1]: https://github.com/cronofy/cronofy-php/releases/tag/v1.0.1
[1.1.0]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.0
[1.1.1]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.1
[1.1.2]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.2
[1.1.3]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.3
[1.1.4]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.4
[1.1.5]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.5
[1.1.6]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.6
[1.1.7]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.7
[1.1.8]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.8
[1.1.9]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.9
[1.1.10]: https://github.com/cronofy/cronofy-php/releases/tag/v1.1.10
[1.2.0]: https://github.com/cronofy/cronofy-php/releases/tag/v1.2.0
[1.3.0]: https://github.com/cronofy/cronofy-php/releases/tag/v1.3.0
[1.4.0]: https://github.com/cronofy/cronofy-php/releases/tag/v1.4.0
[1.5.0]: https://github.com/cronofy/cronofy-php/releases/tag/v1.5.0

[#32]: https://github.com/cronofy/cronofy-php/pull/76
[#33]: https://github.com/cronofy/cronofy-php/pull/74
[#34]: https://github.com/cronofy/cronofy-php/pull/77
[#94]: https://github.com/cronofy/cronofy-php/pull/94
[#86]: https://github.com/cronofy/cronofy-php/pull/86
[#97]: https://github.com/cronofy/cronofy-php/pull/97
[#103]: https://github.com/cronofy/cronofy-php/pull/103
[#104]: https://github.com/cronofy/cronofy-php/pull/104
[#105]: https://github.com/cronofy/cronofy-php/pull/105
[#106]: https://github.com/cronofy/cronofy-php/pull/106
