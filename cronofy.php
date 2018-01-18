<?php

class CronofyException extends Exception
{
    private $error_details;

    public function __construct($message, $code, $error_details = null)
    {
        $this->error_details = $error_details;

        parent::__construct($message, $code, null);
    }

    public function error_details()
    {
        return $this->error_details;
    }
}

interface HttpRequest
{
    public function http_get($url, array $auth_headers);
    public function http_post($url, array $params, array $auth_headers);
    public function http_delete($url, array $params, array $auth_headers);
}

class CurlRequest implements HttpRequest
{
    public $useragent;

    public function __construct($useragent) {
        $this->useragent = $useragent;
    }

    public function http_get($url, array $auth_headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_headers);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->useragent);
        // empty string means send all supported encoding types
        curl_setopt($curl, CURLOPT_ENCODING, '');
        $result = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new CronofyException(curl_error($curl), 2);
        }
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return array($result, $status_code);
    }

    public function http_post($url, array $params, array $auth_headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_headers);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        // empty string means send all supported encoding types
        curl_setopt($curl, CURLOPT_ENCODING, '');
        $result = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new CronofyException(curl_error($curl), 3);
        }
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return array($result, $status_code);
    }

    public function http_delete($url, array $params, array $auth_headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_headers);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        // empty string means send all supported encoding types
        curl_setopt($curl, CURLOPT_ENCODING, '');
        $result = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new CronofyException(curl_error($curl), 4);
        }
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return array($result, $status_code);
    }
}

class Cronofy
{
    const USERAGENT = 'Cronofy PHP 0.15.0';
    const API_VERSION = 'v1';

    public $api_root_url;
    public $app_root_url;
    public $host_domain;

    public $client_id;
    public $client_secret;
    public $access_token;
    public $refresh_token;
    public $expires_in;
    public $http_client;

    public function __construct($config = array())
    {
        if (!function_exists('curl_init')) {
            throw new CronofyException("missing cURL extension", 1);
        }

        if (!empty($config["client_id"])) {
            $this->client_id = $config["client_id"];
        }
        if (!empty($config["client_secret"])) {
            $this->client_secret = $config["client_secret"];
        }
        if (!empty($config["access_token"])) {
            $this->access_token = $config["access_token"];
        }
        if (!empty($config["refresh_token"])) {
            $this->refresh_token = $config["refresh_token"];
        }
        if (!empty($config["expires_in"])) {
            $this->expires_in = $config["expires_in"];
        }

        if(!empty($config["http_client"])) {
            $this->http_client = $config["http_client"];
        } else {
            $this->http_client = new CurlRequest(self::USERAGENT);
        }

        $this->set_urls(isset($config["data_center"]) ? $config["data_center"] : false);
    }

    private function set_urls($data_center = false)
    {
        $data_center_addin = $data_center ? '-' . $data_center : '';

        $this->api_root_url = "https://api$data_center_addin.cronofy.com";
        $this->app_root_url = "https://app$data_center_addin.cronofy.com";
        $this->host_domain = "api$data_center_addin.cronofy.com";
    }

    private function base_http_get($path, array $auth_headers, array $params)
    {
        $url = $this->api_url($path);
        $url .= $this->url_params($params);

        if (filter_var($url, FILTER_VALIDATE_URL)===false) {
            throw new CronofyException('invalid URL');
        }

        list ($result, $status_code) = $this->http_client->http_get($url, $auth_headers);

        return $this->handle_response($result, $status_code);
    }

    private function api_key_http_get($path, array $params = array())
    {
        return $this->base_http_get($path, $this->get_api_key_auth_headers(), $params);
    }

    private function http_get($path, array $params = array())
    {
        return $this->base_http_get($path, $this->get_auth_headers(), $params);
    }

    private function base_http_post($path, $auth_headers, array $params = array())
    {
        $url = $this->api_url($path);

        if (filter_var($url, FILTER_VALIDATE_URL)===false) {
            throw new CronofyException('invalid URL');
        }

        list ($result, $status_code) = $this->http_client->http_post($url, $params, $auth_headers);

        return $this->handle_response($result, $status_code);
    }

    private function http_post($path, array $params = array())
    {
        return $this->base_http_post($path, $this->get_auth_headers(true), $params);
    }

    private function api_key_http_post($path, array $params = array())
    {
        return $this->base_http_post($path, $this->get_api_key_auth_headers(true), $params);
    }

