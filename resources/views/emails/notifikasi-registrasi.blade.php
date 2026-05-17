<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota Berhasil</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding:40px 20px;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg, #16a34a, #22c55e); padding:32px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:28px;">
                                Pendaftaran Berhasil
                            </h1>
                            <p style="margin:10px 0 0; color:#dcfce7; font-size:15px;">
                                Selamat datang di Koperasi Konsumen Incoe
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:40px 32px; color:#334155;">

                            <p style="font-size:16px; margin-top:0;">
                                Halo <strong>{{ $user->nama_anggota }}</strong>,
                            </p>

                            <p style="font-size:15px; line-height:1.8;">
                                Terima kasih telah melakukan pendaftaran sebagai anggota 
                                <strong>Koperasi Konsumen Incoe</strong>.
                            </p>

                            <p style="font-size:15px; line-height:1.8;">
                                Data pendaftaran Anda telah berhasil kami terima dan saat ini sedang 
                                dalam proses verifikasi serta menunggu persetujuan dari ketua koperasi.
                            </p>

                            <!-- Status Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td style="background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:18px;">
                                        <p style="margin:0; font-size:14px; color:#166534;">
                                            <strong>Status Pendaftaran:</strong><br>
                                            Menunggu Approval Ketua
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:15px; line-height:1.8;">
                                Kami akan mengirimkan pemberitahuan kembali melalui email apabila 
                                pendaftaran Anda telah disetujui.
                            </p>

                            <p style="font-size:15px; line-height:1.8;">
                                Mohon menunggu beberapa saat dan pastikan email ini tetap aktif.
                            </p>

                            <p style="margin-top:32px; font-size:15px;">
                                Hormat kami,<br>
                                <strong>Tim Koperasi Konsumen Incoe</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; padding:20px; text-align:center; font-size:13px; color:#94a3b8;">
                            © {{ date('Y') }} Koperasi Konsumen Incoe. All rights reserved.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>