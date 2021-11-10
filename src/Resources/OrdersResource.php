<?php

namespace Jasara\AmznSPA\Resources;

use Carbon\CarbonImmutable;
use Jasara\AmznSPA\AmznSPAHttp;
use Jasara\AmznSPA\Constants\MarketplacesList;
use Jasara\AmznSPA\Contracts\ResourceContract;
use Jasara\AmznSPA\DataTransferObjects\Responses\Orders\GetOrderAddressResponse;
use Jasara\AmznSPA\DataTransferObjects\Responses\Orders\GetOrderBuyerInfoResponse;
use Jasara\AmznSPA\DataTransferObjects\Responses\Orders\GetOrderItemsBuyerInfoResponse;
use Jasara\AmznSPA\DataTransferObjects\Responses\Orders\GetOrderItemsResponse;
use Jasara\AmznSPA\DataTransferObjects\Responses\Orders\GetOrderResponse;
use Jasara\AmznSPA\DataTransferObjects\Responses\Orders\GetOrdersResponse;
use Jasara\AmznSPA\Traits\ValidatesParameters;

class OrdersResource implements ResourceContract
{
    use ValidatesParameters;

    public const BASE_PATH = '/orders/v0/';

    public function __construct(
        private AmznSPAHttp $http,
        private string $endpoint,
    ) {
    }

    public function getOrders(
        array $marketplace_ids,
        ?CarbonImmutable $created_after = null,
        ?CarbonImmutable $created_before = null,
        ?CarbonImmutable $last_updated_after = null,
        ?CarbonImmutable $last_updated_before = null,
        ?array $order_statuses = null,
        ?array $fulfillment_channels = [],
        ?array $payment_methods = [],
        ?string $buyer_email = null,
        ?string $seller_order_id = null,
        ?int $max_results_per_page = null,
        ?array $easy_ship_shipment_statuses = [],
        ?string $next_token = null,
        ?array $amazon_order_ids = [],
        ?string $actual_fulfillment_supply_source_id = null,
        ?bool $is_ispu = null,
        ?string $store_chain_store_id = null,
    ): GetOrdersResponse {
        $this->validateIsArrayOfStrings($marketplace_ids, MarketplacesList::allIdentifiers());

        if ($order_statuses) {
            $this->validateIsArrayOfStrings($order_statuses);
        }

        $response = $this->http->get($this->endpoint . self::BASE_PATH . 'orders', array_filter([
            'MarketplaceIds' => $marketplace_ids,
            'CreatedAfter' => $created_after,
            'CreatedBefore' => $created_before,
            'LastUpdatedAfter' => $last_updated_after,
            'LastUpdatedBefore' => $last_updated_before,
            'OrderStatuses' => $order_statuses,
            'FulfillmentChannels' => $fulfillment_channels,
            'PaymentMethods' => $payment_methods,
            'BuyerEmail' => $buyer_email,
            'SellerOrderId' => $seller_order_id,
            'MaxResultsPerPage' => $max_results_per_page,
            'EasyShipShipmentStatuses' => $easy_ship_shipment_statuses,
            'NextToken' => $next_token,
            'AmazonOrderIds' => $amazon_order_ids,
            'ActualFulfillmentSupplySourceId' => $actual_fulfillment_supply_source_id,
            'IsIspu' => $is_ispu,
            'StoreChainStoreId' => $store_chain_store_id,
        ]));

        return new GetOrdersResponse($response);
    }

    public function getOrder(string $order_id): GetOrderResponse
    {
        $response = $this->http->get($this->endpoint . self::BASE_PATH . 'orders/' . $order_id);

        return new GetOrderResponse($response);
    }

    public function getOrderBuyerInfo(string $order_id): GetOrderBuyerInfoResponse
    {
        $response = $this->http->get($this->endpoint . self::BASE_PATH . 'orders/' . $order_id . '/buyerInfo');

        return new GetOrderBuyerInfoResponse($response);
    }

    public function getOrderAddress(string $order_id): GetOrderAddressResponse
    {
        $response = $this->http->get($this->endpoint . self::BASE_PATH . 'orders/' . $order_id . '/address');

        return new GetOrderAddressResponse($response);
    }

    public function getOrderItems(string $order_id, ?string $next_token = null): GetOrderItemsResponse
    {
        $response = $this->http->get($this->endpoint . self::BASE_PATH . 'orders/' . $order_id . '/orderItems');

        return new GetOrderItemsResponse($response);
    }

    public function getOrderItemsBuyerInfo(string $order_id, ?string $next_token = null): GetOrderItemsBuyerInfoResponse
    {
        $response = $this->http->get($this->endpoint . self::BASE_PATH . 'orders/' . $order_id . '/orderItems/buyerInfo');

        return new GetOrderItemsBuyerInfoResponse($response);
    }
}