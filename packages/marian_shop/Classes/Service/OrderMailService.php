<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

use Marian\Shop\Domain\Model\Customer;
use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Domain\Model\PaymentMethod;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Mail\MailerInterface;
use Symfony\Component\Mime\Address as MimeAddress;
use TYPO3\CMS\Core\Mail\FluidEmail;

/**
 * Verschickt die E-Mails rund um eine Bestellung.
 *
 * Ein fehlgeschlagener Mailversand darf die Bestellung nicht kippen: die
 * Bestellung ist zu diesem Zeitpunkt bereits gespeichert und bezahlt. Fehler
 * landen deshalb im Log, nicht als Ausnahme beim Kunden.
 */
class OrderMailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ShopSettings $settings,
        private readonly LoggerInterface $logger,
    ) {}

    public function sendOrderConfirmation(Order $order, ?PaymentMethod $payment, string $orderUrl = ''): void
    {
        if ($order->getEmail() === '') {
            return;
        }

        $email = (new FluidEmail())
            ->to(new MimeAddress($order->getEmail(), $order->getBillingAddress()?->getFullName() ?? ''))
            ->subject(sprintf('Deine Bestellung %s', $order->getOrderNumber()))
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate('OrderConfirmation')
            ->assignMultiple([
                'order' => $order,
                'payment' => $payment,
                'instructions' => $payment?->getInstructions() ?? '',
                'orderUrl' => $orderUrl,
                'shopName' => $this->settings->getShopName(),
            ]);

        $this->send($email, 'Bestellbestätigung', $order);
    }

    /**
     * Meldung an den Shopbetreiber, damit eine Bestellung nicht übersehen wird.
     */
    public function sendOrderNotification(Order $order): void
    {
        $recipient = $this->settings->getNotificationEmail();
        if ($recipient === '') {
            $this->logger->notice('Keine Benachrichtigungsadresse gesetzt, Bestelleingang nicht gemeldet');

            return;
        }

        $email = (new FluidEmail())
            ->to($recipient)
            ->subject(sprintf('Neue Bestellung %s über %s', $order->getOrderNumber(), $order->getFormattedTotal()))
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate('OrderNotification')
            ->assignMultiple([
                'order' => $order,
                'shopName' => $this->settings->getShopName(),
            ]);

        $this->send($email, 'Bestelleingang', $order);
    }

    public function sendRegistrationConfirmation(Customer $customer, string $confirmUrl): void
    {
        $email = (new FluidEmail())
            ->to(new MimeAddress($customer->getEmail(), $customer->getDisplayName()))
            ->subject('Bitte bestätige dein Kundenkonto')
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate('RegistrationConfirmation')
            ->assignMultiple([
                'customer' => $customer,
                'confirmUrl' => $confirmUrl,
                'shopName' => $this->settings->getShopName(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            $this->logger->error('Bestätigungsmail konnte nicht versendet werden', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Zahlungsbestätigung. Nimmt einfache Werte statt eines Modells entgegen,
     * weil der Aufruf aus dem Webhook kommt – dort gibt es keinen
     * Extbase-Kontext, aus dem sich ein Modell laden ließe.
     */
    public function sendPaymentReceived(string $recipient, string $orderNumber, string $formattedTotal): void
    {
        if ($recipient === '') {
            return;
        }

        $email = (new FluidEmail())
            ->to($recipient)
            ->subject(sprintf('Zahlungseingang zu Bestellung %s', $orderNumber))
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate('PaymentReceived')
            ->assignMultiple([
                'orderNumber' => $orderNumber,
                'total' => $formattedTotal,
                'shopName' => $this->settings->getShopName(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            $this->logger->error('Zahlungsbestätigung konnte nicht versendet werden', [
                'order' => $orderNumber,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function send(FluidEmail $email, string $what, Order $order): void
    {
        try {
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            // Die Bestellung steht schon – ein Mailproblem darf sie nicht kippen.
            $this->logger->error($what . ' konnte nicht versendet werden', [
                'order' => $order->getOrderNumber(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
