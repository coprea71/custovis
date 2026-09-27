<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\ZugferdProfiles;

/**
 * Builds the EN 16931 e-invoice of an issued invoice from its seller/buyer
 * snapshots: an XRechnung 3 CII file and a ZUGFeRD (EN16931 profile) PDF/A-3
 * with the same data embedded (32.md).
 */
class EInvoiceBuilder
{
    private const SMALL_BUSINESS_REASON = 'Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG';

    public function xrechnung(Invoice $invoice): string
    {
        return $this->document($invoice, ZugferdProfiles::PROFILE_XRECHNUNG_3)->getContent();
    }

    public function zugferdPdf(Invoice $invoice): string
    {
        $visualPdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice->loadMissing('items')])->output();

        return (new ZugferdDocumentPdfBuilder($this->document($invoice, ZugferdProfiles::PROFILE_EN16931), $visualPdf))
            ->generateDocument()
            ->downloadString();
    }

    private function document(Invoice $invoice, int $profile): ZugferdDocumentBuilder
    {
        $document = ZugferdDocumentBuilder::createNew($profile)
            ->setDocumentInformation($invoice->number, $invoice->documentTypeCode(), $invoice->issue_date, 'EUR')
            ->setDocumentBuyerReference($invoice->buyer['reference']);

        $this->addNotes($document, $invoice);
        $this->addSeller($document, $invoice->seller);
        $this->addBuyer($document, $invoice->buyer);
        $this->addDeliveryAndPayment($document, $invoice);
        $invoice->items->each(fn (InvoiceItem $item) => $this->addPosition($document, $invoice, $item));
        $this->addTotals($document, $invoice);

        return $document;
    }

    private function addNotes(ZugferdDocumentBuilder $document, Invoice $invoice): void
    {
        if ($invoice->notes) {
            $document->addDocumentNote($invoice->notes);
        }
        if ($invoice->small_business) {
            $document->addDocumentNote(self::SMALL_BUSINESS_REASON);
        }
    }

    /**
     * @param  array<string, string>  $seller
     */
    private function addSeller(ZugferdDocumentBuilder $document, array $seller): void
    {
        $document->setDocumentSeller($seller['company'])
            ->setDocumentSellerAddress($seller['street'], null, null, $seller['postal_code'], $seller['city'], $seller['country'])
            ->setDocumentSellerContact($seller['contact_name'], null, $seller['phone'], null, $seller['email'])
            ->setDocumentSellerCommunication('EM', $seller['email']);

        if ($seller['vat_id'] !== '') {
            $document->addDocumentSellerTaxRegistration('VA', $seller['vat_id']);
        }
        if ($seller['tax_number'] !== '') {
            $document->addDocumentSellerTaxRegistration('FC', $seller['tax_number']);
        }
    }

    /**
     * @param  array<string, string|null>  $buyer
     */
    private function addBuyer(ZugferdDocumentBuilder $document, array $buyer): void
    {
        $document->setDocumentBuyer($buyer['name'], $buyer['customer_number'])
            ->setDocumentBuyerAddress($buyer['street'], null, null, $buyer['postal_code'], $buyer['city'], $buyer['country'])
            ->setDocumentBuyerCommunication('EM', $buyer['email']);

        if ($buyer['contact']) {
            $document->setDocumentBuyerContact($buyer['contact'], null, null, null, $buyer['email']);
        }
        if ($buyer['vat_id']) {
            $document->addDocumentBuyerTaxRegistration('VA', $buyer['vat_id']);
        }
    }

    private function addDeliveryAndPayment(ZugferdDocumentBuilder $document, Invoice $invoice): void
    {
        $invoice->service_from && $invoice->service_to
            ? $document->setDocumentBillingPeriod($invoice->service_from, $invoice->service_to, null)
            : $document->setDocumentSupplyChainEvent($invoice->issue_date);

        if ($invoice->isCancellation()) {
            $original = $invoice->cancelledInvoice;
            $document->setDocumentInvoiceReferencedDocument($original->number, null, $original->issue_date);
        }

        $seller = $invoice->seller;
        $document->addDocumentPaymentMeanToCreditTransfer($seller['iban'], $seller['company'], null, $seller['bic'] ?: null, $invoice->number)
            ->addDocumentPaymentTerm($this->paymentTerm($invoice), $invoice->due_date);
    }

    private function paymentTerm(Invoice $invoice): string
    {
        return $invoice->isCancellation()
            ? 'Der Betrag wird mit der stornierten Rechnung '.$invoice->cancelledInvoice->number.' verrechnet.'
            : 'Zahlbar bis '.$invoice->due_date->format('d.m.Y').' ohne Abzug.';
    }

    private function addPosition(ZugferdDocumentBuilder $document, Invoice $invoice, InvoiceItem $item): void
    {
        [$category, $rate, $reason] = $this->taxCategory($invoice);

        $document->addNewPosition((string) $item->position)
            ->setDocumentPositionProductDetails($item->description)
            ->setDocumentPositionNetPrice($item->unit_price_cents / 100)
            ->setDocumentPositionQuantity((float) $item->quantity, $item->unit_code)
            ->addDocumentPositionTax($category, 'VAT', $rate, null, $reason)
            ->setDocumentPositionLineSummation($item->net_cents / 100);
    }

    private function addTotals(ZugferdDocumentBuilder $document, Invoice $invoice): void
    {
        [$category, $rate, $reason] = $this->taxCategory($invoice);
        $net = $invoice->net_cents / 100;
        $tax = $invoice->tax_cents / 100;
        $gross = $invoice->gross_cents / 100;

        $document->addDocumentTax($category, 'VAT', $net, $tax, $rate, $reason)
            ->setDocumentSummation($gross, $gross, $net, 0.0, 0.0, $net, $tax, null, 0.0);
    }

    /**
     * @return array{0: string, 1: float, 2: string|null} UNCL 5305 category, rate, exemption reason
     */
    private function taxCategory(Invoice $invoice): array
    {
        return $invoice->small_business
            ? ['E', 0.0, self::SMALL_BUSINESS_REASON]
            : ['S', (float) $invoice->tax_rate, null];
    }
}
