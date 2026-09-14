<?php

namespace App\Mail;

use App\Models\PatientHistoryRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * The radiologist's final report, sent to the patient with the PDF attached.
 *
 * Unlike ReportReadyMail (which points at the OTP portal), this one carries the
 * document itself — the radiologist is sending the report, not an invitation.
 */
class RadiologyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PatientHistoryRecord $record,
        public string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Pink Caravan mammography report · تقرير التصوير الشعاعي للثدي من القافلة الوردية',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.radiology-report',
            with: [
                'ref'  => $this->record->ref_no,
                'link' => $this->link,
                'name' => $this->record->patient?->full_name,
            ],
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $path = $this->record->radiology_report_path;

        if (! $path || ! Storage::exists($path)) {
            return [];
        }

        return [
            Attachment::fromStorage($path)
                ->as('pink-caravan-mammography-report-'.$this->record->ref_no.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
