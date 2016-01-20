<?php

class Cronofy{
	const USERAGENT = 'Cronofy PHP';

	var $client_id;
	
	var $client_secret;
	
	var $access_token;
	
	var $refresh_token;
	
	
	function __construct($client_id = false, $client_secret = false, $access_token = false, $refresh_token = false /*, $params = array()*/){
		if(!empty($client_id)){
			$this->client_id = $client_id;
		}
		if(!empty($client_secret)){
			$this->client_secret = $client_secret;
		}
		if(!empty($access_token)){
			$this->access_token = $access_token;
		}
		if(!empty($refresh_token)){
			$this->refresh_token = $refresh_token;
		}
	}
	
	function getAuthorizationURL($client_id, $params){
		/*
			String $client_id : The client ID provided by Cronofy to authenticate your OAuth Client. Authenticates you as a trusted client. REQUIRED
			Array $params : An array of additional paramaters
					redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
					scope : An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
					state : String A value that will be returned to you unaltered along with the user's authorization request decision. OPTIONAL
					avoid_linking : Boolean when true means we will avoid linking calendar accounts together under one set of credentials. OPTIONAL
					
			Response : 
				String $url : The URL to authorize your access to the Cronofy API
		*/
	
	
		$scope_list = "";
		foreach($params['scope'] as $scope){
			$scope_list.=$scope." ";
		}
		
		
		$url = "https://app.cronofy.com/oauth/authorize?response_type=code&client_id=".$this->client_id."&redirect_uri=".urlencode($params['redirect_uri'])."&scope=".$scope_list;
		if(!empty($params['state'])){$url.="&state=".$params['state'];}
		if(!empty($params['avoid_linking'])){$url.="&avoid_linking=".$params['avoid_linking'];}
		return $url;
	}
	
	function request_token($client_id, $client_secret, $params){
		/*
			String $client_id : The client ID provided by Cronofy to authenticate your OAuth Client. Authenticates you as a trusted client. REQUIRED
			String $client_secret : The client_secret issued to you by Cronofy to authenticate your OAuth Client. Authenticates you as a trusted client along with your client_id. REQUIRED
			Array $params : An array of additional paramaters
					redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
					code: The short-lived, single-use code issued to you when the user authorized your access to their account as part of an Authorization  REQUIRED
					
			Response : 
				true if successful, error string if not
		*/
		$curl = curl_init();
		$url = "https://api.cronofy.com/oauth/token";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$headers = array();
		$headers[] = 'Host: api.cronofy.com';
		$headers[] = 'Content-Type: application/json; charset=utf-8';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);

		$postfields = array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'grant_type' => 'authorization_code',
			'code' => $params['code'],
			'redirect_uri' => $params['redirect_uri']
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
		
		$result = curl_exec($curl);
		$tokens = json_decode($result);
		curl_close($curl);