    private function base_http_delete($path, $auth_headers, array $params = array())
    {
        $url = $this->api_url($path);

        if (filter_var($url, FILTER_VALIDATE_URL)===false) {
            throw new CronofyException('invalid URL');
        }

        list ($result, $status_code) = $this->http_client->http_delete($url, $params, $auth_headers);

        return $this->handle_response($result, $status_code);
    }

    private function http_delete($path, array $params = array())
    {
        return $this->base_http_delete($path, $this->get_auth_headers(true), $params);
    }

    public function getAuthorizationURL($params)
    {
        /*
          Array $params : An array of additional paramaters
          redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
          scope : An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
          state : String A value that will be returned to you unaltered along with the user's authorization request decision. OPTIONAL
          avoid_linking : Boolean when true means we will avoid linking calendar accounts together under one set of credentials. OPTIONAL
          link_token : String The link token to explicitly link to a pre-existing account. OPTIONAL

          Response :
          String $url : The URL to authorize your access to the Cronofy API
         */

        $scope_list = rawurlencode(join(" ", $params['scope']));

        $url = $this->app_root_url . "/oauth/authorize?response_type=code&client_id=" . $this->client_id . "&redirect_uri=" . urlencode($params['redirect_uri']) . "&scope=" . $scope_list;
        if (!empty($params['state'])) {
            $url.="&state=" . $params['state'];
        }
        if (!empty($params['avoid_linking'])) {
            $url.="&avoid_linking=" . $params['avoid_linking'];
        }
        if (!empty($params['link_token'])) {
            $url.="&link_token=" . $params['link_token'];
        }

        return $url;
    }

    public function getEnterpriseConnectAuthorizationUrl($params)
    {
        /*
          Array $params : An array of additional parameters
          redirect_uri : String. The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
          scope : Array. An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
          delegated_scope : Array. An array of scopes to be granted that will be allowed to be granted to the account's users. REQUIRED
          state : String. A value that will be returned to you unaltered along with the user's authorization request decsion. OPTIONAL

          Response :
          $url : String. The URL to authorize your enterprise connect access to the Cronofy API
         */

        $scope_list = rawurlencode(join(" ", $params['scope']));
        $delegated_scope_list = rawurlencode(join(" ", $params['delegated_scope']));

        $url = $this->app_root_url . "/enterprise_connect/oauth/authorize?response_type=code&client_id=" . $this->client_id . "&redirect_uri=" . urlencode($params['redirect_uri']) . "&scope=" . $scope_list . "&delegated_scope=" . $delegated_scope_list;
        if (!empty($params['state'])) {
            $url.="&state=" . rawurlencode($params['state']);
        }
        return $url;
    }

    public function request_token($params)
    {
        /*
          Array $params : An array of additional paramaters
          redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
          code: The short-lived, single-use code issued to you when the user authorized your access to their account as part of an Authorization  REQUIRED

          Response :
          true if successful, error string if not
         */
        $postfields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $params['code'],
            'redirect_uri' => $params['redirect_uri']
        );

        $tokens = $this->http_post("/oauth/token", $postfields);

