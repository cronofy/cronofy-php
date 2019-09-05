# Cronofy

[Cronofy](https://www.cronofy.com) - one API for all the calendars (Google, iCloud, Exchange, Office 365, Outlook.com)
[![Build Status](https://travis-ci.org/cronofy/cronofy-php.svg?branch=master)](https://travis-ci.org/cronofy/cronofy-php)

## Sample Application

To see this API wrapper in action see our sample app [here](https://github.com/cronofy/cronofy-php-sample-app)

## Usage

In order to use the Cronofy API you will need to [create a developer account](https://app.cronofy.com/sign_up/new).

From there you can [use your Calendar Sandbox](https://app.cronofy.com/oauth/sandbox)
to access your own calendars, or you can [create an OAuth application](https://app.cronofy.com/oauth/applications/new)
to obtain an OAuth `client_id` and `client_secret` to be able to use the full
API.

## Authorization

[API documentation](https://www.cronofy.com/developers/api/#authorization)

Generate a link for a user to grant access to their calendars:

```php
$redirect_uri = "http://yoursite.dev/oauth2/callback";

$cronofy = new Cronofy(array("client_id" => "clientId"));
$params = array(
  'redirect_uri' => $redirect_uri,
  'scope' => array('read_account','list_calendars','read_events','create_event','delete_event')
);
$auth = $cronofy->getAuthorizationURL($params);
```

The redirect URI is a page on your website that will handle the OAuth 2.0
callback and receive a `code` parameter. You can then use that code to retrieve
an OAuth token granting access to the user's Cronofy account:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret"
));

$params = array(
  'redirect_uri' => $redirect_uri,
  'code' => $code
);

$token=$cronofy->request_token($params);

```

You should save the response's `AccessToken` and `RefreshToken` for later use.

Note that the **exact same** redirect URI must be passed to both methods for
access to be granted.

## List calendars

[API documentation](https://www.cronofy.com/developers/api/#calendars)

Get a list of all the user's calendars:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$calendar = $cronofy->list_calendars();
```

## Read events

[API documentation](https://www.cronofy.com/developers/api/#read-events)

Get a list of all the user's events:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$params = array(
  'tzid' => 'Etc/UTC'
);

$events = $cronofy->read_events($params);

foreach($events->each() as $event){
  // process event
}
```

## Create or update events

[API documentation](https://www.cronofy.com/developers/api/#upsert-event)

To create/update an event in the user's calendar:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$params = array(
  'calendar_id' => 'calendarID',
  'event_id' => 'event_test_12345679',
  'summary' => 'test event 2',
  'description' => 'some event data here',
  'start' => '2015-12-07T09:00:00Z',
  'end' => '2015-12-08T10:00:00Z'
);
$new_event = $cronofy->upsert_event($params);

```

## Delete events

[API documentation](https://www.cronofy.com/developers/api/#delete-event)

To delete an event from user's calendar:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$params = array(
  'calendar_id' => 'calendarID',
  'event_id' => 'EventID'
);

$delete = $cronofy->delete_event($params);

```

## Delete external events

To delete an external event from a user's calendar:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$params = array(
  'calendar_id' => 'calendarID',
  'event_uid' => 'EventUID'
);

$delete = $cronofy->delete_external_event($params);

```

## Elevated permissions

To elevate a client's permissions for a user's calendar(s):

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$params = array(
  'permissions' => array(
    array(
      'calendar_id' => 'calendarID_1',
      'permission_level' => 'unrestricted'
    ),
    array(
      'calendar_id' => 'calendarID_2',
      'permission_level' => 'unrestricted'
    )
  ),
  'redirect_uri' => 'http://yoursite.dev/elevate/callback'
);

$response = $cronofy->elevated_permissions($params);

```

## Authorize with a Service Account

To authorize a user's account using a service account:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$params = array(
  'email' => $email,
  'callback_url' => $callback_url,
  'scope' => array('read_account','list_calendars','read_events','create_event','delete_event')
);

$response = $cronofy->authorize_with_service_account($params);

```

Note: You will need to use a Service Account access token to perform this action.

## Create a Calendar

To create a calendar for a user's account profile:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$params = array(
  'profile_id' => $account_profile_id,
  'name' => $new_calendar_name
);

$response = $cronofy->create_calendar($params);

```

## Using an Alternative Data Center

To use an alternative data center:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken",
  "data_center" => "DataCenter"
));

```

## List Availability Rules

To retrieve all availability rules saved against an account:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

$response = $cronofy->list_availability_rules();

```

## Read Availability Rule

To retrieve an availability rule:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

// The String that uniquely identifies the availability rule. 
$rule_id = "default";

$response = $cronofy->get_availability_rule($rule_id);

```

## Delete Availability Rule

To delete an availability rule for the authenticated account:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

// The String that uniquely identifies the availability rule. 
$rule_id = "default";

$response = $cronofy->delete_availability_rule($rule_id);

```

## Create or Update Availability Rule

To creates or update an availability rule for the authenticated account:

```php
$cronofy = new Cronofy(array(
  "client_id" => "clientId",
  "client_secret" => "ClientSecret",
  "access_token" => "AccessToken",
  "refresh_token" => "RefreshToken"
));

// The details of the event to create or update:
$params = array(
    "availability_rule_id" => "default",
    "calendar_ids" => array("cal_123"),
    "tzid" => "America/Chicago",
    "weekly_periods" => array(
        array(
            "day" => "monday",
            "start_time" => "09:30",
            "end_time" => "12:30"
        ),
        array(
            "day" => "wednesday",
            "start_time" => "09:30",
            "end_time" => "12:30"
        )
    )
);

$response = $cronofy->create_availability_rule($params);

```

## Running unit tests

```shell
make test
```

## Links

 * [API documentation](https://www.cronofy.com/developers/api)
 * [API mailing list](https://groups.google.com/d/forum/cronofy-api)
