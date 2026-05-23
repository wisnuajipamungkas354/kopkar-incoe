<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifikasiApprovalAnggotaBaru extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $namaKoperasi = 'Koperasi Konsumen Incoe';
    public $password = '1234';

    /**
     * Create a new message instance.
     */
    public function __construct($member, $newPassword = null)
    {
        $this->member = $member;
        $this->password = $newPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notifikasi Approval Anggota Baru - ' . $this->member->employee->nama_lengkap,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->member->is_approved ? 'emails.notifikasi-anggota-diterima' : 'emails.notifikasi-anggota-ditolak';

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
