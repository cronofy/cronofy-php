<?php declare(strict_types=1);

namespace Cronofy\Http;

class CurlRequest implements HttpRequest
{
    public $useragent;

    public function __construct($useragent)
    {
        $this->useragent = $useragent;
    }

    public function httpGet($url, array $auth_headers)
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

        return [$result, $status_code];
    }

    public function getPage($url, array $auth_headers, $url_params = "")
    {
        return $this->httpGet($url.$url_params, $auth_headers);
    }

    public function httpPost($url, array $params, array $auth_headers)
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

        return [$result, $status_code];
    }

    public function httpDelete($url, array $params, array $auth_headers)
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

        return [$result, $status_code];
    }
}
