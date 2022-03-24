<?php

namespace UBA\DHLExpress\Model\Service\Logic;

use UBA\DHLExpress\Model\Piece as Piece;
use UBA\DHLExpress\Model\PieceFactory as PieceFactory;
use UBA\DHLExpress\Model\ResourceModel\Piece as PieceResource;
use UBA\DHLExpress\Model\Service\Shipment as ShipmentService;
use UBA\DHLExpress\Model\Api\Connector;
use UBA\DHLExpress\Model\Data\Api\Response\Label as LabelResponse;
use UBA\DHLExpress\Model\Data\Api\Response\LabelFactory as LabelResponseFactory;

use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackFactory;

class Label
{
    protected $pieceFactory;
    protected $pieceResource;
    protected $shipmentService;
    protected $shipmentRepository;
    protected $trackFactory;
    protected $connector;
    protected $labelResponseFactory;

    /**
     * Label constructor.
     * @param PieceFactory $pieceFactory
     * @param PieceResource $pieceResource
     * @param ShipmentService $shipmentService
     * @param ShipmentRepository $shipmentRepository
     * @param TrackFactory $trackFactory
     * @param Connector $connector
     * @param LabelResponseFactory $labelResponseFactory
     */
    public function __construct(
        PieceFactory $pieceFactory,
        PieceResource $pieceResource,
        ShipmentService $shipmentService,
        ShipmentRepository $shipmentRepository,
        TrackFactory $trackFactory,
        Connector $connector,
        LabelResponseFactory $labelResponseFactory
    ) {
        $this->pieceFactory = $pieceFactory;
        $this->pieceResource = $pieceResource;
        $this->shipmentService = $shipmentService;
        $this->shipmentRepository = $shipmentRepository;
        $this->trackFactory = $trackFactory;
        $this->connector = $connector;
        $this->labelResponseFactory = $labelResponseFactory;
    }

    /**
     * @param $shipment
     * @return array
     */
    public function getShipmentLabelIds($shipment)
    {
        $labelIds = [];
        /** @var Track $track */
        foreach ($shipment->getTracks() as $track) {
            if ($track->getCarrierCode() == 'dhlexpress') {
                $trackNumber = $track->getTrackNumber();
                $piece = $this->pieceFactory->create();
                /** @var Piece $piece */
                $this->pieceResource->load($piece, $trackNumber, 'tracker_code');
                $labelId = $piece->getLabelId();
                if ($labelId) {
                    $labelIds[] = $piece->getLabelId();
                }
            }
        }
        return $labelIds;
    }

    /**
     * @param $labelId
     * @return LabelResponse|null
     */
    public function get($labelId)
    {
        $response = $this->connector->get('labels/' . $labelId);
        if (!$response) {
            return null;
        }

        /** @var LabelResponse $labelResponse */
        $labelResponse = $this->labelResponseFactory->create(['automap' => $response]);
        return $labelResponse;
    }
}
