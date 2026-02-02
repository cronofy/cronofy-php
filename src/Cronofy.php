<?php

declare(strict_types=1);

namespace Cronofy;

use Cronofy\Batch\BatchRequest;
use Cronofy\Exception\CronofyException;
use Cronofy\Exception\PartialBatchFailureException;
use Cronofy\Http\CurlRequest;
use Cronofy\Batch\Batch;
use Cronofy\Batch\BatchResponse;
use Cronofy\Batch\BatchResult;

class Cronofy
{
    const USERAGENT = 'Cronofy PHP 1.0.0';
    const API_VERSION = 'v1';

    public $apiRootUrl;
    public $appRootUrl;
    public $hostDomain;

    public $clientId;
    public $clientSecret;
    public $accessToken;
    public $refreshToken;
    public $expiresIn;
    public $tokens;
    public $httpClient;

    /**
     * @param array $config
     * - client_id
     * - client_secret
     * - access_token
     * - refresh_token
     * - expires_in
     * - http_client
     * - data_center
     */
    public function __construct($config = [])
    {
        if (!function_exists('curl_init')) {
            throw new CronofyException("missing cURL extension", 1);
        }

        if (!empty($config["client_id"])) {
            $this->clientId = $config["client_id"];
        }
        if (!empty($config["client_secret"])) {
            $this->clientSecret = $config["client_secret"];
        }
        if (!empty($config["access_token"])) {
            $this->accessToken = $config["access_token"];
        }
        if (!empty($config["refresh_token"])) {
            $this->refreshToken = $config["refresh_token"];
        }
        if (!empty($config["expires_in"])) {
            $this->expiresIn = $config["expires_in"];
        }

        if (!empty($config["http_client"])) {
            $this->httpClient = $config["http_client"];
        } else {
            $this->httpClient = new CurlRequest(self::USERAGENT);
        }

        $this->setUrls(isset($config["data_center"]) ? $config["data_center"] : false);
    }

    private function setUrls($data_center = false)
    {
        $data_center_addin = $data_center ? '-' . $data_center : '';

        $this->apiRootUrl = "https://api$data_center_addin.cronofy.com";
        $this->appRootUrl = "https://app$data_center_addin.cronofy.com";
        $this->hostDomain = "api$data_center_addin.cronofy.com";
    }

    private function baseHttpGet($path, array $auth_headers, array $params)
    {
        $url = $this->apiUrl($path);
        $url .= $this->urlParams($params);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new CronofyException('invalid URL');
        }

        list($result, $status_code) = $this->httpClient->httpGet($url, $auth_headers);

