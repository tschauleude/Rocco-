<?php

declare(strict_types=1);

namespace Marian\Shop\Controller;

use Marian\Shop\Domain\Checkout\CheckoutForm;
use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Domain\Repository\OrderRepository;
use Marian\Shop\Domain\Repository\PaymentMethodRepository;
use Marian\Shop\Domain\Repository\ShippingMethodRepository;
use Marian\Shop\Exception\CheckoutException;
use Marian\Shop\Payment\PaymentContext;
use Marian\Shop\Payment\PaymentProviderRegistry;
use Marian\Shop\Payment\PaymentResult;
use Marian\Shop\Service\CartService;
use Marian\Shop\Service\CheckoutValidator;
use Marian\Shop\Service\CustomerService;
use Marian\Shop\Service\OrderMailService;
use Marian\Shop\Service\OrderService;
use Marian\Shop\Service\PriceCalculator;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Http\ForwardResponse;

/**
 * Die Kasse: ein Formular, eine Prüfung, eine Bestellung.
 */
class CheckoutController extends AbstractShopController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly PriceCalculator $priceCalculator,
        private readonly ShippingMethodRepository $shippingMethodRepository,
        private readonly PaymentMethodRepository $paymentMethodRepository,
        private readonly CheckoutValidator $validator,
        private readonly OrderService $orderService,
        private readonly OrderRepository $orderRepository,
        private readonly OrderMailService $mailService,
        private readonly CustomerService $customerService,
        private readonly PaymentProviderRegistry $paymentProviders,
    ) {}

    /**
     * Das Kassenformular.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $values
     */
    public function indexAction(array $errors = [], array $values = []): ResponseInterface
    {
        $cart = $this->cartService->getCart($this->request);

        if ($cart->isEmpty()) {
            $this->addFlashMessage('Der Warenkorb ist leer.', '', ContextualFeedbackSeverity::INFO);

            return $this->redirectToCart();
        }

        $shippingMethods = $this->shippingMethodRepository->findAll();
        $paymentMethods = $this->filterAvailablePaymentMethods();

        $customer = $this->getCurrentCustomer();
        if ($values === [] && $customer !== null) {
            $values = $this->prefillFromCustomer($customer);
        }

        $selectedShipping = $this->resolveShipping((int)($values['shippingMethod'] ?? 0));
        $selectedPayment = $this->resolvePayment((int)($values['paymentMethod'] ?? 0), $paymentMethods);

        $this->view->assignMultiple([
            'cart' => $cart,
            'totals' => $this->priceCalculator->calculate($cart, $selectedShipping, $selectedPayment),
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            'selectedShipping' => $selectedShipping?->getUid(),
            'selectedPayment' => $selectedPayment?->getUid(),
            'errors' => $errors,
            'values' => $values,
            'customer' => $customer,
            'isLoggedIn' => $this->isLoggedIn(),
            'savedAddresses' => $customer?->getActiveAddresses() ?? [],
            'salutations' => \Marian\Shop\Domain\Model\Address::SALUTATIONS,
            'countries' => \Marian\Shop\Domain\Model\Address::COUNTRIES,
            'termsPid' => $this->intSetting('termsPid'),
            'withdrawalPid' => $this->intSetting('withdrawalPid'),
            'privacyPid' => $this->intSetting('privacyPid'),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Nimmt die Bestellung entgegen.
     *
     * Die Formularwerte werden bewusst roh aus dem Request gelesen und selbst
     * geprüft – siehe CheckoutForm.
     */
    #[Extbase\IgnoreValidation(['value' => 'form'])]
    public function placeAction(): ResponseInterface
    {
        $cart = $this->cartService->getCart($this->request);
        $form = CheckoutForm::fromParameters($this->request->getArguments());

        $errors = $this->validator->validate($form, $cart, $this->isLoggedIn());
        if ($errors !== []) {
            return (new ForwardResponse('index'))
                ->withArguments(['errors' => $errors, 'values' => $this->request->getArguments()]);
        }

        $shipping = $this->shippingMethodRepository->findByUid($form->shippingMethod);
        $payment = $this->paymentMethodRepository->findByUid($form->paymentMethod);

        if ($shipping === null || $payment === null) {
            return (new ForwardResponse('index'))->withArguments([
                'errors' => ['shippingMethod' => 'Bitte wähle Versand- und Zahlungsart erneut.'],
                'values' => $this->request->getArguments(),
            ]);
        }

        $provider = $this->paymentProviders->get($payment->getProvider());
        if ($provider === null || !$provider->isAvailable()) {
            return (new ForwardResponse('index'))->withArguments([
                'errors' => ['paymentMethod' => 'Diese Zahlungsart steht gerade nicht zur Verfügung.'],
                'values' => $this->request->getArguments(),
            ]);
        }

        $totals = $this->priceCalculator->calculate($cart, $shipping, $payment);
        $storagePid = $this->getStoragePid();
        $customer = $this->getCurrentCustomer();

        // Konto auf Wunsch anlegen – die Bestellung hängt dann schon daran,
        // auch wenn die Bestätigungsmail erst noch beantwortet werden muss.
        if ($form->createAccount && $customer === null) {
            $customer = $this->registerCustomerFromCheckout($form, $storagePid);
        }

        try {
            $order = $this->orderService->createFromCart(
                $cart,
                $totals,
                $form,
                $shipping,
                $payment,
                $customer?->getUid() ?? 0,
                $storagePid,
            );
        } catch (CheckoutException $exception) {
            return (new ForwardResponse('index'))->withArguments([
                'errors' => ['cart' => $exception->getMessage()],
                'values' => $this->request->getArguments(),
            ]);
        }

        // Anschrift ins Adressbuch übernehmen, wenn der Kunde eingeloggt ist.
        if ($customer !== null) {
            $this->customerService->addAddress(
                $customer,
                $form->getBillingAddress(),
                $storagePid,
                $customer->getActiveAddresses() === []
            );
        }

        $token = $this->orderService->createAccessToken($order);
        $result = $provider->start($order, new PaymentContext(
            successUrl: $this->buildOrderUri('success', $order, $token),
            cancelUrl: $this->buildOrderUri('cancel', $order, $token),
        ));

        if ($result->isFailed()) {
            $this->orderService->markFailed($order, $result->message);
            $this->addFlashMessage($result->message, '', ContextualFeedbackSeverity::ERROR);

            return (new ForwardResponse('index'))->withArguments([
                'values' => $this->request->getArguments(),
            ]);
        }

        $this->cartService->clear($this->request);

        if ($result->isRedirect()) {
            if ($result->reference !== '') {
                $this->orderService->storePaymentReference($order, $result->reference);
            }

            $this->sendMails($order, $payment, $token);

            return $this->redirectToUri($result->redirectUrl);
        }

        // Zahlarten ohne Umleitung: Bestellung gilt als angenommen.
        $order->setStatus(Order::STATUS_CONFIRMED);
        $this->orderRepository->update($order);
        $this->sendMails($order, $payment, $token);

        return $this->redirectToUri($this->buildOrderUri('success', $order, $token));
    }

    /**
     * Bestätigungsseite. Erreichbar auch für Gäste – über einen signierten
     * Schlüssel, damit sich keine fremden Bestellungen aufrufen lassen.
     */
    public function successAction(int $order = 0, string $token = ''): ResponseInterface
    {
        $orderRecord = $this->loadOrder($order, $token);

        $this->view->assignMultiple([
            'order' => $orderRecord,
            'payment' => $this->paymentMethodRepository->findByUid($orderRecord->getPaymentMethod()),
            'awaitingPayment' => $orderRecord->getPaymentProvider() === 'stripe' && !$orderRecord->isPaid(),
            'shopPid' => $this->intSetting('shopPid'),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Der Kunde hat die Zahlung abgebrochen.
     */
    public function cancelAction(int $order = 0, string $token = ''): ResponseInterface
    {
        $orderRecord = $this->loadOrder($order, $token);

        $this->view->assignMultiple([
            'order' => $orderRecord,
            'shopPid' => $this->intSetting('shopPid'),
        ]);

        return $this->htmlResponse();
    }

    private function loadOrder(int $uid, string $token): Order
    {
        $order = $uid > 0 ? $this->orderRepository->findByUid($uid) : null;

        if (!$order instanceof Order || !$this->orderService->isValidAccessToken($order, $token)) {
            $this->pageNotFound('Diese Bestellung gibt es nicht.');
        }

        return $order;
    }

    private function buildOrderUri(string $action, Order $order, string $token): string
    {
        return $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor($action, ['order' => $order->getUid(), 'token' => $token]);
    }

    private function sendMails(Order $order, \Marian\Shop\Domain\Model\PaymentMethod $payment, string $token): void
    {
        $this->mailService->sendOrderConfirmation(
            $order,
            $payment,
            $this->buildOrderUri('success', $order, $token)
        );
        $this->mailService->sendOrderNotification($order);
    }

    /**
     * Füllt die Kasse mit dem, was über den angemeldeten Kunden schon bekannt
     * ist: E-Mail, Telefon und die Standardanschrift aus dem Adressbuch.
     *
     * @return array<string, mixed>
     */
    private function prefillFromCustomer(\Marian\Shop\Domain\Model\Customer $customer): array
    {
        $values = [
            'email' => $customer->getEmail(),
            'phone' => $customer->getPhone(),
        ];

        $address = $customer->getDefaultAddress();
        if ($address !== null) {
            $values['billing'] = [
                'salutation' => $address->getSalutation(),
                'firstName' => $address->getFirstName(),
                'lastName' => $address->getLastName(),
                'company' => $address->getCompany(),
                'street' => $address->getStreet(),
                'houseNumber' => $address->getHouseNumber(),
                'zip' => $address->getZip(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
            ];
            $values['savedAddress'] = $address->getUid();
        } else {
            $values['billing'] = [
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'country' => 'DE',
            ];
        }

        return $values;
    }

    private function registerCustomerFromCheckout(CheckoutForm $form, int $storagePid): ?\Marian\Shop\Domain\Model\Customer
    {
        $billing = $form->getBillingAddress();

        $customer = $this->customerService->register(
            $form->email,
            $form->password,
            $storagePid,
            $this->intSetting('customerGroup'),
            $billing->getFirstName(),
            $billing->getLastName(),
            $form->phone,
        );

        $this->mailService->sendRegistrationConfirmation(
            $customer,
            $this->uriBuilder
                ->reset()
                ->setCreateAbsoluteUri(true)
                ->setTargetPageUid($this->intSetting('accountPid'))
                ->uriFor('confirm', ['token' => $customer->getConfirmationToken()], 'Account', 'MarianShop', 'Account')
        );

        return $customer;
    }

    /**
     * Nur Zahlungsarten anbieten, deren Implementierung auch einsatzbereit ist.
     *
     * @return array<int, \Marian\Shop\Domain\Model\PaymentMethod>
     */
    private function filterAvailablePaymentMethods(): array
    {
        $methods = [];
        foreach ($this->paymentMethodRepository->findAll() as $method) {
            $provider = $this->paymentProviders->get($method->getProvider());
            if ($provider !== null && $provider->isAvailable()) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    private function resolveShipping(int $uid): ?\Marian\Shop\Domain\Model\ShippingMethod
    {
        return ($uid > 0 ? $this->shippingMethodRepository->findByUid($uid) : null)
            ?? $this->shippingMethodRepository->findDefault();
    }

    /**
     * @param array<int, \Marian\Shop\Domain\Model\PaymentMethod> $available
     */
    private function resolvePayment(int $uid, array $available): ?\Marian\Shop\Domain\Model\PaymentMethod
    {
        foreach ($available as $method) {
            if ($method->getUid() === $uid) {
                return $method;
            }
        }

        return $available[0] ?? null;
    }

    private function redirectToCart(): ResponseInterface
    {
        $cartPid = $this->intSetting('cartPid');
        if ($cartPid > 0) {
            return $this->redirectToUri(
                $this->uriBuilder->reset()->setTargetPageUid($cartPid)->build()
            );
        }

        return $this->redirect('show', 'Cart');
    }
}
