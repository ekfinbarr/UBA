<?php

namespace UBA\DHLExpress\Model\Data\Api\Request;

use UBA\DHLExpress\Model\Data\AbstractData;

class Shipment extends AbstractData
{
    public $shipmentId;
    public $orderReference;
    /** @var  \UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee */
    public $receiver;
    /** @var  \UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee */
    public $shipper;
    /** @var  \UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee */
    public $onBehalfOf;
    public $accountId;
    /** @var  \UBA\DHLExpress\Model\Data\Api\Request\Shipment\Option[] */
    public $options;
    public $returnLabel;
    /** @var  \UBA\DHLExpress\Model\Data\Api\Request\Shipment\Piece[] */
    public $pieces;
    public $application;

    protected function getClassMap()
    {
        return [
            'receiver'   => 'UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee',
            'shipper'    => 'UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee',
            'onBehalfOf' => 'UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee',
        ];
    }

    protected function getClassArrayMap()
    {
        return [
            'options' => 'UBA\DHLExpress\Model\Data\Api\Request\Shipment\Option',
            'pieces'  => 'UBA\DHLExpress\Model\Data\Api\Request\Shipment\Piece',
        ];
    }
}