        return $this->handleResponse($result, $status_code);
    }

    private function apiKeyHttpGet($path, array $params = [])
    {
        return $this->baseHttpGet($path, $this->getApiKeyAuthHeaders(), $params);
    }

    private function httpGet($path, array $params = [])
    {
        return $this->baseHttpGet($path, $this->getAuthHeaders(), $params);
    }

    private function baseHttpPost($path, $auth_headers, array $params = [])
    {
        $url = $this->apiUrl($path);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new CronofyException('invalid URL');
        }

        list($result, $status_code) = $this->httpClient->httpPost($url, $params, $auth_headers);

        return $this->handleResponse($result, $status_code);
    }

    private function httpPost($path, array $params = [])
    {
        return $this->baseHttpPost($path, $this->getAuthHeaders(true), $params);
    }

    private function apiKeyHttpPost($path, array $params = [])
    {
        return $this->baseHttpPost($path, $this->getApiKeyAuthHeaders(true), $params);
    }

    private function baseHttpDelete($path, $auth_headers, array $params = [])
    {
        $url = $this->apiUrl($path);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new CronofyException('invalid URL');
        }

        list($result, $status_code) = $this->httpClient->httpDelete($url, $params, $auth_headers);

        return $this->handleResponse($result, $status_code);
    }

    private function httpDelete($path, array $params = [])
    {
        return $this->baseHttpDelete($path, $this->getAuthHeaders(true), $params);
    }

    /**
     * @param array $params An array of additional parameters
     * redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
     * scope : An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
     * delegated_scope : Array. An array of scopes to be granted that will be allowed to be granted to the account's users. OPTIONAL
     * state : String A value that will be returned to you unaltered along with the user's authorization request decision. OPTIONAL
     * avoid_linking : Boolean when true means we will avoid linking calendar accounts together under one set of credentials. OPTIONAL
     * link_token : String The link token to explicitly link to a pre-existing account. OPTIONAL
     * @return string The URL to authorize your access to the Cronofy API
     */
    public function getAuthorizationURL($params)
    {
        $scope_list = rawurlencode(join(" ", $params['scope']));

        $url = $this->appRootUrl . "/oauth/authorize?response_type=code&client_id="
            . $this->clientId . "&redirect_uri=" . urlencode($params['redirect_uri']) . "&scope=" . $scope_list;

        if (!empty($params['state'])) {
            $url .= "&state=" . $params['state'];
        }
        if (!empty($params['avoid_linking'])) {
            $url .= "&avoid_linking=" . $params['avoid_linking'];
        }
        if (!empty($params['link_token'])) {
            $url .= "&link_token=" . $params['link_token'];
        }
        if (!empty($params['delegated_scope'])) {
            $url .= "&delegated_scope=" . rawurlencode(join(" ", $params['delegated_scope']));
        }
        if (!empty($params['provider_name'])) {
            $url .= "&provider_name=" . $params['provider_name'];
        }

        return $url;
    }

    /**
     * @param array $params An array of additional parameters
     * redirect_uri : String. The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
     * scope : Array. An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
     * delegated_scope : Array. An array of scopes to be granted that will be allowed to be granted to the account's users. REQUIRED
     * state : String. A value that will be returned to you unaltered along with the user's authorization request decsion. OPTIONAL
     * @return string The URL to authorize your enterprise connect access to the Cronofy API
     */
    public function getEnterpriseConnectAuthorizationUrl($params)
    {
        $scope_list = rawurlencode(join(" ", $params['scope']));
        $delegated_scope_list = rawurlencode(join(" ", $params['delegated_scope']));

        $url = $this->appRootUrl . "/enterprise_connect/oauth/authorize?response_type=code&client_id="
            . $this->clientId . "&redirect_uri=" . urlencode($params['redirect_uri']) . "&scope="
            . $scope_list . "&delegated_scope=" . $delegated_scope_list;

        if (!empty($params['state'])) {
            $url .= "&state=" . rawurlencode($params['state']);
        }
        return $url;
    }

    /**
     * @param array $params An array of additional parameters
     * permissions : Array. An array of permissions the token will be granted. REQUIRED
     * subs:  : Array. An array of subs to identify the accounts the token is allowed to access  REQUIRED
     * origin: String he Origin of the application where the Element will be used. REQUIRED
     * @return array
     * element_token.permissions : The array of permissions granted.
     * element_token.origin : The permitted Origin the token can be used with.
     * element_token.token : The token that is passed to Elements to authenticate them.
     * element_token.expires_in : The number of seconds the token can be used for.
     */
    public function requestElementToken($params)
    {
        $postfields = [
            "version" => "1",
            "permissions" => $params['permissions'],
            'subs' => $params['subs'],
            "origin" => $params['origin']
        ];

        return $this->apiKeyHttpPost("/v1/element_tokens", $postfields);
    }

    /**
     * @param array $params An array of additional parameters
     * redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
     * code: The short-lived, single-use code issued to you when the user authorized your access to their account as part of an Authorization  REQUIRED
     * @return true|string true if successful, error string if not
     */
    public function requestToken($params)
    {
        $postfields = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $params['code'],
            'redirect_uri' => $params['redirect_uri']
        ];

        $tokens = $this->httpPost("/oauth/token", $postfields);

        if (!empty($tokens["access_token"])) {
            $this->accessToken = $tokens["access_token"];
            $this->refreshToken = $tokens["refresh_token"];
            $this->expiresIn = $tokens["expires_in"];
            $this->tokens = $tokens;
            return true;
        } else {
            return $tokens["error"];
        }
    }

    /**
     * @param array $params An array of additional parameters
     * profile_id : String. This specifies the ID of the profile you wish to get delegated authorization through.
     * email : String. The email address of the account or resource to receive delegated access to.
     * callback_url: String. The URL to callback with the result of the delegated access request.
     * scope : array. The scope of the privileges you want the eventual access_token to grant.
     * state : String. A value that will be returned to you unaltered along with the delegated authorization request decision.
     */
    public function requestDelegatedAuthorization($params)
    {
        if (isset($params["scope"]) && gettype($params["scope"]) == "array") {
            $params["scope"] = join(" ", $params["scope"]);
        }

        return $this->httpPost('/' . self::API_VERSION . '/delegated_authorizations', $params);
    }


    /**
     * @return mixed The link_token to explicitly link to a pre-existing account. Details are available in the Cronofy API Documentation
     */
    public function requestLinkToken()
    {
        return $this->httpPost('/' . self::API_VERSION . '/link_tokens');
    }

    /**
     * @return true|string true if successful, error string if not
     */
    public function refreshToken()
    {
        $postfields = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken
        ];

        $tokens = $this->httpPost("/oauth/token", $postfields);

        if (!empty($tokens["access_token"])) {
            $this->accessToken = $tokens["access_token"];
            $this->refreshToken = $tokens["refresh_token"];
            $this->expiresIn = $tokens["expires_in"];
            $this->tokens = $tokens;
            return true;
        } else {
            return $tokens["error"];
        }
    }

    /**
     * @param array $params An array of additional parameters
     * String token : Either the refresh_token or access_token for the authorization you wish to revoke. OPTIONAL
     * String sub : The sub value for the account you wish to revoke. OPTIONAL
     * @return true|string true if successful, error string if not
     */
    public function revokeAuthorization($params)
    {
        $postfields = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if (is_string($params)) {
            // in version <= 1.1.6 the method only supported token
            $params = ['token' => $params];
        }

        if (array_key_exists('token', $params)) {
            $postfields['token'] = $params['token'];
        }

        if (array_key_exists('sub', $params)) {
            $postfields['sub'] = $params['sub'];
        }

        return $this->httpPost("/oauth/token/revoke", $postfields);
    }

    /**
     * @param string $profile_id : The profile_id of the profile you wish to revoke access to. REQUIRED
     */
    public function revokeProfile($profile_id)
    {
        return $this->httpPost("/" . self::API_VERSION . "/profiles/" . $profile_id . "/revoke");
    }

    /**
     * @param string $application_calendar_id : String The identifier for the application calendar to create
     * @return true|strring if successful, error string if not
     */
    public function applicationCalendar($application_calendar_id)
    {
        $postfields = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'application_calendar_id' => $application_calendar_id,
        ];

        $application_calendar = $this->httpPost("/v1/application_calendars", $postfields);

        if (!empty($application_calendar["access_token"])) {
            $this->accessToken = $application_calendar["access_token"];
            $this->refreshToken = $application_calendar["refresh_token"];
            $this->expiresIn = $application_calendar["expires_in"];
            $this->tokens = $application_calendar;
            return $application_calendar;
        } else {
            return $application_calendar["error"];
        }
    }

    /**
     * @return mixed info for the user logged in. Details are available in the Cronofy API Documentation
     */
    public function getAccount()
    {
        return $this->httpGet("/" . self::API_VERSION . "/account");
    }

    /**
     * @return mixed userinfo for the user logged in. Details are available in the Cronofy API Documentation
     */
    public function getUserInfo()
    {
        return $this->httpGet("/" . self::API_VERSION . "/userinfo");
    }

    /**
     * @return mixed list of all the authenticated user's calendar profiles. Details are available in the Cronofy API Documentation
     */
    public function getProfiles()
    {
        return $this->httpGet("/" . self::API_VERSION . "/profiles");
    }

    /**
     * @return array Array of calendars. Details are available in the Cronofy API Documentation
     */
    public function listCalendars()
    {
        return $this->httpGet("/" . self::API_VERSION . "/calendars");
    }

    public function listAccessibleCalendars($profileId)
    {
        return $this->httpGet("/" . self::API_VERSION . "/accessible_calendars", ['profile_id' => $profileId]);
    }

    /**
     * @param array $params An array of additional parameters
     * Date from : The minimum date from which to return events. Defaults to 16 days in the past. OPTIONAL
     * Date to : The date to return events up until. Defaults to 201 days in the future. OPTIONAL
     * String tzid : A string representing a known time zone identifier from the IANA Time Zone Database. REQUIRED
     * Boolean include_deleted : Indicates whether to include or exclude events that have been deleted.
     * Defaults to excluding deleted events. OPTIONAL
     * Boolean include_moved: Indicates whether events that have ever existed within the given window should be
     * included or excluded from the results. Defaults to only include events currently within the search window. OPTIONAL
     * Time last_modified : The Time that events must be modified on or after in order to be returned.
     * Defaults to including all events regardless of when they were last modified. OPTIONAL
     * Boolean include_managed : Indiciates whether events that you are managing for the account should be included
     * or excluded from the results. Defaults to include only non-managed events. OPTIONAL
     * Boolean only_managed : Indicates whether only events that you are managing for the account should be included
     * in the results. OPTIONAL
     * Array calendar_ids : Restricts the returned events to those within the set of specified calendar_ids.
     * Defaults to returning events from all of a user's calendars. OPTIONAL
     * Boolean localized_times : Indicates whether the events should have their start and end times returned with any
     * available localization information. Defaults to returning start and end times as simple Time values. OPTIONAL
     * Boolean include_geo : Indicates whether the events should have their location's latitude and longitude
     * returned where available. OPTIONAL
     *
     * @return array Array of events
     */
    public function readEvents($params)
    {
        $url = $this->apiUrl("/" . self::API_VERSION . "/events");

        return new PagedResultIterator($this, "events", $this->getAuthHeaders(), $url, $this->urlParams($params));
    }

    /**
     * @param array $params An array of additional parameters
     * Date from : The minimum date from which to return free-busy information. Defaults to 16 days in the past. OPTIONAL
     * Date to : The date to return free-busy information up until. Defaults to 201 days in the future. OPTIONAL
     * String tzid : A string representing a known time zone identifier from the IANA Time Zone Database. REQUIRED
     * Boolean include_managed : Indiciates whether events that you are managing for the account should be included or
     * excluded from the results. Defaults to include only non-managed events. OPTIONAL
     * Array calendar_ids : Restricts the returned free-busy information to those within the set of specified calendar_ids.
     * Defaults to returning free-busy information from all of a user's calendars. OPTIONAL
     * Boolean localized_times : Indicates whether the free-busy information should have their start and end times returned
     * with any available localization information. Defaults to returning start and end times as simple Time values. OPTIONAL
     *
     * @return array Array of events
     */
    public function freeBusy($params)
    {
        $url = $this->apiUrl("/" . self::API_VERSION . "/free_busy");

        return new PagedResultIterator($this, "free_busy", $this->getAuthHeaders(), $url, $this->urlParams($params));
    }

    /**
     * @param array $params An array of additional parameters
     * calendar_id : The calendar_id of the calendar you wish the event to be added to. REQUIRED
     * String event_id : The String that uniquely identifies the event. REQUIRED
     * String summary : The String to use as the summary, sometimes referred to as the name, of the event. REQUIRED
     * String description : The String to use as the description, sometimes referred to as the notes, of the event. OPTIONAL
     * String tzid : A String representing a known time zone identifier from the IANA Time Zone Database. OPTIONAL
     * Time start: The start time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * Time end: The end time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * String location.description : The String describing the event's location. OPTIONAL
     * String location.lat : The String describing the event's latitude. OPTIONAL
     * String location.long : The String describing the event's longitude. OPTIONAL
     * Array reminders : An array of arrays detailing a length of time and a quantity. OPTIONAL
     *             for example: array(array("minutes" => 30), array("minutes" => 1440))
     * Boolean reminders_create_only: A Boolean specifying whether reminders should only be applied when creating an event. OPTIONAL
     * String transparency : The transparency of the event. Accepted values are "transparent" and "opaque". OPTIONAL
     * String color : The color of the event in calendars which support custom event colors. OPTIONAL
     * Array attendees : An array of "invite" and "reject" arrays which are lists of attendees to invite and remove from the event. OPTIONAL
     *             for example: array("invite" => array(array("email" => "new_invitee@test.com", "display_name" => "New Invitee"))
     *                                 "reject" => array(array("email" => "old_invitee@test.com", "display_name" => "Old Invitee")))
     * String Locale: The locale represents the 2 character code for the language of the content. For example: "en". OPTIONAL unless using Templates
     *
     * @return true|mixed true on success, associative array of errors on failure
     */
    public function upsertEvent($params)
    {
        $postfields = [
            'event_id' => $params['event_id'],
            'summary' => $params['summary'],
            'start' => $params['start'],
            'end' => $params['end']
        ];

        return $this->baseUpsertEvent($postfields, $params);
    }

    /**
     * @param array $params An array of additional parameters
     * calendar_id : The calendar_id of the calendar you wish the event to be added to. REQUIRED
     * String event_uid : The String that uniquely identifies the event. REQUIRED
     * String summary : The String to use as the summary, sometimes referred to as the name, of the event. REQUIRED
     * String description : The String to use as the description, sometimes referred to as the notes, of the event. OPTIONAL
     * String tzid : A String representing a known time zone identifier from the IANA Time Zone Database. OPTIONAL
     * Time start: The start time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * Time end: The end time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * String location.description : The String describing the event's location. OPTIONAL
     * String location.lat : The String describing the event's latitude. OPTIONAL
     * String location.long : The String describing the event's longitude. OPTIONAL
     * Array reminders : An array of arrays detailing a length of time and a quantity. OPTIONAL
     *                 for example: array(array("minutes" => 30), array("minutes" => 1440))
     * Boolean reminders_create_only: A Boolean specifying whether reminders should only be applied when creating an event. OPTIONAL
     * String transparency : The transparency of the event. Accepted values are "transparent" and "opaque". OPTIONAL
     * Array attendees : An array of "invite" and "reject" arrays which are lists of attendees to invite and remove from the event. OPTIONAL
     *                 for example: array("invite" => array(array("email" => "new_invitee@test.com", "display_name" => "New Invitee"))
     *                                     "reject" => array(array("email" => "old_invitee@test.com", "display_name" => "Old Invitee")))
     * @return true|mixed true on success, associative array of errors on failure
     */
    public function upsertExternalEvent($params)
    {
        $postFields = [
            'event_uid' => $params['event_uid'],
            'summary' => $params['summary'],
            'start' => $params['start'],
            'end' => $params['end']
        ];

        return $this->baseUpsertEvent($postFields, $params);
    }

    private function baseUpsertEvent($postFields, $params)
    {
        if (isset($params['description'])) {
            $postFields['description'] = $params['description'];
        }
        if (!empty($params['tzid'])) {
            $postFields['tzid'] = $params['tzid'];
        }
        if (!empty($params['location'])) {
            $postFields['location'] = $params['location'];
        }
        if (!empty($params['reminders'])) {
            $postFields['reminders'] = $params['reminders'];
        }
        if (!empty($params['reminders_create_only'])) {
            $postFields['reminders_create_only'] = $params['reminders_create_only'];
        }
        if (!empty($params['event_private'])) {
            $postFields['event_private'] = $params['event_private'];
        }
        if (!empty($params['transparency'])) {
            $postFields['transparency'] = $params['transparency'];
        }
        if (!empty($params['color'])) {
            $postFields['color'] = $params['color'];
        }
        if (!empty($params['attendees'])) {
            $postFields['attendees'] = $params['attendees'];
        }
        if (!empty($params['conferencing'])) {
            $postFields['conferencing'] = $params['conferencing'];
        }
        if (!empty($params['subscriptions'])) {
            $postFields['subscriptions'] = $params['subscriptions'];
        }
        if (!empty($params['recurrence'])) {
            $postFields['recurrence'] = $params['recurrence'];
        }
        if (!empty($params['locale'])) {
            $postFields['locale'] = $params['locale'];
        }
        if (!empty($params['event_classes'])) {
            $postFields['event_classes'] = $params['event_classes'];
        }

        return $this->httpPost("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
     * String event_id : The String that uniquely identifies the event. REQUIRED
     *
     * @return true|mixed true on success, associative array of errors on failure
     */
    public function deleteEvent($params)
    {
        $postFields = ['event_id' => $params['event_id']];

        return $this->httpDelete("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * delete_all : A Boolean specifying whether all events you are managing for the user should be deleted. When specified must be true. OPTIONAL
     * calendar_ids : An Array specifying the calendars from which to delete all events you are managing for the user.
     * When provided at least one calendar must be specified. OPTIONAL

     * Only one of delete_all or calendar_ids must be provided in the body, if both are set this is considered an error.
     *
     * @return true|mixed true on success, associative array of errors on failure
     */
    public function bulkDeleteEvents($params)
    {
        $postFields = [];

        if (!empty($params['calendar_ids'])) {
            $postFields['calendar_ids'] = $params['calendar_ids'];
        }

        if (!empty($params['delete_all'])) {
            $postFields['delete_all'] = $params['delete_all'];
        }

        return $this->httpDelete("/" . self::API_VERSION . "/events", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
     * String event_uid : The String that uniquely identifies the event. REQUIRED
     *
     * @return true|mixed true on success, associative array of errors on failure
     */
    public function deleteExternalEvent($params)
    {
        $postFields = ['event_uid' => $params['event_uid']];

        return $this->httpDelete("/" . self::API_VERSION . "/calendars/" . $params['calendar_id'] . "/events", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * String callback_url : The URL that is notified whenever a change is made. REQUIRED
     *
     * @return mixed Details of new channel. Details are available in the Cronofy API Documentation
     */
    public function createChannel($params)
    {
        $postFields = ['callback_url' => $params['callback_url']];

        if (!empty($params['filters'])) {
            $postFields['filters'] = $params['filters'];
        }

        return $this->httpPost("/" . self::API_VERSION . "/channels", $postFields);
    }

    /**
     * @return array Array of channels. Details are available in the Cronofy API Documentation
     */
    public function listChannels()
    {
        return $this->httpGet("/" . self::API_VERSION . "/channels");
    }

    /**
     * @param array $params An array of additional parameters
     * channel_id : The ID of the channel to be closed. REQUIRED
     *
     * @return array Array of channels. Details are available in the Cronofy API Documentation
     */
    public function closeChannel($params)
    {
        return $this->httpDelete("/" . self::API_VERSION . "/channels/" . $params['channel_id']);
    }

    /**
     * @param array $params An array of additional parameters
     * email : The email of the user to be authorized. REQUIRED
     * scope : The scopes to authorize for the user. REQUIRED
     * callback_url : The URL to return to after authorization. REQUIRED
     */
    public function authorizeWithServiceAccount($params)
    {
        if (isset($params["scope"]) && gettype($params["scope"]) == "array") {
            $params["scope"] = join(" ", $params["scope"]);
        }

        return $this->httpPost("/" . self::API_VERSION . "/service_account_authorizations", $params);
    }

    /**
     * @param array $params An array of additional parameters
     * permissions : The permissions to elevate to. Should be in an array of `array($calendar_id, $permission_level)`. REQUIRED
     * redirect_uri : The application's redirect URI. REQUIRED
     */
    public function elevatedPermissions($params)
    {
        return $this->httpPost("/" . self::API_VERSION . "/permissions", $params);
    }

    /**
     * @param array $params An array of additional parameters
     * profile_id : The ID for the profile on which to create the calendar. REQUIRED
     * name : The name for the created calendar. REQUIRED
     */
    public function createCalendar($params)
    {
        return $this->httpPost("/" . self::API_VERSION . "/calendars", $params);
    }

    /**
     * @return array Array of resources.
     * Details are available in the Cronofy API Documentation
     */
    public function resources()
    {
        return $this->httpGet('/' . self::API_VERSION . "/resources");
    }

    /**
     * @param array $params An array of additional parameters
     * calendar_id : The ID of the calendar holding the event. REQUIRED
     * event_uid : The UID of the event to chang ethe participation status of. REQUIRED
     * status : The new participation status for the event. Accepted values are: accepted, tentative, declined. REQUIRED
     */
    public function changeParticipationStatus($params)
    {
        $postFields = [
            "status" => $params["status"]
        ];

        return $this->httpPost("/" . self::API_VERSION . "/calendars/" . $params["calendar_id"] . "/events/" . $params["event_uid"] . "/participation_status", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * participants : An array of the groups of participants whose availability should be taken into account. REQUIRED
     *                 for example: array(
     *                             array("members" => array(
     *                                 array("sub" => "acc_567236000909002"),
     *                                 array("sub" => "acc_678347111010113")
     *                             ), "required" => "all")
     *                             )
     * required_duration : Duration that an available period must last to be considered viable. REQUIRED
     *                 for example: array("minutes" => 60)
     *
     * start_interval : Duration that an events can start on for example: array("minutes" => 60)
     * buffer : Buffer to apply before or after events can start
     *                 for example:
     *                     array(
     *                         array("before" => array("minutes" => 30)),
     *                         array("after" => array("minutes" => 30))
     *                     )
     * available_periods : An array of available periods within which suitable matches may be found. REQUIRED
     *                 for example: array(
     *                             array("start" => "2017-01-01T09:00:00Z", "end" => "2017-01-01T18:00:00Z"),
     *                             array("start" => "2017-01-02T09:00:00Z", "end" => "2017-01-02T18:00:00Z")
     *                             )
     */
    public function availability($params)
    {
        $postFields = [
            "participants" => $params["participants"],
            "required_duration" => $params["required_duration"]
        ];

        if (!empty($params["query_periods"])) {
            $postFields["query_periods"] = $params["query_periods"];
        } elseif (!empty($params["available_periods"])) {
            $postFields["available_periods"] = $params["available_periods"];
        }

        if (!empty($params["query_slots"])) {
            $postFields["query_slots"] = $params["query_slots"];
        }

        if (!empty($params["buffer"])) {
            $postFields["buffer"] = $params["buffer"];
        }
        if (!empty($params["max_results"])) {
            $postFields["max_results"] = $params["max_results"];
        }
        if (!empty($params["start_interval"])) {
            $postFields["start_interval"] = $params["start_interval"];
        }
        if (!empty($params["response_format"])) {
            $postFields["response_format"] = $params["response_format"];
        }
        if (!empty($params["include_member_statuses"])) {
            $postFields["include_member_statuses"] = $params["include_member_statuses"];
        }
        if (!empty($params["excluded_events"])) {
            $postFields["excluded_events"] = $params["excluded_events"];
        }

        return $this->apiKeyHttpPost("/" . self::API_VERSION . "/availability", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * oauth: An object of redirect_uri and scope following the event creation
     *         for example: array(
     *                     "redirect_uri" => "http://test.com/",
     *                     "scope" => "test_scope"
     *                     )
     * event: An object with an event's details
     *         for example: array(
     *                     "event_id" => "test_event_id",
     *                     "summary" => "Add to Calendar test event",
     *                     )
     * availability: An object holding the event's availability information
     *         for example: array(
     *                     "participants" => array(
     *                         array(
     *                         "members" => array(
     *                             array(
     *                             "sub" => "acc_567236000909002"
     *                             "calendar_ids" => array("cal_n23kjnwrw2_jsdfjksn234")
     *                             )
     *                         ),
     *                         "required" => "all"
     *                         )
     *                     ),
     *                     "required_duration" => array(
     *                         "minutes" => 60
     *                     ),
     *                     "available_periods" => array(
     *                         array(
     *                         "start" => "2017-01-01T09:00:00Z",
     *                         "end" => "2017-01-01T17:00:00Z"
     *                         )
     *                     )
     *                     )
     * target_calendars: An object holding the calendars for the event to be inserted into
     *         for example: array(
     *         array(
     *             "sub" => "acc_567236000909002",
     *             "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
     *         )
     *         )
     * tzid: the timezone to create the event in
     *     for example:  'Europe/London'
     * formatting: An object indicating how to format the hours. The hour_format property should be h (12-hour format) or H (24-hour format).
     *     for example: array(
     *                     "hour_format" => "h"
     *                     )
     * minimum_notice: a Duration for the minimum amount of time before the first slot offered.
     *     for example: array( "hours" => 2 )
     * event_creation: The event creation mode - "default" (one event per target calendar) or "single" (one event for all attendees)
     * callback_url: Deprecated - use callback_urls.completed_url instead
     *     for example:  'http://www.example.com/callback'
     * callback_urls: URLs to send a request to when certain states occur.
     *     callback_urls.completed_url: A URL to call when the full event details are known.
     *     callback_urls.no_times_suitable_url: A URL to call if the user indicates none of the offered times are suitable.
     *     for example: array(
     *         "completed_url" => "https://example.com/callbacks/scheduled",
     *         "no_times_suitable_url" => "https://example.com/callbacks/unable_to_schedule"
     *     )
     * redirect_urls: URLs to redirect the user to when certain states are reached.
     *     redirect_urls.completed_url: A URL to redirect the user to when the full event details are known.
     *     for example: array(
     *         "completed_url" => "https://example.com/scheduling/thank_you",
     *     )
     */
    public function realTimeScheduling($params)
    {
        $postFields = [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "oauth" => $params["oauth"],
            "event" => $params["event"],
            "availability" => $params["availability"],
            "target_calendars" => $params["target_calendars"],
            "tzid" => $params["tzid"]
        ];

        if (isset($params['minimum_notice'])) {
            $postFields["minimum_notice"] = $params["minimum_notice"];
        }

        if (isset($params['event_creation'])) {
            $postFields["event_creation"] = $params["event_creation"];
        }

        if (!empty($params["formatting"])) {
            $postFields["formatting"] = $params["formatting"];
        }

        if (!empty($params["redirect_urls"])) {
            $postFields["redirect_urls"] = $params["redirect_urls"];
        }

        if (isset($params['callback_url'])) {
            $postFields["callback_urls"] = array("completed_url" => $params["callback_url"]);
        }

        if (!empty($params["callback_urls"])) {
            $postFields["callback_urls"] = $params["callback_urls"];

            # If the deprecated callback_url was passed and the newer callback_urls.complete_url was not passed, modernize the payload.
            if (empty($params["callback_urls"]["completed_url"]) && !empty($params["callback_url"])) {
                $postFields["callback_urls"]["completed_url"] = $params["callback_url"];
            }
        }

        return $this->httpPost("/" . self::API_VERSION . "/real_time_scheduling", $postFields);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function disableRealTimeScheduling($params)
    {

        $id = $params['id'];

        $postFields = [
            'display_message' => $params['display_message']
        ];

        return $this->apiKeyHttpPost("/" . self::API_VERSION . "/real_time_scheduling/" . $id . "/disable", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * oauth: An object of redirect_uri and scope following the event creation
     *         for example: array(
     *                     "redirect_uri" => "http://test.com/",
     *                     "scope" => "test_scope"
     *                     )
     * event: An object with an event's details
     *         for example: array(
     *                     "event_id" => "test_event_id",
     *                     "summary" => "Add to Calendar test event",
     *                     )
     * availability: An object holding the event's availability information
     *     for example: array(
     *             "sequence" => array(
     *                 array(
     *                     "sequence_id" => "123",
     *                     "ordinal" => 1,
     *                     "participants" => array(
     *                         array(
     *                             "members" => array(
     *                                 array(
     *                                     "sub" => "acc_567236000909002",
     *                                     "calendar_ids" => array("cal_n23kjnwrw2_jsdfjksn234")
     *                                 )
     *                             ),
     *                             "required" => "all"
     *                         )
     *                     ),
     *                     "event" => $event,
     *                     "required_duration" => array(
     *                         "minutes" => 60
     *                     ),
     *                 ),
     *             ),
     *             "available_periods" => array(
     *                 array(
     *                     "start" => "2017-01-01T09:00:00Z",
     *                     "end" => "2017-01-01T17:00:00Z"
     *                 )
     *             )
     *         );
     * target_calendars: An object holding the calendars for the event to be inserted into
     *         for example: array(
     *         array(
     *             "sub" => "acc_567236000909002",
     *             "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
     *         )
     *         )
     * tzid: the timezone to create the event in
     *     for example:  'Europe/London'
     */
    public function realTimeSequencing($params)
    {
        $postFields = [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "oauth" => $params["oauth"],
            "event" => $params["event"],
            "availability" => $params["availability"],
            "target_calendars" => $params["target_calendars"],
            "tzid" => $params["tzid"],
        ];

        return $this->httpPost("/" . self::API_VERSION . "/real_time_sequencing", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * oauth: An object of redirect_uri and scope following the event creation
     *         for example: array(
     *                     "redirect_uri" => "http://test.com/",
     *                     "scope" => "test_scope"
     *                     )
     * event: An object with an event's details
     *         for example: array(
     *                     "event_id" => "test_event_id",
     *                     "summary" => "Add to Calendar test event",
     *                     "start" => "2017-01-01T12:00:00Z",
     *                     "end" => "2017-01-01T15:00:00Z"
     *                     )
     */
    public function addToCalendar($params)
    {
        $postFields = [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "oauth" => $params["oauth"],
            "event" => $params["event"],
        ];

        return $this->httpPost("/" . self::API_VERSION . "/add_to_calendar", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * Array event: An object with an event's details REQUIRED
     *         for example: array(
     *                     "summary" => "Add to Calendar test event",
     *                     "start" => "2017-01-01T12:00:00Z",
     *                     "end" => "2017-01-01T15:00:00Z"
     *                     )
     * Array recipient: An object with recipient details REQUIRED
     *             for example: array(
     *                 "email" => "example@example.com"
     *             )
     * String smart_invite_id: A string representing the id for the smart invite. REQUIRED
     * String callback_url : The URL that is notified whenever a change is made. REQUIRED
     * Array organizer: An object with recipient details OPTIONAL
     *             for example: array(
     *                 "name" => "Smart invite organizer"
     *             )
     */
    public function createSmartInvite($params)
    {
        $postFields = [
            "event" => $params["event"],
            "smart_invite_id" => $params["smart_invite_id"],
            "callback_url" => $params["callback_url"],
        ];

        if (!empty($params['organizer'])) {
            $postFields['organizer'] = $params['organizer'];
        }

        if (!empty($params['recipients'])) {
            $postFields['recipients'] = $params['recipients'];
        } else {
            $postFields['recipient'] = $params['recipient'];
        }

        return $this->apiKeyHttpPost("/" . self::API_VERSION . "/smart_invites", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * Array recipient: An object with recipient details REQUIRED
     *             for example: array(
     *                 "email" => "example@example.com"
     *             )
     * String smart_invite_id: A string representing the id for the smart invite. REQUIRED
     */
    public function cancelSmartInvite($params)
    {
        $postFields = [
            "smart_invite_id" => $params["smart_invite_id"],
            "method" => "cancel",
        ];

        if (!empty($params['recipients'])) {
            $postFields['recipients'] = $params['recipients'];
        } else {
            $postFields['recipient'] = $params['recipient'];
        }

        return $this->apiKeyHttpPost("/" . self::API_VERSION . "/smart_invites", $postFields);
    }

    /**
     * @param string $smart_invite_id: A string representing the id for the smart invite. REQUIRED
     * @param string $recipient_email: A string representing the email of the recipient to get status for. REQUIRED
     */
    public function getSmartInvite($smart_invite_id, $recipient_email)
    {
        $urlParams = [
            "smart_invite_id" => $smart_invite_id,
            "recipient_email" => $recipient_email,
        ];

        return $this->apiKeyHttpGet("/" . self::API_VERSION . "/smart_invites", $urlParams);
    }

    /**
     * @param array $params An array of additional parameters
     * Date from: The minimum Date from which to return available periods. OPTIONAL
     * Date to: The Date to return available periods up until.
     * Note that the results will not include available periods occurring on this date. OPTIONAL
     * String tzid: A String representing a known time zone identifier from the IANA Time Zone Database. OPTIONAL
     * Boolean localized_times: A Boolean specifying whether the available periods should have their start and end times
     * returned with any available localization information. If not provided the start and end times will be
     * returned as simple Time values. OPTIONAL
     *
     * @return array Array of available_periods
     */
    public function readAvailablePeriods($params)
    {
        $url = $this->apiUrl("/" . self::API_VERSION . "/available_periods");

        return new PagedResultIterator($this, "available_periods", $this->getAuthHeaders(), $url, $this->urlParams($params));
    }

    /**
     * @param array $params An array of additional parameters
     * String available_period_id: The String that uniquely identifies the available period. The first request made
     * for an available_period_id will create an available period for the account and subsequent requests will
     * update its details. REQUIRED
     * Time start: The start time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * Time end: The end time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * String tzid: A String representing a known time zone identifier from the IANA Time Zone Database.
     */
    public function createAvailablePeriod($params)
    {
        $postfields = array(
            'available_period_id' => $params['available_period_id'],
            'start'               => $params['start'],
            'end'                 => $params['end'],
        );

        if (!empty($params['tzid'])) {
            $postFields['tzid'] = $params['tzid'];
        }

        return $this->httpPost("/" . self::API_VERSION . "/available_periods", $postfields);
    }

    /**
     * @param array $params An array of additional parameters
     * String available_period_id: The String that uniquely identifies the available period. REQUIRED
     *
     * @return true|mixed true on success, associative array of errors on failure
     */
    public function deleteAvailablePeriod($params)
    {
        $postFields = ['available_period_id' => $params["available_period_id"]];

        return $this->httpDelete("/" . self::API_VERSION . "/available_periods/", $postFields);
    }

    public function bulkDeleteAvailablePeriods()
    {
        return $this->httpDelete("/" . self::API_VERSION . "/available_periods", [
            'delete_all' => true,
        ]);
    }

    /**
     * @param string availability_rule_id: A string representing the id for the rule. REQUIRED
     */
    public function getAvailabilityRule($availability_rule_id)
    {
        return $this->httpGet("/" . self::API_VERSION . "/availability_rules/" . $availability_rule_id);
    }

    public function listAvailabilityRules()
    {
        return $this->httpGet("/" . self::API_VERSION . "/availability_rules");
    }

    /**
     * @param string availability_rule_id: A string representing the id for the rule. REQUIRED
     *
     * @return true|mixed true on success, associative array of errors on failure
     */
    public function deleteAvailabilityRule($availability_rule_id)
    {
        return $this->httpDelete("/" . self::API_VERSION . "/availability_rules/" . $availability_rule_id);
    }

    /**
     * @param array rule: An object with an availability rule's details REQUIRED
     *         for example: array(
     *                     "availability_rule_id" => "default",
     *                     "tzid" => "America/Chicago",
     *                     "calendar_ids" => array(
     *                         "cal_123"
     *                     ),
     *                     "weekly_periods" => array(
     *                         array(
     *                             "day" => "monday",
     *                             "start_time" => "09:30",
     *                             "end_time" => "12:30"
     *                         ),
     *                         array(
     *                             "day" => "wednesday",
     *                             "start_time" => "09:30",
     *                             "end_time" => "12:30"
     *                         )
     *                     )
     *                 )
     */
    public function createAvailabilityRule($rule)
    {
        $postFields = [
            "availability_rule_id" => $rule["availability_rule_id"],
            "tzid" => $rule["tzid"],
            "calendar_ids" => $rule["calendar_ids"],
            "weekly_periods" => $rule["weekly_periods"],
        ];

        return $this->httpPost("/" . self::API_VERSION . "/availability_rules", $postFields);
    }

    /**
     * @param array $params An array of additional parameters
     * @return mixed
     */
    public function conferencingServiceAuthorization($params)
    {
        $postFields = [
            'redirect_uri' => $params['redirect_uri'],
        ];

        if (!empty($params['provider_name'])) {
            $postFields['provider_name'] = $params['provider_name'];
        }

        return $this->httpPost("/" . self::API_VERSION . "/conferencing_service_authorizations", $postFields);
    }

    public function executeBatch(Batch $batch): BatchResult
    {
        $requests = $batch->requests();

        $postFields = [
            'batch' => $this->convertBatchRequestsToArray(...$requests),
        ];

        $httpResult = $this->httpPost(
            sprintf('/%s/batch', self::API_VERSION),
            $postFields
        );

        $responses = [];

        foreach ($httpResult['batch'] as $i => $response) {
            $responses[] = new BatchResponse(
                $response['status'],
                $response['headers'] ?? null,
                $response['data'] ?? null,
                $requests[$i]
            );
        }

        $result = new BatchResult(...$responses);

        if ($result->hasErrors()) {
            $errorCount = count($result->errors());

            throw new PartialBatchFailureException(
                sprintf('Batch contains %d errors', $errorCount),
                $result
            );
        }

        return $result;
    }

    /**
     * Verifies a HMAC from a push notification using the client secret.
     *
     * @param string $hmac_header: A String containing comma-separated values
     * describing HMACs of the notification taken from the Cronofy-HMAC-SHA256 header.
     *
     * @param string $body: A String of the body of the notification.
     *
     * @return true|mixed true if one of the HMAC provided matches the one calculated using the client secret, otherwise false.
     */
    public function hmacValid($hmac_header, $body)
    {
        if ($hmac_header == null  || empty($hmac_header)) {
            return false;
        }

        $digest = hash_hmac('sha256', $body, $this->clientSecret, true);
        $calculated = base64_encode($digest);
        $hmac_list = explode(',', $hmac_header);

        return in_array($calculated, $hmac_list);
    }

    private function convertBatchRequestsToArray(BatchRequest ...$requests): array
    {
        $requestMapper = function (BatchRequest $request) {
            return [
                'method' => $request->method(),
                'relative_url' => $request->relativeUrl(),
                'data' => $request->data(),
            ];
        };

        return array_map($requestMapper, $requests);
    }

    private function apiUrl($path)
    {
        return $this->apiRootUrl . $path;
    }

    private function urlParams($params)
    {
        if (count($params) == 0) {
            return "";
        }
        $str_params = [];

        foreach ($params as $key => $val) {
            if (gettype($val) == "array") {
                for ($i = 0; $i < count($val); $i++) {
                    array_push($str_params, $key . "[]=" . urlencode($val[$i]));
                }
            } elseif (gettype($val) == "boolean") {
                $bool_str = $val ? "true" : "false";
                array_push($str_params, $key . "=" . urlencode($bool_str));
            } else {
                array_push($str_params, $key . "=" . urlencode($val));
            }
        }

        return "?" . join("&", $str_params);
    }

    private function getApiKeyAuthHeaders($with_content_headers = false)
    {
        $headers = [];

        $headers[] = 'Authorization: Bearer ' . $this->clientSecret;
        $headers[] = 'Host: ' . $this->hostDomain;

        if ($with_content_headers) {
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        return $headers;
    }

    private function getAuthHeaders($with_content_headers = false)
    {
        $headers = [];

        if (isset($this->accessToken)) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        $headers[] = 'Host: ' . $this->hostDomain;

        if ($with_content_headers) {
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        return $headers;
    }

    private function parsedResponse($response)
    {
        $json_decoded = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            return $response;
        }

        return $json_decoded;
    }

    public function handleResponse($result, $status_code)
    {
        if ($status_code >= 200 && $status_code < 300) {
            return $this->parsedResponse($result);
        }

        throw new CronofyException($this->http_codes[$status_code], $status_code, $this->parsedResponse($result));
    }

    private $http_codes = [
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
        429 => 'Too Many Requests',
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
    ];
}
