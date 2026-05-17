<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota Diterima</title>
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
            style="background:linear-gradient(135deg,#16a34a,#22c55e); padding:40px 24px;">

            <h1 style="margin:0; color:#ffffff; font-size:30px;">
                Pendaftaran Diterima 🎉
            </h1>

            <p style="margin:12px 0 0; color:#dcfce7; font-size:16px;">
                Selamat bergabung di Koperasi Konsumen Incoe
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
                Selamat! Pengajuan pendaftaran anggota koperasi Anda telah
                <strong>disetujui</strong> oleh ketua koperasi.
            </p>

            <p style="font-size:15px; line-height:28px; color:#475569;">
                Berikut informasi akun Anda:
            </p>

            <!-- Account Box -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="
                        background:#f8fafc;
                        border:1px solid #e2e8f0;
                        border-radius:12px;
                        margin:24px 0;
                   ">

                <tr>
                    <td style="padding:24px;">

                        <p style="margin:0 0 14px; font-size:14px; color:#64748b;">
                            USERNAME
                        </p>

                        <p style="margin:0 0 24px; font-size:16px; font-weight:bold; color:#0f172a;">
                            {{ $user->username }}
                        </p>

                        <p style="margin:0 0 14px; font-size:14px; color:#64748b;">
                            PASSWORD
                        </p>

                        <p style="margin:0; font-size:16px; font-weight:bold; color:#0f172a;">
                            {{ $user->password }}
                        </p>

                    </td>
                </tr>

            </table>

            <p style="font-size:15px; line-height:28px; color:#475569;">
                Demi keamanan akun, kami menyarankan Anda untuk segera mengganti password setelah login pertama.
            </p>

            <!-- Button -->
            <table cellpadding="0" cellspacing="0" style="margin-top:32px;">
                <tr>
                    <td align="center"
                        style="background:#16a34a; border-radius:10px;">

                        <a href="{{ url('login') }}"
                           style="
                                display:inline-block;
                                padding:14px 28px;
                                color:#ffffff;
                                text-decoration:none;
                                font-size:15px;
                                font-weight:bold;
                           ">
                            Login Sekarang
                        </a>

                    </td>
                </tr>
            </table>

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