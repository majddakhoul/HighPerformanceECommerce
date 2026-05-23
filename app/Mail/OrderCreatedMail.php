<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Invoice $invoice
    ) {}

    public function build()
    {
        return $this->subject('Order Confirmed')
            ->view('emails.order_created')
            ->with([
                'order'   => $this->order,
                'invoice' => $this->invoice,
                'user'    => $this->order->user,
            ]);
    }
}