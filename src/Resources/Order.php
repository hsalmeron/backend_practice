<?php

namespace Mollie\Api\Resources;

use Mollie\Api\Types\OrderStatus;

class Order extends BaseResource
{
    /**
     * @var string
     */
    public $resource;

    /**
     * Id of the order.
     *
     * @example ord_8wmqcHMN4U
     * @var string
     */
    public $id;

    /**
     * The profile ID this order belongs to.
     *
     * @example pfl_xH2kP6Nc6X
     * @var string
     */
    public $profileId;

    /**
     * Either "live" or "test". Indicates this being a test or a live (verified) order.
     *
     * @var string
     */
    public $mode;

    /**
     * Amount object containing the value and currency
     *
     * @var object
     */
    public $amount;

    /**
     * The total amount captured, thus far.
     *
     * @var object
     */
    public $amountCaptured;

    /**
     * The total amount refunded, thus far.
     *
     * @var object
     */
    public $amountRefunded;

    /**
     * The status of the order.
     *
     * @var string
     */
    public $status;

    /**
     * The person and the address the order is billed to.
     *
     * @var object
     */
    public $billingAddress;

    /**
     * The date of birth of your customer, if available.
     * @example 1976-08-21
     * @var string|null
     */
    public $consumerDateOfBirth;

    /**
     * The order number that was used when creating the order.
     *
     * @var string
     */
    public $orderNumber;

    /**
     * The person and the address the order is billed to.
     *
     * @var object
     */
    public $shippingAddress;


    /**
     * The payment method last used when paying for the order.
     *
     * @see Method
     * @var string
     */
    public $method;

    /**
     * The locale used for this order.
     *
     * @var string
     */
    public $locale;

    /**
     * During creation of the order you can set custom metadata that is stored with
     * the order, and given back whenever you retrieve that order.
     *
     * @var object|mixed|null
     */
    public $metadata;

    /**
     * UTC datetime the order was created in ISO-8601 format.
     *
     * @example "2013-12-25T10:30:54+00:00"
     * @var string|null
     */
    public $createdAt;

    /**
     * The order lines contain the actual things the customer bought.
     * @var array|object[]
     */
    public $lines;

    /**
     * An object with several URL objects relevant to the customer. Every URL object will contain an href and a type field.
     * @var object[]
     */
    public $_links;

    /**
     * Is this order created?
     *
     * @return bool
     */
    public function isCreated()
    {
        return $this->status === OrderStatus::STATUS_CREATED;
    }

    /**
     * Is this order paid for?
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->status === OrderStatus::STATUS_PAID;
    }

    /**
     * Is this order authorized?
     *
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->status === OrderStatus::STATUS_AUTHORIZED;
    }

    /**
     * Is this order canceled?
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->status === OrderStatus::STATUS_CANCELED;
    }

    /**
     * Is this order refunded?
     *
     * @return bool
     */
    public function isRefunded()
    {
        return $this->status === OrderStatus::STATUS_REFUNDED;
    }

    /**
     * Is this order shipping?
     *
     * @return bool
     */
    public function isShipping()
    {
        return $this->status === OrderStatus::STATUS_SHIPPING;
    }

    /**
     * Is this order completed?
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === OrderStatus::STATUS_COMPLETED;
    }

    /**
     * Is this order void?
     *
     * @return bool
     */
    public function isVoid()
    {
        return $this->status === OrderStatus::STATUS_VOID;
    }

    /**
     * Cancel a line for this order.
     * Returns HTTP status 204 (no content) if succesful.
     *
     * @param  string $lineId
     * @return null
     */
    public function cancelLine($lineId)
    {
        return $this->client->orderLines->cancelFor($this, $lineId);
    }

    /**
     * Get the line value objects
     *
     * @return OrderLineCollection
     */
    public function lines()
    {
        $lines  = new OrderLineCollection(count($this->lines), null);
        foreach ($this->lines as $line) {
            $lines->append(ResourceFactory::createFromApiResult($line, new OrderLine($this->client)));
        }

        return $lines;
    }

    /**
     * Create a shipment for some order lines. You can provide an empty array for the
     * "lines" option to include all unshipped lines for this order.
     *
     * @param array $options
     *
     * @return Shipment
     */
    public function createShipment(array $options = [])
    {
        return $this->client->shipments->createFor($this, $options);
    }

    /**
     * Create a shipment for all unshipped order lines.
     *
     * @param array $options
     *
     * @return Shipment
     */
    public function shipAll(array $options = [])
    {
        $options['lines'] = [];
        return $this->createShipment($options);
    }

    /**
     * Retrieve a specific shipment for this order.
     *
     * @param string $shipmentId
     * @param array $parameters
     *
     * @return Shipment
     */
    public function getShipment($shipmentId, array $parameters = [])
    {
        return $this->client->shipments->getFor($this, $shipmentId, $parameters);
    }

    /**
     * Get all shipments for this order.
     *
     * @param array $parameters
     *
     * @return ShipmentCollection
     */
    public function shipments(array $parameters = [])
    {
        return $this->client->shipments->listFor($this, $parameters);
    }

    /**
     * Get the checkout URL where the customer can complete the payment.
     *
     * @return string|null
     */
    public function getCheckoutUrl()
    {
        if (empty($this->_links->checkout)) {
            return null;
        }

        return $this->_links->checkout->href;
    }
}
