<?php declare(strict_types=1);

namespace Cronofy\Http;

interface HttpRequest
{
    public function httpGet($url, array $auth_headers);
    public function getPage($url, array $auth_headers, $url_params = "");
    public function httpPost($url, array $params, array $auth_headers);
    public function httpDelete($url, array $params, array $auth_headers);
}
