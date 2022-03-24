<?php

namespace UBA\DHLExpress\Model\Service;

use UBA\DHLExpress\Helper\Data;
use UBA\DHLExpress\Model\Cache\Api as ApiCache;
use UBA\DHLExpress\Model\Config\Source\LabelsOnPage;
use UBA\DHLExpress\Model\Exception\LabelNotFoundException;
use UBA\DHLExpress\Model\Exception\NoTrackException;
use UBA\DHLExpress\Model\Exception\ShipmentNoLabelsException;
use UBA\DHLExpress\Model\Service\Logic\Label as LabelLogic;
use UBA\DHLExpress\Model\Service\Logic\PDFMerger;

use Magento\Framework\App\ResponseInterface;

class Label
{
    protected $apiCache;
    protected $labelLogic;
    protected $PDFMerger;
    protected $helper;

    /**
     * Label constructor.
     * @param ApiCache $apiCache
     * @param LabelLogic $labelLogic
     * @param PDFMerger $PDFMerger
     * @param Data $helper
     */
    public function __construct(
        ApiCache $apiCache,
        LabelLogic $labelLogic,
        PDFMerger $PDFMerger,
        Data $helper
    ) {
        $this->apiCache = $apiCache;
        $this->labelLogic = $labelLogic;
        $this->PDFMerger = $PDFMerger;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     * @throws NoTrackException
     * @throws ShipmentNoLabelsException
     */
    public function getShipmentLabelIds($shipment)
    {
        if (!is_callable([$shipment, 'getTracks'])) {
            throw new NoTrackException(__("Unable to use track and trace and therefore no labels available for shipment %1", $shipment->getId()));
        }
        $labelIds = $this->labelLogic->getShipmentLabelIds($shipment);
        if (empty($labelIds)) {
            throw new ShipmentNoLabelsException(__("No labels found for shipment %1, order number #%2", $shipment->getId()));
        }
        return $labelIds;
    }

    /**
     * @param $labelId
     * @return bool|string
     * @throws LabelNotFoundException
     */
    public function getLabelPdf($labelId)
    {
        $cacheKey = $this->apiCache->createKey(0, 'label', ['labelId' => $labelId]);
        $raw = $this->apiCache->load($cacheKey);

        if ($raw === false) {
            if (!$labelResponse = $this->labelLogic->get($labelId)) {
                throw new LabelNotFoundException(__('Unable to retrieve label %1', $labelId));
            }
            if (!$label = base64_decode($labelResponse->pdf)) {
                throw new LabelNotFoundException(__('Unable to retrieve label %1', $labelId));
            }
            $this->apiCache->save($labelResponse->pdf, $cacheKey, [], 3600);
        } else {
            $label = base64_decode($raw);
        }

        return $label;
    }

    /**
     * @param ResponseInterface $response
     * @param $PDFs
     * @return mixed
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \setasign\Fpdi\PdfReader\PdfReaderException
     */
    public function pdfResponse(ResponseInterface $response, $PDFs)
    {
        if ($this->helper->getConfigData('label/labels_per_page') == LabelsOnPage::LABEL_PAGE_TRIPLE) {
            $pdfString = $this->PDFMerger->mergePDFs($PDFs, 3, 1);
        } elseif ($this->helper->getConfigData('label/labels_per_page') == LabelsOnPage::LABEL_PAGE_QUADRUPLE) {
            $pdfString = $this->PDFMerger->mergePDFs($PDFs, 2, 2);
        } else {
            $pdfString = $this->PDFMerger->mergePDFs($PDFs, 1, 1);
        }

        return $response
            ->setBody($pdfString)
            ->setHeader('Content-type', 'application/pdf', true);
    }
}
