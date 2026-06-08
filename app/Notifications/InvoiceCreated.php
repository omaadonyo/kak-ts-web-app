<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $book = $this->invoice->bookService;
        $client = $book->user;

        return (new MailMessage)
            ->subject("Invoice #{$this->invoice->invoice_number} – {$book->service_type}")
            ->greeting("Hello {$client->name},")
            ->line("An invoice has been created for your service request: **{$book->service_type}** at {$book->location}.")
            ->line("**Invoice #:** {$this->invoice->invoice_number}")
            ->line("**Total:** UGX " . number_format($this->invoice->total, 2))
            ->line("**Status:** Sent")
            ->action('View Invoice', route('invoices.show', $book->id))
            ->line('Please review the invoice and proceed with payment at your earliest convenience.')
            ->salutation('Thank you, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total' => $this->invoice->total,
        ];
    }
}
