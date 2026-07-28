<?php

namespace App\Mail;

use App\Models\PatientHistoryRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PatientHistoryRecord $record,
        public string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Pink Caravan report is ready · تقريرك من القافلة الوردية جاهز',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.report-ready',
            with: [
                'ref'  => $this->record->ref_no,
                'link' => $this->link,
                'name' => $this->record->patient?->full_name,
            ],
        );
    }
}
