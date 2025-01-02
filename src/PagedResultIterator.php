<?php declare(strict_types=1);

namespace Cronofy;

class PagedResultIterator implements \IteratorAggregate
{
    private $cronofy;
    private $itemsKey;
    private $authHeaders;
    private $url;
    private $urlParams;
    private $firstPage;

    public function __construct(Cronofy $cronofy, $itemsKey, $authHeaders, $url, $urlParams)
    {
        $this->cronofy = $cronofy;
        $this->itemsKey = $itemsKey;
        $this->authHeaders = $authHeaders;
        $this->url = $url;
        $this->urlParams = $urlParams;
        $this->firstPage = $this->getPage($url, $urlParams);
    }

    public function each()
    {
        $page = $this->firstPage;

        for ($i = 0; $i < count($page[$this->itemsKey]); $i++) {
            yield $page[$this->itemsKey][$i];
        }

        while (isset($page["pages"]["next_page"])) {
            $page = $this->getPage($page["pages"]["next_page"]);

            for ($i = 0; $i < count($page[$this->itemsKey]); $i++) {
                yield $page[$this->itemsKey][$i];
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->each();
    }

    private function getPage($url, $urlParams = '')
    {
        list ($result, $status_code) = $this->cronofy->httpClient->getPage($url, $this->authHeaders, $urlParams);

        return $this->cronofy->handleResponse($result, $status_code);
    }
}