        if (!empty($tokens["access_token"])) {
            $this->access_token = $tokens["access_token"];
            $this->refresh_token = $tokens["refresh_token"];
            $this->expires_in = $tokens["expires_in"];
            return true;
        } else {
            return $tokens["error"];
        }
    }

    public function request_link_token()
    {
        /*
          returns $result - The link_token to explicitly link to a pre-existing account. Details are available in the Cronofy API Documentation
         */
        return $this->http_post('/' . self::API_VERSION . '/link_tokens');
    }

    public function refresh_token()
    {
        /*
          String $refresh_token : The refresh_token issued to you when the user authorized your access to their account. REQUIRED

          Response :
          true if successful, error string if not
         */
        $postfields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token
        );

        $tokens = $this->http_post("/oauth/token", $postfields);

        if (!empty($tokens["access_token"])) {
            $this->access_token = $tokens["access_token"];
            $this->refresh_token = $tokens["refresh_token"];
            $this->expires_in = $tokens["expires_in"];
            return true;
        } else {
            return $tokens["error"];
        }
    }

    public function revoke_authorization($token)
    {
        /*
          String token : Either the refresh_token or access_token for the authorization you wish to revoke. REQUIRED

          Response :
          true if successful, error string if not
         */
        $postfields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'token' => $token
        );

        return $this->http_post("/oauth/token/revoke", $postfields);
    }

    public function revoke_profile($profile_id)
    {
        /*
          String profile_id : The profile_id of the profile you wish to revoke access to. REQUIRED
         */
        return $this->http_post("/" . self::API_VERSION . "/profiles/" . $profile_id . "/revoke");
    }

    public function get_account()
    {
        /*
          returns $result - info for the user logged in. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/account");
    }


    public function get_userinfo()
    {
        /*
          returns $result - userinfo for the user logged in. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/userinfo");
    }

    public function get_profiles()
    {
        /*
          returns $result - list of all the authenticated user's calendar profiles. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/profiles");
    }

    public function list_calendars()
    {
        /*
          returns $result - Array of calendars. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/calendars");
    }

    public function read_events($params)
    {
        /*
          Date from : The minimum date from which to return events. Defaults to 16 days in the past. OPTIONAL
          Date to : The date to return events up until. Defaults to 201 days in the future. OPTIONAL
          String tzid : A string representing a known time zone identifier from the IANA Time Zone Database. REQUIRED
          Boolean include_deleted : Indicates whether to include or exclude events that have been deleted. Defaults to excluding deleted events. OPTIONAL
          Boolean include_moved: Indicates whether events that have ever existed within the given window should be included or excluded from the results. Defaults to only include events currently within the search window. OPTIONAL
          Time last_modified : The Time that events must be modified on or after in order to be returned. Defaults to including all events regardless of when they were last modified. OPTIONAL
          Boolean include_managed : Indiciates whether events that you are managing for the account should be included or excluded from the results. Defaults to include only non-managed events. OPTIONAL
          Boolean only_managed : Indicates whether only events that you are managing for the account should be included in the results. OPTIONAL
          Array calendar_ids : Restricts the returned events to those within the set of specified calendar_ids. Defaults to returning events from all of a user's calendars. OPTIONAL
          Boolean localized_times : Indicates whether the events should have their start and end times returned with any available localization information. Defaults to returning start and end times as simple Time values. OPTIONAL
          Boolean include_geo : Indicates whether the events should have their location's latitude and longitude returned where available. OPTIONAL

          returns $result - Array of events
         */
        $url = $this->api_url("/" . self::API_VERSION . "/events");

        return new PagedResultIterator($this, "events", $this->get_auth_headers(), $url, $this->url_params($params));
    }

    public function free_busy($params)
    {
        /*
          Date from : The minimum date from which to return free-busy information. Defaults to 16 days in the past. OPTIONAL
          Date to : The date to return free-busy information up until. Defaults to 201 days in the future. OPTIONAL
          String tzid : A string representing a known time zone identifier from the IANA Time Zone Database. REQUIRED
          Boolean include_managed : Indiciates whether events that you are managing for the account should be included or excluded from the results. Defaults to include only non-managed events. OPTIONAL
          Array calendar_ids : Restricts the returned free-busy information to those within the set of specified calendar_ids. Defaults to returning free-busy information from all of a user's calendars. OPTIONAL
          Boolean localized_times : Indicates whether the free-busy information should have their start and end times returned with any available localization information. Defaults to returning start and end times as simple Time values. OPTIONAL

          returns $result - Array of events
         */
        $url = $this->api_url("/" . self::API_VERSION . "/free_busy");

        return new PagedResultIterator($this, "free_busy", $this->get_auth_headers(), $url, $this->url_params($params));
    }

    public function upsert_event($params)
    {
        /*
          calendar_id : The calendar_id of the calendar you wish the event to be added to. REQUIRED
          String event_id : The String that uniquely identifies the event. REQUIRED
          String summary : The String to use as the summary, sometimes referred to as the name, of the event. REQUIRED
          String description : The String to use as the description, sometimes referred to as the notes, of the event. REQUIRED
          String tzid : A String representing a known time zone identifier from the IANA Time Zone Database. OPTIONAL
          Time start: The start time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
          Time end: The end time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
          String location.description : The String describing the event's location. OPTIONAL
          String location.lat : The String describing the event's latitude. OPTIONAL
          String location.long : The String describing the event's longitude. OPTIONAL
          Array reminders : An array of arrays detailing a length of time and a quantity. OPTIONAL
                            for example: array(array("minutes" => 30), array("minutes" => 1440))
          Boolean reminders_create_only: A Boolean specifying whether reminders should only be applied when creating an event. OPTIONAL
          String transparency : The transparency of the event. Accepted values are "transparent" and "opaque". OPTIONAL
          Array attendees : An array of "invite" and "reject" arrays which are lists of attendees to invite and remove from the event. OPTIONAL
                            for example: array("invite" => array(array("email" => "new_invitee@test.com", "display_name" => "New Invitee"))
                                               "reject" => array(array("email" => "old_invitee@test.com", "display_name" => "Old Invitee")))

          returns true on success, associative array of errors on failure
         */
        $postfields = array(
            'event_id' => $params['event_id'],
            'summary' => $params['summary'],
            'description' => $params['description'],
            'start' => $params['start'],
            'end' => $params['end']
        );

        if (!empty($params['tzid'])) {
            $postfields['tzid'] = $params['tzid'];
        }
        if (!empty($params['location'])) {
            $postfields['location'] = $params['location'];
        }
        if(!empty($params['reminders'])) {
            $postfields['reminders'] = $params['reminders'];
        }
        if(!empty($params['reminders_create_only'])) {
            $postfields['reminders_create_only'] = $params['reminders_create_only'];
        }
        if(!empty($params['transparency'])) {
            $postfields['transparency'] = $params['transparency'];
        }
        if(!empty($params['attendees'])) {
            $postfields['attendees'] = $params['attendees'];
        }

        return $this->http_post("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postfields);
    }

    public function delete_event($params)
    {
        /*
          calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
          String event_id : The String that uniquely identifies the event. REQUIRED

          returns true on success, associative array of errors on failure
         */
        $postfields = array('event_id' => $params['event_id']);

        return $this->http_delete("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postfields);
    }

    public function create_channel($params)
    {
        /*
          String callback_url : The URL that is notified whenever a change is made. REQUIRED

          returns $result - Details of new channel. Details are available in the Cronofy API Documentation
        */
        $postfields = array('callback_url' => $params['callback_url']);

        if(!empty($params['filters'])) {
            $postfields['filters'] = $params['filters'];
        }

        return $this->http_post("/" . self::API_VERSION . "/channels", $postfields);
    }

    public function list_channels()
    {
        /*
          returns $result - Array of channels. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/channels");
    }

    public function close_channel($params)
    {
        /*
          channel_id : The ID of the channel to be closed. REQUIRED

          returns $result - Array of channels. Details are available in the Cronofy API Documentation
         */
        return $this->http_delete("/" . self::API_VERSION . "/channels/" . $params['channel_id']);
    }

    public function delete_external_event($params)
    {
        /*
          calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
          String event_uid : The String that uniquely identifies the event. REQUIRED

          returns true on success, associative array of errors on failure
         */
        $postfields = array('event_uid' => $params['event_uid']);

        return $this->http_delete("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postfields);
    }

    public function authorize_with_service_account($params)
    {
        /*
          email : The email of the user to be authorized. REQUIRED
          scope : The scopes to authorize for the user. REQUIRED
          callback_url : The URL to return to after authorization. REQUIRED
         */
        if (isset($params["scope"]) && gettype($params["scope"]) == "array") {
            $params["scope"] = join(" ", $params["scope"]);
        }

        return $this->http_post("/" . self::API_VERSION . "/service_account_authorizations", $params);
    }

    public function elevated_permissions($params)
    {
        /*
          permissions : The permissions to elevate to. Should be in an array of `array($calendar_id, $permission_level)`. REQUIRED
          redirect_uri : The application's redirect URI. REQUIRED
         */
        return $this->http_post("/" . self::API_VERSION . "/permissions", $params);
    }

    public function create_calendar($params)
    {
        /*
          profile_id : The ID for the profile on which to create the calendar. REQUIRED
          name : The name for the created calendar. REQUIRED
         */
        return $this->http_post("/" . self::API_VERSION . "/calendars", $params);
    }

    public function resources()
    {
      /*
        returns $result - Array of resources. Details
        are available in the Cronofy API Documentation
       */
      return $this->http_get('/' . self::API_VERSION . "/resources");
    }

    public function change_participation_status($params)
    {
        /*
          calendar_id : The ID of the calendar holding the event. REQUIRED
          event_uid : The UID of the event to chang ethe participation status of. REQUIRED
          status : The new participation status for the event. Accepted values are: accepted, tentative, declined. REQUIRED
         */
        $postfields = array(
            "status" => $params["status"]
        );

        return $this->http_post("/" . self::API_VERSION . "/calendars/" . $params["calendar_id"] . "/events/" . $params["event_uid"] . "/participation_status", $postfields);
    }

    public function availability($params)
    {
        /*
          participants : An array of the groups of participants whose availability should be taken into account. REQUIRED
                         for example: array(
                                        array("members" => array(
                                          array("sub" => "acc_567236000909002"),
                                          array("sub" => "acc_678347111010113")
                                        ), "required" => "all")
                                      )
          required_duration : Duration that an available period must last to be considered viable. REQUIRED
                         for example: array("minutes" => 60)
          available_periods : An array of available periods within which suitable matches may be found. REQUIRED
                         for example: array(
                                        array("start" => "2017-01-01T09:00:00Z", "end" => "2017-01-01T18:00:00Z"),
                                        array("start" => "2017-01-02T09:00:00Z", "end" => "2017-01-02T18:00:00Z")
                                      )
         */
        $postfields = array(
            "participants" => $params["participants"],
            "required_duration" => $params["required_duration"],
            "available_periods" => $params["available_periods"]
        );

        return $this->http_post("/" . self::API_VERSION . "/availability", $postfields);
    }

    public function real_time_scheduling($params)
    {
        /*
          oauth: An object of redirect_uri and scope following the event creation
                 for example: array(
                                "redirect_uri" => "http://test.com/",
                                "scope" => "test_scope"
                              )
          event: An object with an event's details
                 for example: array(
                                "event_id" => "test_event_id",
                                "summary" => "Add to Calendar test event",
                              )
          availability: An object holding the event's availability information
                 for example: array(
                                "participants" => array(
                                  array(
                                    "members" => array(
                                      array(
                                        "sub" => "acc_567236000909002"
                                        "calendar_ids" => array("cal_n23kjnwrw2_jsdfjksn234")
                                      )
                                    ),
                                    "required" => "all"
                                  )
                                ),
                                "required_duration" => array(
                                  "minutes" => 60
                                ),
                                "available_periods" => array(
                                  array(
                                    "start" => "2017-01-01T09:00:00Z",
                                    "end" => "2017-01-01T17:00:00Z"
                                  )
                                )
                              )
          target_calendars: An object holding the calendars for the event to be inserted into
                  for example: array(
                    array(
                      "sub" => "acc_567236000909002",
                      "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
                    )
                  )
          tzid: the timezone to create the event in
                for example:  'Europe/London'
         */

        $postfields = array(
          "client_id" => $this->client_id,
          "client_secret" => $this->client_secret,
          "oauth" => $params["oauth"],
          "event" => $params["event"],
          "availability" => $params["availability"],
          "target_calendars" => $params["target_calendars"],
          "tzid" => $params["tzid"],
        );

        return $this->http_post("/" . self::API_VERSION . "/real_time_scheduling", $postfields);
    }

    public function add_to_calendar($params)
    {
        /*
          oauth: An object of redirect_uri and scope following the event creation
                 for example: array(
                                "redirect_uri" => "http://test.com/",
                                "scope" => "test_scope"
                              )
          event: An object with an event's details
                 for example: array(
                                "event_id" => "test_event_id",
                                "summary" => "Add to Calendar test event",
                                "start" => "2017-01-01T12:00:00Z",
                                "end" => "2017-01-01T15:00:00Z"
                              )
         */

        $postfields = array(
          "client_id" => $this->client_id,
          "client_secret" => $this->client_secret,
          "oauth" => $params["oauth"],
          "event" => $params["event"],
        );

        return $this->http_post("/" . self::API_VERSION . "/add_to_calendar", $postfields);
    }

    public function create_smart_invite($params)
    {
        /*
          Array event: An object with an event's details REQUIRED
                 for example: array(
                                "summary" => "Add to Calendar test event",
                                "start" => "2017-01-01T12:00:00Z",
                                "end" => "2017-01-01T15:00:00Z"
                              )
          Array recipient: An object with recipient details REQUIRED
                     for example: array(
                         "email" => "example@example.com"
                     )
          String smart_invite_id: A string representing the id for the smart invite. REQUIRED
          String callback_url : The URL that is notified whenever a change is made. REQUIRED
         */

        $postfields = array(
          "recipient" => $params["recipient"],
          "event" => $params["event"],
          "smart_invite_id" => $params["smart_invite_id"],
          "callback_url" => $params["callback_url"],
        );

        return $this->api_key_http_post("/" . self::API_VERSION . "/smart_invites", $postfields);
    }

    public function cancel_smart_invite($params)
    {
        /*
          Array recipient: An object with recipient details REQUIRED
                     for example: array(
                         "email" => "example@example.com"
                     )
          String smart_invite_id: A string representing the id for the smart invite. REQUIRED
         */

        $postfields = array(
          "recipient" => $params["recipient"],
          "smart_invite_id" => $params["smart_invite_id"],
          "method" => "cancel",
        );

        return $this->api_key_http_post("/" . self::API_VERSION . "/smart_invites", $postfields);
    }

    public function get_smart_invite($smart_invite_id, $recipient_email)
    {
        /*
          String smart_invite_id: A string representing the id for the smart invite. REQUIRED
          String recipient_email: A string representing the email of the recipient to get status for. REQUIRED
         */

        $url_params = array(
            "smart_invite_id" => $smart_invite_id,
            "recipient_email" => $recipient_email,
        );

        return $this->api_key_http_get("/" . self::API_VERSION . "/smart_invites", $url_params);
    }

    private function api_url($path)
    {
        return $this->api_root_url . $path;
    }

    private function url_params($params)
    {
        if (count($params) == 0) {
            return "";
        }
        $str_params = array();

        foreach ($params as $key => $val) {
            if(gettype($val) == "array"){
                for($i = 0; $i < count($val); $i++){
                    array_push($str_params, $key . "[]=" . urlencode($val[$i]));
                }
            } else {
                array_push($str_params, $key . "=" . urlencode($val));
            }
        }

        return "?" . join("&", $str_params);
    }

    private function get_api_key_auth_headers($with_content_headers = false)
    {
        $headers = array();

        $headers[] = 'Authorization: Bearer ' . $this->client_secret;
        $headers[] = 'Host: ' . $this->host_domain;

        if ($with_content_headers) {
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        return $headers;
    }

    private function get_auth_headers($with_content_headers = false)
    {
        $headers = array();

        $headers[] = 'Authorization: Bearer ' . $this->access_token;
        $headers[] = 'Host: ' . $this->host_domain;

        if ($with_content_headers) {
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        return $headers;
    }

    private function parsed_response($response)
    {
        $json_decoded = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            return $response;
        }

        return $json_decoded;
    }

    public function handle_response($result, $status_code)
    {
        if ($status_code >= 200 && $status_code < 300) {
            return $this->parsed_response($result);
        }

        throw new CronofyException($this->http_codes[$status_code], $status_code, $this->parsed_response($result));
    }

    private $http_codes = array(
      100 => 'Continue',
      101 => 'Switching Protocols',
      102 => 'Processing',
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      207 => 'Multi-Status',
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      306 => 'Switch Proxy',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Long',
      415 => 'Unsupported Media Type',
      416 => 'Requested Range Not Satisfiable',
      417 => 'Expectation Failed',
      418 => 'I\'m a teapot',
      422 => 'Unprocessable Entity',
      423 => 'Locked',
      424 => 'Failed Dependency',
      425 => 'Unordered Collection',
      426 => 'Upgrade Required',
      449 => 'Retry With',
      450 => 'Blocked by Windows Parental Controls',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Timeout',
      505 => 'HTTP Version Not Supported',
      506 => 'Variant Also Negotiates',
      507 => 'Insufficient Storage',
      509 => 'Bandwidth Limit Exceeded',
      510 => 'Not Extended'
    );
}

class PagedResultIterator
{
  private $cronofy;
  private $items_key;
  private $auth_headers;
  private $url;
  private $url_params;

  public function __construct($cronofy, $items_key, $auth_headers, $url, $url_params){
    $this->cronofy = $cronofy;
    $this->items_key = $items_key;
    $this->auth_headers = $auth_headers;
    $this->url = $url;
    $this->url_params = $url_params;
    $this->first_page = $this->get_page($url, $url_params);
  }

  public function each(){
    $page = $this->first_page;

    for($i = 0; $i < count($page[$this->items_key]); $i++){
      yield $page[$this->items_key][$i];
    }

    while(isset($page["pages"]["next_page"])){
      $page = $this->get_page($page["pages"]["next_page"]);

      for($i = 0; $i < count($page[$this->items_key]); $i++){
        yield $page[$this->items_key][$i];
      }
    }
  }

  private function get_page($url, $url_params=""){
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url.$url_params);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->auth_headers);
    curl_setopt($curl, CURLOPT_USERAGENT, Cronofy::USERAGENT);
    // empty string means send all supported encoding types
    curl_setopt($curl, CURLOPT_ENCODING, '');
    $result = curl_exec($curl);
    if (curl_errno($curl) > 0) {
      throw new CronofyException(curl_error($curl), 2);
    }
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $this->cronofy->handle_response($result, $status_code);
  }
}
