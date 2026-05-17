<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota Ditolak</title>
</head>

<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;">
<tr>
<td align="center">

<table width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; background:#ffffff; border-radius:16px; overflow:hidden;">

    <!-- Header -->
    <tr>
        <td align="center"
            style="background:linear-gradient(135deg,#dc2626,#ef4444); padding:40px 24px;">

            <h1 style="margin:0; color:#ffffff; font-size:30px;">
                Pendaftaran Ditolak
            </h1>

            <p style="margin:12px 0 0; color:#fee2e2; font-size:16px;">
                Informasi hasil pengajuan anggota
            </p>

        </td>
    </tr>

    <!-- Content -->
    <tr>
        <td style="padding:40px 28px; color:#334155;">

            <p style="font-size:16px; line-height:28px;">
                Halo <strong>{{ $user->nama_anggota }}</strong>,
            </p>

            <p style="font-size:15px; line-height:28px; color:#475569;">
                Mohon maaf, pengajuan pendaftaran anggota koperasi Anda saat ini
                <strong>belum dapat disetujui</strong> oleh ketua koperasi.
            </p>

            <p style="font-size:15px; line-height:28px; color:#475569;">
                Berikut alasan penolakan:
            </p>

            <!-- Reason Box -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="
                        background:#fef2f2;
                        border:1px solid #fecaca;
                        border-radius:12px;
                        margin:24px 0;
                   ">

                <tr>
                    <td style="padding:24px;">

                        <p style="
                            margin:0;
                            font-size:15px;
                            line-height:28px;
                            color:#991b1b;
                        ">
                            {{ $user->catatan_approval ?? 'Terdapat data yang tidak valid!' }}
                        </p>

                    </td>
                </tr>

            </table>

            <p style="font-size:15px; line-height:28px; color:#475569;">
                Jika terdapat kesalahan data atau informasi tambahan yang perlu disampaikan,
                silakan hubungi pihak koperasi.
            </p>

            <p style="margin-top:40px; font-size:15px; line-height:28px; color:#475569;">
                Hormat kami,<br>
                <strong>Tim Koperasi Konsumen Incoe</strong>
            </p>

        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td align="center"
            style="background:#f8fafc; padding:24px; font-size:13px; color:#94a3b8;">

            © {{ date('Y') }} Koperasi Konsumen Incoe. All rights reserved.

        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>