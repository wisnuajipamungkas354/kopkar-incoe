<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifikasiPersetujuanPembiayaan extends Mailable
{
    use Queueable, SerializesModels;

    public string $namaAnggota;
    public string $namaKoperasi = 'Koperasi Konsumen Incoe';
    public string $nomorPengajuan;
    public string $statusApprove;
    public float $nominal;
    public ?string $alasanPenolakan = 'Terdapat rincian data yang tidak memenuhi persyaratan.';

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $namaAnggota,
        string $statusApprove,
        string $nomorPengajuan,
        float $nominal,
        ?string $alasanPenolakan = null
    ) {
        $this->namaAnggota = $namaAnggota;
        $this->statusApprove = $statusApprove;
        $this->nomorPengajuan = $nomorPengajuan;
        $this->nominal = $nominal;
        if ($alasanPenolakan) {
            $this->alasanPenolakan = $alasanPenolakan;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->statusApprove) {
            'disetujui_bendahara' => 'Notifikasi Pengajuan Pembiayaan disetujui Bendahara',
            'disetujui_ketua' => 'Notifikasi Pengajuan Pembiayaan disetujui Ketua',
            default => 'Notifikasi Pengajuan Pembiayaan Ditolak',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = ($this->statusApprove === 'disetujui_bendahara' || $this->statusApprove === 'disetujui_ketua')
            ? 'emails.notifikasi-pembiayaan-disetujui'
            : 'emails.notifikasi-pembiayaan-ditolak';

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
