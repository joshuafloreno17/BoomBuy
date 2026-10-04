<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A checkout that can't go through, with the message to show the buyer.
 * $backToCart: the problem is in the cart itself (send them to the cart page)
 * rather than in the checkout form.
 */
class CheckoutFailed extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $backToCart = false,
        public readonly bool $voucherDropped = false,
    ) {
        parent::__construct($message);
    }

    public static function inCart(string $message): self
    {
        return new self($message, backToCart: true);
    }

    public static function inForm(string $message): self
    {
        return new self($message);
    }
}
