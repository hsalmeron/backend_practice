<?php

namespace Mollie\Api\Endpoints;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\BaseCollection;
use Mollie\Api\Resources\BaseResource;
use Mollie\Api\Resources\ResourceFactory;
use Psr\Http\Message\StreamInterface;

abstract class EndpointAbstract
{
    const REST_CREATE = MollieApiClient::HTTP_POST;
    const REST_UPDATE = MollieApiClient::HTTP_PATCH;
    const REST_READ = MollieApiClient::HTTP_GET;
    const REST_LIST = MollieApiClient::HTTP_GET;
    const REST_DELETE = MollieApiClient::HTTP_DELETE;

    /**
     * @var MollieApiClient
     */
    protected $api;

    /**
     * @var string
     */
    protected $resourcePath;

    /**
     * @var string|null
     */
    protected $parentId;

    /**
     * @param MollieApiClient $api
     */
    public function __construct(MollieApiClient $api)
    {
        $this->api = $api;
    }

    /**
     * @param array $filters
     * @return string
     */
    private function buildQueryString(array $filters)
    {
        if (empty($filters)) {
            return "";
        }

        return "?" . http_build_query($filters, "");
    }

    /**
     * @param string|null|resource|StreamInterface $body
     * @param array $filters
     * @return BaseResource
     * @throws ApiException
     */
    protected function rest_create($body, array $filters)
    {
        try {
            $encoded = \GuzzleHttp\json_encode($body);
        } catch (\InvalidArgumentException $e) {
            throw new ApiException("Error encoding parameters into JSON: '" . $e->getMessage() . "'.");
        }

        $result = $this->api->performHttpCall(
            self::REST_CREATE,
            $this->getResourcePath() . $this->buildQueryString($filters),
            $encoded
        );

        return ResourceFactory::createFromApiResult($result, $this->getResourceObject());
    }

    /**
     * Retrieves a single object from the REST API.
     *
     * @param string $id Id of the object to retrieve.
     * @param array $filters
     * @return BaseResource
     * @throws ApiException
     */
    protected function rest_read($id, array $filters)
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $id = urlencode($id);
        $result = $this->api->performHttpCall(
            self::REST_READ,
            "{$this->getResourcePath()}/{$id}" . $this->buildQueryString($filters)
        );

        return ResourceFactory::createFromApiResult($result, $this->getResourceObject());
    }

    /**
     * Sends a DELETE request to a single Molle API object.
     *
     * @param string $id
     *
     * @return BaseResource
     * @throws ApiException
     */
    protected function rest_delete($id)
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $id = urlencode($id);
        $result = $this->api->performHttpCall(
            self::REST_DELETE,
            "{$this->getResourcePath()}/{$id}"
        );

        if ($result === null) {
            return null;
        }

        return ResourceFactory::createFromApiResult($result, $this->getResourceObject());
    }

    /**
     * Sends a POST request to a single Molle API object to update it.
     *
     * @param string $id
     * @param string|null|resource|StreamInterface $body
     *
     * @return BaseResource
     * @throws ApiException
     */
    protected function rest_update($id, $body)
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $id = urlencode($id);
        $result = $this->api->performHttpCall(
            self::REST_UPDATE,
            "{$this->getResourcePath()}/{$id}",
            $body
        );

        return ResourceFactory::createFromApiResult($result, $this->getResourceObject());
    }

    /**
     * Get a collection of objects from the REST API.
     *
     * @param string $from The first resource ID you want to include in your list.
     * @param int $limit
     * @param array $filters
     *
     * @return BaseCollection
     * @throws ApiException
     */
    protected function rest_list($from = null, $limit = null, array $filters)
    {
        $filters = array_merge(["from" => $from, "limit" => $limit], $filters);

        $apiPath = $this->getResourcePath() . $this->buildQueryString($filters);

        $result = $this->api->performHttpCall(self::REST_LIST, $apiPath);

        /** @var BaseCollection $collection */
        $collection = $this->getResourceCollectionObject($result->count, $result->_links);

        foreach ($result->_embedded->{$collection->getCollectionResourceName()} as $dataResult) {
            $collection[] = ResourceFactory::createFromApiResult($dataResult, $this->getResourceObject());
        }

        return $collection;
    }

    /**
     * Get the object that is used by this API endpoint. Every API endpoint uses one type of object.
     *
     * @return BaseResource
     */
    abstract protected function getResourceObject();

    /**
     * Get the collection object that is used by this API endpoint. Every API endpoint uses one type of collection object.
     *
     * @param int $count
     * @param object[] $_links
     *
     * @return BaseCollection
     */
    abstract protected function getResourceCollectionObject($count, $_links);

    /**
     * @param string $resourcePath
     */
    public function setResourcePath($resourcePath)
    {
        $this->resourcePath = strtolower($resourcePath);
    }

    /**
     * @return string
     * @throws ApiException
     */
    public function getResourcePath()
    {
        if (strpos($this->resourcePath, "_") !== false) {
            list($parentResource, $childResource) = explode("_", $this->resourcePath, 2);

            if (empty($this->parentId)) {
                throw new ApiException("Subresource '{$this->resourcePath}' used without parent '$parentResource' ID.");
            }

            return "$parentResource/{$this->parentId}/$childResource";
        }

        return $this->resourcePath;
    }
}
