<?php

namespace Nosok\XeroReport\Reports;

use Carbon\Carbon;
use Nosok\XeroReport\Notifications\ReportCreated;
use XeroPHP\Application\PrivateApplication as XeroApp;
use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Models\Accounting\PurchaseOrder;

class WeeklyReport extends BaseReport
{
    public function __construct()
    {
        // TODO:: validate config params, catch exceptions
        $this->xero = new XeroApp(config('xeroreport.xero'));
        $this->invoices = $this->getInvoicesModifiedSinceStartOfLastMonth();
        $this->purchaseOrders = $this->getPurchaseOrdersWaitingToBeApproved();
    }

    public function generate()
    {
        $this->generateFullReport();
        $this->generatePartialReport();
    }

    public function generateFullReport()
    {
        $fullReport  = '';
        $fullReport .= $this->prepareIssuedInvoicesSection($this->invoices);
        $fullReport .= $this->prepareOutstandingInvoicesSection($this->invoices);
        $fullReport .= $this->prepareOverDueInvoicesSection($this->invoices);
        $fullReport .= $this->prepareSubmittedPurchaseOrdersSection($this->purchaseOrders);

        $this->setSlackChannel(config('xeroreport.notifications.slack.channel.full_report'))
            ->notify(new ReportCreated($fullReport));
    }

    public function generatePartialReport()
    {
        $partialReport  = '';
        $partialReport .= $this->prepareIssuedInvoicesSection($this->invoices);
        $partialReport .= $this->prepareSubmittedPurchaseOrdersSection($this->purchaseOrders);

        $this->setSlackChannel(config('xeroreport.notifications.slack.channel.partial_report'))
            ->notify(new ReportCreated($partialReport));
    }

    public function prepareIssuedInvoicesSection($invoices)
    {
        if ($invoices->count() < 1) { return "*Issued Invoices:*\nnone\n"; }

        $startofLastWeek  = Carbon::now()->subWeeks(1)->startofWeek(Carbon::MONDAY);
        $endofLastWeek    = Carbon::now()->subWeeks(1)->endofWeek(Carbon::SUNDAY);
        $startofLastMonth = Carbon::now()->submonths(1)->startOfMonth();
        $endofLastMonth   = Carbon::now()->submonths(1)->endOfMonth();
        $startofThisMonth = Carbon::now()->startOfMonth();
        $invoicedAmountLastWeek = 0;
        $invoicedAmountThisMonth = 0;
        $invoicedAmountLastMonth = 0;

        foreach ($invoices as $invoice) {
            $invoiceDate = Carbon::instance($invoice->getDate());

            if ($invoice->getType() != 'ACCREC') { continue; }

            if ($invoiceDate->greaterThanOrEqualTo($startofLastWeek) && $invoiceDate->lessThanOrEqualTo($endofLastWeek)) {
                $invoicedAmountLastWeek = $invoicedAmountLastWeek + $invoice->getTotal();
            }
            if ($invoiceDate->greaterThanOrEqualTo($startofLastMonth) && $invoiceDate->lessThanOrEqualTo($endofLastMonth)) {
                $invoicedAmountLastMonth = $invoicedAmountLastMonth + $invoice->getTotal();
            }
            if ($invoiceDate->greaterThanOrEqualTo($startofThisMonth)) {
                $invoicedAmountThisMonth = $invoicedAmountThisMonth + $invoice->getTotal();
            }
        }

        $sectionReport  = "*Summary for Last Week:*\n";
        $sectionReport .= "Invoiced last week  = *\${$this->kFormat($invoicedAmountLastWeek)}* ({$startofLastWeek->format('jS F')} - {$endofLastWeek->format('jS F')}) \n";
        $sectionReport .= "Invoiced this month = *\${$this->kFormat($invoicedAmountThisMonth)}* ({$startofThisMonth->format('F')}) \n";
        $sectionReport .= "Invoiced last month = *\${$this->kFormat($invoicedAmountLastMonth)}* ({$startofLastMonth->format('F')}) \n";

        return "{$sectionReport}\n";
    }

