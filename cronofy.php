<?php

class CronofyException extends Exception
{

}

class Cronofy
{

    const USERAGENT = 'Cronofy PHP 1.0';
    const API_ROOT_URL = 'http://local.cronofy.com';
    const API_VERSION = 'v1';

    var $client_id;
    var $client_secret;
    var $access_token;
    var $refresh_token;

    function __construct($client_id = false, $client_secret = false, $access_token = false, $refresh_token = false)
    {
        if (!function_exists('curl_init')) {
            throw new CronofyException("missing cURL extension", 1);
        }

        if (!empty($client_id)) {
            $this->client_id = $client_id;
        }
        if (!empty($client_secret)) {
            $this->client_secret = $client_secret;
        }
        if (!empty($access_token)) {
            $this->access_token = $access_token;
        }
        if (!empty($refresh_token)) {
            $this->refresh_token = $refresh_token;
        }
    }

    function http_get($method, array $params = array())
    {
        $url = $this->api_url($method);
        $url .= $this->url_params($params);

        if (filter_var($url, FILTER_VALIDATE_URL)===false) {
            throw new CronofyException('invalid URL');
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->get_auth_headers());
        curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);
        $result = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new CronofyException(curl_error($curl), 2);
        }
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $this->handle_response($result, $status_code);
    }

    function http_post($method, array $params = array())
    {
        $url = $this->api_url($method);

        if (filter_var($url, FILTER_VALIDATE_URL)===false) {
            throw new CronofyException('invalid URL');
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->get_auth_headers(true));
        curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        $result = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new CronofyException(curl_error($curl), 3);
        }
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $this->handle_response($result, $status_code);
    }

    function http_delete($method, array $params = array())
    {
        $url = $this->api_url($method);

        if (filter_var($url, FILTER_VALIDATE_URL)===false) {
            throw new CronofyException('invalid URL');
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->get_auth_headers(true));
        curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        $result = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new CronofyException(curl_error($curl), 4);
        }
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $this->handle_response($result, $status_code);
    }

    function getAuthorizationURL($params)
    {
        /*
          Array $params : An array of additional paramaters
          redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
          scope : An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
          state : String A value that will be returned to you unaltered along with the user's authorization request decision. OPTIONAL
          avoid_linking : Boolean when true means we will avoid linking calendar accounts together under one set of credentials. OPTIONAL

          Response :
          String $url : The URL to authorize your access to the Cronofy API
         */

        $scope_list = join(" ", $params['scope']);

        $url = "https://app.cronofy.com/oauth/authorize?response_type=code&client_id=" . $this->client_id . "&redirect_uri=" . urlencode($params['redirect_uri']) . "&scope=" . $scope_list;
        if (!empty($params['state'])) {
            $url.="&state=" . $params['state'];
        }
        if (!empty($params['avoid_linking'])) {
            $url.="&avoid_linking=" . $params['avoid_linking'];
        }
        return $url;
    }

    function request_token($params)
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
            return true;
        } else {
            return $tokens["error"];
        }
    }

    function refresh_token()
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
            return true;
        } else {
            return $tokens["error"];
        }
    }

    function revoke_authorization($token)
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

    function get_account()
    {
        /*
          returns $result - info for the user logged in. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/account");
    }

    function get_profiles()
    {
        /*
          returns $result - list of all the authenticated user's calendar profiles. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/profiles");
    }

    function list_calendars()
    {
        /*
          returns $result - Array of calendars. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/calendars");
    }

    function read_events($params)
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

          returns $result - Array of events
         */
        return $this->http_get("/" . self::API_VERSION . "/events", $params);
    }

    function free_busy($params)
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
        return $this->http_get("/" . self::API_VERSION . "/free_busy", $params);
    }

    function upsert_event($params)
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
        if (!empty($params['location']['description'])) {
            $postfields['location']['description'] = $params['location']['description'];
        }

        return $this->http_post("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postfields);
    }

    function delete_event($params)
    {
        /*
          calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
          String event_id : The String that uniquely identifies the event. REQUIRED

          returns true on success, associative array of errors on failure
         */
        $postfields = array('event_id' => $params['event_id']);

        return $this->http_delete("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postfields);
    }

    function create_channel($params){
        /*
          String callback_url : The URL that is notified whenever a change is made. REQUIRED

          returns $result - Details of new channel. Details are available in the Cronofy API Documentation
        */
        $postfields = array('callback_url' => $params['callback_url']);

        return $this->http_post("/" . self::API_VERSION . "/channels", $postfields);
    }

    function list_channels(){
        /*
          returns $result - Array of channels. Details are available in the Cronofy API Documentation
         */
        return $this->http_get("/" . self::API_VERSION . "/channels");
    }

    function close_channel($params){
        /*
          channel_id : The ID of the channel to be closed. REQUIRED

          returns $result - Array of channels. Details are available in the Cronofy API Documentation
         */
        return $this->http_delete("/" . self::API_VERSION . "/channels/" . $params['channel_id']);
    }

    function delete_external_event($params)
    {
        /*
          calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
          String event_uid : The String that uniquely identifies the event. REQUIRED

          returns true on success, associative array of errors on failure
         */
        $postfields = array('event_uid' => $params['event_uid']);

        return $this->http_delete("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postfields);
    }
    
    function authorize_with_service_account($params){
        /*
          email : The email of the user to be authorized. REQUIRED
          scope : The scopes to authorize for the user. REQUIRED
          callback_url : The URL to return to after authorization. REQUIRED
         */
        if(isset($params["scope"]) && gettype($params["scope"]) == "array") {
            $params["scope"] = join(" ", $params["scope"]);
        }

        return $this->http_post("/" . self::API_VERSION . "/service_account_authorizations", $params);
    }

    private function api_url($method){
        return self::API_ROOT_URL . $method;
    }

    private function url_params($params){
        if(count($params) == 0){
            return "";
        }
        $str_params = array();

        foreach($params as $key => $val){
            array_push($str_params, $key . "=" . urlencode($val));
        }

        return "?" . join("&", $str_params);
    }

    private function get_auth_headers($with_content_headers = false){
        $headers = array();

        $headers[] = 'Authorization: Bearer ' . $this->access_token;
        $headers[] = 'Host: api.cronofy.com';

        if($with_content_headers){
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        return $headers;
    }

    function parsed_response($response){
      $json_decoded = json_decode($response, true);

      if(json_last_error() != JSON_ERROR_NONE) {
        return $response;
      }

      return $json_decoded;
    }

    function handle_response($result, $status_code){
      switch($status_code){
        case 400:
          throw new CronofyException("Bad request", 400);
          break;
        case 401:
          throw new CronofyException("Unauthorized", 403);
          break;
        case 403:
          throw new CronofyException("Forbidden", 403);
          break;
        case 404:
          throw new CronofyException("Not found", 404);
          break;
        case 422:
          throw new CronofyException("Unprocessable", 422);
          break;
        case 429:
          throw new CronofyException("Too many requests", 429);
          break;
        case 500:
          throw new CronofyException("Server error", 500);
          break;
      }

      return $this->parsed_response($result);
    }
}