		if(!empty($tokens->access_token)){
			$this->access_token = $tokens->access_token;
			$this->refresh_token = $tokens->refresh_token;
			return true;
		}else{
			return $token->error;
		}
	}
	function refresh_token($client_id, $client_secret, $refresh_token){
		/*
			String $client_id : The client ID provided by Cronofy to authenticate your OAuth Client. Authenticates you as a trusted client. REQUIRED
			String $client_secret : The client_secret issued to you by Cronofy to authenticate your OAuth Client. Authenticates you as a trusted client along with your client_id. REQUIRED
			String $refresh_token : The refresh_token issued to you when the user authorized your access to their account. REQUIRED
			
			Response : 
				true if successful, error string if not
		*/
		$curl = curl_init();
		$url = "https://api.cronofy.com/oauth/token";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$headers = array();
		$headers[] = 'Host: api.cronofy.com';
		$headers[] = 'Content-Type: application/json; charset=utf-8';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);

		$postfields = array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'grant_type' => 'refresh_token',
			'refresh_token' => $this->refresh_token
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
		
		$result = curl_exec($curl);
		
		$tokens = json_decode($result);
		curl_close($curl);
		if(!empty($tokens->access_token)){
			$this->access_token = $tokens->access_token;
			$this->refresh_token = $tokens->refresh_token;
			return true;
		}else{
			return $token->error;
		}
	}
	function revoke_authorization($client_id, $client_secret, $token){
		/*
			String $client_id : The client ID provided by Cronofy to authenticate your OAuth Client. Authenticates you as a trusted client. REQUIRED
			String $client_secret : The client_secret issued to you by Cronofy to authenticate your OAuth Client. Authenticates you as a trusted client along with your client_id. REQUIRED
			String token : Either the refresh_token or access_token for the authorization you wish to revoke. REQUIRED
			
			Response : 
				HTTP Response
		*/
		$curl = curl_init();
		$url = "https://api.cronofy.com/oauth/token/revoke";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$headers = array();
		$headers[] = 'Host: api.cronofy.com';
		$headers[] = 'Content-Type: application/json; charset=utf-8';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);

		$postfields = array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'token' => $token
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
		
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
		
	}
	function list_calendars(){
		/*
			returns $result - Array of calendars. Details are available in the Cronofy API Documentation
		*/
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.cronofy.com/v1/calendars");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$headers = array();
		$headers[] = 'Authorization: Bearer '.$this->access_token;
		$headers[] = 'Host: api.cronofy.com';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);

		$result = curl_exec($curl);
		$result = json_decode($result, true);
		curl_close($curl);
		return $result;
	}

	function read_events($params){
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
		$url = "https://api.cronofy.com/v1/events?tzid=".urlencode($params['tzid']);
		if(!empty($params['from'])){$url.="&from=".$params['from'];}
		if(!empty($params['to'])){$url.="&to=".$params['to'];}
		if(!empty($params['include_deleted'])){$url.="&include_deleted=".$params['include_deleted'];}
		if(!empty($params['include_moved'])){$url.="&include_moved=".$params['include_moved'];}
		if(!empty($params['last_modified'])){$url.="&last_modified=".$params['last_modified'];}
		if(!empty($params['include_managed'])){$url.="&include_managed=".$params['include_managed'];}
		if(!empty($params['only_managed'])){$url.="&only_managed=".$params['only_managed'];}
		if(!empty($params['localized_times'])){$url.="&localized_times=".$params['localized_times'];}
		if(!empty($params['calendar_ids'])){
			foreach($params['calendar_ids'] as $calendar_id){
				$url.="&calendar_ids[]=".$calendar_id;
			}
		}
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$headers = array();
		$headers[] = 'Authorization: Bearer '.$this->access_token;
		$headers[] = 'Host: api.cronofy.com';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);

		$result = curl_exec($curl);
		$result = json_decode($result,true);
		curl_close($curl);
		return $result;
	}
	
	function upsert_event($params){
		/*
			calendar_id : The calendar_id of the calendar you wish the event to be added to. REQUIRED
			String event_id : The String that uniquely identifies the event. REQUIRED
			String summary : The String to use as the summary, sometimes referred to as the name, of the event. REQUIRED
			String description : The String to use as the description, sometimes referred to as the notes, of the event. REQUIRED
			String tzid : A String representing a known time zone identifier from the IANA Time Zone Database. OPTIONAL
			Time start: The start time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED 
			Time end: The end time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
			String location.description : The String describing the event's location. OPTIONAL
			
			
			returns true on success, error message on failure
		*/	
		$url = "https://api.cronofy.com/v1/calendars/".$params['calendar_id']."/events";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$headers = array();
		$headers[] = 'Authorization: Bearer '.$this->access_token;
		$headers[] = 'Host: api.cronofy.com';
		$headers[] = 'Content-Type: application/json; charset=utf-8';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);

		curl_setopt($curl, CURLOPT_POST, 1);
		$postfields = array(
			'event_id' => $params['event_id'],
			'summary' => $params['summary'],
			'description' => $params['description'],
			'start' => $params['start'],
			'end' => $params['end']
		);
		
		if(!empty($params['tzid'])){$postfields['tzid']=$params['tzid'];}
		if(!empty($params['location.description'])){$postfields['location.description']=$params['location.description'];}
		
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
		
		$result = curl_exec($curl);
		curl_close($curl);
		if (empty($result)){
			return true;
		}else{
			return $result;
		}
	}
	
	function delete_event($params){
		/*
			calendar_id : The calendar_id of the calendar you wish the event to be added to. REQUIRED
			String event_id : The String that uniquely identifies the event. REQUIRED
			
			returns true on success, error message on failure
		*/
		$url = "https://api.cronofy.com/v1/calendars/".$params['calendar_id']."/events";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$headers = array();
		$headers[] = 'Authorization: Bearer '.$this->access_token;
		$headers[] = 'Host: api.cronofy.com';
		$headers[] = 'Content-Type: application/json; charset=utf-8';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);

		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		$postfields = array('event_id' => $params['event_id']);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
		
		$result = curl_exec($curl);
		curl_close($curl);
		
		if (empty($result)){
			return true;
		}else{
			return $result;
		}
	}
}

?>
