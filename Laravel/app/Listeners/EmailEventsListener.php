<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use App\Services\SystemLogService;
use Symfony\Component\Mime\Email;

class EmailEventsListener
{
    /**
     * Handle message sending event (before email is sent).
     * Useful for debugging, but can be noisy in production.
     */
    public function handleMessageSending(MessageSending $event): void
    {
        // Optional: Log only in debug mode
        // Uncomment if you want to log before sending
        /*
        $message = $event->message;
        
        SystemLogService::email()->debug('Email sending', [
            'to' => $this->formatAddresses($message->getTo()),
            'subject' => $message->getSubject(),
        ]);
        */
    }

    /**
     * Handle message sent event (after email is successfully sent).
     */
    public function handleMessageSent(MessageSent $event): void
    {
        $message = $event->message;

        // Extract email details
        $to = $this->formatAddresses($message->getTo());
        $cc = $this->formatAddresses($message->getCc());
        $bcc = $this->formatAddresses($message->getBcc());
        $subject = $message->getSubject();

        // Try to get the Mailable class name if available
        $mailableClass = null;
        if (isset($event->data['__laravel_notification'])) {
            $mailableClass = get_class($event->data['__laravel_notification']);
        } elseif (isset($event->data['mailable'])) {
            $mailableClass = get_class($event->data['mailable']);
        }

        // Build context
        $context = [
            'to' => $to,
            'subject' => $subject,
        ];

        if (!empty($cc)) {
            $context['cc'] = $cc;
        }

        if (!empty($bcc)) {
            $context['bcc'] = $bcc;
        }

        if ($mailableClass) {
            $context['mailable_class'] = $mailableClass;
        }

        // Log the sent email
        SystemLogService::email()->info('Email sent successfully', $context);
    }

    /**
     * Format email addresses array to string.
     *
     * @param array|null $addresses
     * @return string|null
     */
    private function formatAddresses($addresses): ?string
    {
        if (empty($addresses)) {
            return null;
        }

        // Handle Symfony Address objects
        $formatted = [];
        foreach ($addresses as $address) {
            if (is_object($address) && method_exists($address, 'getAddress')) {
                $formatted[] = $address->getAddress();
            } elseif (is_object($address) && method_exists($address, 'toString')) {
                $formatted[] = $address->toString();
            } elseif (is_string($address)) {
                $formatted[] = $address;
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     * @return array<string, string>
     */
    public function subscribe($events): array
    {
        return [
            MessageSending::class => 'handleMessageSending',
            MessageSent::class => 'handleMessageSent',
        ];
    }
}
