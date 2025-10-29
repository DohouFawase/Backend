<?php

namespace App\Mail;

use App\Models\AdVersion;
use App\Models\PropertyContact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;
    public $adVersion;

    /**
     * Create a new message instance.
     */
    public function __construct(PropertyContact $contact, AdVersion $adVersion)
    {
        $this->contact = $contact;
        $this->adVersion = $adVersion;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🏠 Nouveau contact pour votre annonce : ' . $this->adVersion->seo_description,
            replyTo: [$this->contact->visitor_email],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
                        markdown: 'mail.proprty-contact',

            with: [
                'visitorName' => $this->contact->visitor_name,
                'visitorEmail' => $this->contact->visitor_email,
                'visitorPhone' => $this->contact->visitor_phone,
                'message' => $this->contact->message,
                'propertyTitle' => $this->adVersion->seo_description,
                'propertyType' => $this->adVersion->propertyType->name ?? 'N/A',
                'propertyCity' => $this->adVersion->city,
                'propertyPrice' => number_format($this->adVersion->price, 0, ',', ' ') . ' ' . $this->adVersion->currency,
                'dashboardUrl' => config('app.frontend_url') . '/dashboard/contacts/' . $this->contact->id,
                'adUrl' => config('app.frontend_url') . '/ads/' . $this->adVersion->id,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
