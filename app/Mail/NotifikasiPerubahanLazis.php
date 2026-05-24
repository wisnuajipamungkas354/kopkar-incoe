<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifikasiPerubahanLazis extends Mailable
{
    use Queueable, SerializesModels;

    public string $namaAnggota;
    public string $namaKoperasi = 'Koperasi Konsumen Incoe';
    public string $jenisLazis;
    public ?string $nominalSebelum;
    public ?string $nominalSesudah;
    public string $statusApprove;
    public ?string $alasanPenolakan = 'Terdapat data yang tidak valid.';

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $namaAnggota,
        string $statusApprove,
        string $jenisLazis,
        ?string $nominalSebelum = null,
        ?string $nominalSesudah = null,
        ?string $alasanPenolakan = null
    ) {
        $this->namaAnggota = $namaAnggota;
        $this->statusApprove = $statusApprove;
        $this->jenisLazis = $jenisLazis;
        $this->nominalSebelum = $nominalSebelum;
        $this->nominalSesudah = $nominalSesudah;
        if ($alasanPenolakan) {
            $this->alasanPenolakan = $alasanPenolakan;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notifikasi Perubahan Potongan LAZIS',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->statusApprove == 'disetujui' || $this->statusApprove == 'approved'
            ? 'emails.notifikasi-perubahan-lazis-diterima'
            : 'emails.notifikasi-perubahan-lazis-ditolak';

        return new Content(
            view: $view,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
