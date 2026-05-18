<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifikasiPerubahanSimpananSukarela extends Mailable
{
    use Queueable, SerializesModels;

    public string $namaAnggota;
    public string $namaKoperasi = 'Koperasi Konsumen Incoe';
    public ?string $nominalSebelum;
    public ?string $nominalSesudah;
    public string $statusApprove;
    public ?string $alasanPenolakan = 'Terdapat data yang tidak valid.';

    /**
     * Create a new message instance.
     */
    public function __construct(string $namaAnggota, string $statusApprove, ?string $nominalSebelum = null, ?string $nominalSesudah = null, string $alasanPenolakan = null)
    {
        $this->namaAnggota = $namaAnggota;
        $this->nominalSebelum = $nominalSebelum;
        $this->nominalSesudah = $nominalSesudah;
        $this->statusApprove = $statusApprove;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notifikasi Perubahan Simpanan Sukarela',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->statusApprove == 'approved' ? 'emails.notifikasi-perubahan-sukarela-diterima' : 'emails.notifikasi-perubahan-sukarela-ditolak';

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