    public function prepareOutstandingInvoicesSection($invoices)
    {
        if ($invoices->count() < 1) { return "*Outstanding Invoices:*\nnone\n"; }

        $outstandingAmount = 0;
        $outstandingList = [];
        foreach ($invoices as $invoice) {
            if ($invoice->getType() == 'ACCPAY' && $invoice->getAmountDue() > 0) {
                $dueAmount         = $invoice->getAmountDue();
                $currencyCode      = $invoice->getCurrencyCode();
                $outstandingAmount = $outstandingAmount + $dueAmount;
                $outstandingList[$currencyCode] = isset($outstandingList[$currencyCode]) ? $outstandingList[$currencyCode] + $dueAmount: $dueAmount;
            }
        }

        arsort($outstandingList);
        $sectionReport   = "*Outstanding Invoices: \${$this->kFormat($outstandingAmount)}*\n";
        foreach ($outstandingList as $currencyCode => $amount) {
            $sectionReport .= "{$currencyCode} = *{$this->kFormat($amount)}*, ";
        }

        return "{$sectionReport}\n\n";
    }

    public function prepareOverDueInvoicesSection($invoices)
    {
        if ($invoices->count() < 1) { return "*Overdue Invoices:*\nnone\n"; }

        $invoices = collect($invoices)->sortBy('InvoiceNumber');

        $sectionReport = "*Overdue Invoices:*\n";
        foreach ($invoices as $invoice) {
            if ($invoice->getType() == 'ACCREC' && $invoice->getAmountDue() > 0) {
                $sectionReport .= "{$invoice->getInvoiceNumber()} - {$invoice->getContact()->Name} - *\${$this->kFormat($invoice->getAmountDue())}* \n";
            }
        }

        return "{$sectionReport}\n";
    }

    public function prepareSubmittedPurchaseOrdersSection($purchaseOrders)
    {
        if ($purchaseOrders->count() < 1) { return "*PO's still waiting on:*\nnone\n"; }

        $sectionReport = "*PO's still waiting on:*\n";
        foreach ($purchaseOrders as $purchaseOrder) {
            if ($purchaseOrder->getStatus() == 'SUBMITTED') {
                $sectionReport .= "{$purchaseOrder->getDate()->format('d M')} - {$purchaseOrder->getPurchaseOrderNumber()} - {$purchaseOrder->getContact()->Name}\n";
            }
        }

        return "{$sectionReport}\n";
    }

    public function getInvoicesModifiedSinceStartOfLastMonth()
    {
        $startofLastMonth    = Carbon::now()->submonths(1)->startOfMonth();

        return $this->getInvoicesModifiedSinceDate($startofLastMonth);
    }

    public function getInvoicesModifiedSinceDate($date = null)
    {
        // TODO:: if api returns 100 items, fetch the next page and add to list
        // TODO:: catch errors/exceptions
        return $this->xero->load(Invoice::class)
            ->where("Date >= DateTime({$date->format('Y, m, d')})")
            ->orWhere("AmountDue > 0")
            ->page(1)
            ->execute();
    }

    public function getPurchaseOrdersWaitingToBeApproved()
    {
        // TODO:: if api returns 100 items, fetch the next page and add to list
        // TODO:: catch errors/exceptions
        return $this->xero->load(PurchaseOrder::class)
            ->setParameter('status', 'SUBMITTED')
            ->orderBy('Date')
            ->execute();
    }

    public function kFormat($number = null)
    {
        if ($number > 999 && $number <= 999999) {
            return round($number / 1000, 1) . 'k';
        }
        if ($number > 999999) {
            return number_format((float)$number , 1, '.', '')/1000000 . 'm';
        }
        return $number;
    }
}
