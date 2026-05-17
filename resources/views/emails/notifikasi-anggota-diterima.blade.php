<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota Disetujui</title>
</head>

<body style="
    margin:0;
    padding:32px 16px;
    background:#f3f4f6;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
    color:#111827;
">

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<table width="100%" cellpadding="0" cellspacing="0" style="
    max-width:560px;
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:14px;
">

    <!-- Top Accent -->
    <tr>
        <td style="height:4px; background:#111827;"></td>
    </tr>

    <!-- Content -->
    <tr>
        <td style="padding:40px 36px;">

            <!-- Brand -->
            <p style="
                margin:0 0 32px;
                font-size:14px;
                font-weight:600;
                letter-spacing:.3px;
                color:#111827;
            ">
                {{ $namaKoperasi }}
            </p>

            <!-- Title -->
            <h1 style="
                margin:0 0 24px;
                font-size:28px;
                line-height:36px;
                font-weight:600;
                color:#111827;
            ">
                Pendaftaran disetujui
            </h1>

            <!-- Description -->
            <p style="
                margin:0 0 18px;
                font-size:15px;
                line-height:28px;
                color:#374151;
            ">
                Halo {{ $user->nama_anggota }},
            </p>

            <p style="
                margin:0 0 18px;
                font-size:15px;
                line-height:28px;
                color:#374151;
            ">
                Pengajuan keanggotaan Anda telah disetujui dan akun sudah dapat digunakan untuk login ke sistem koperasi.
            </p>

            <p style="
                margin:0 0 28px;
                font-size:15px;
                line-height:28px;
                color:#374151;
            ">
                Berikut informasi akun Anda:
            </p>

            <!-- Credentials -->
            <table width="100%" cellpadding="0" cellspacing="0" style="
                border-collapse:collapse;
                margin-bottom:32px;
            ">

                <tr>
                    <td style="
                        width:120px;
                        padding:0 0 16px;
                        font-size:14px;
                        color:#6b7280;
                        vertical-align:top;
                    ">
                        Username
                    </td>

                    <td style="
                        padding:0 0 16px;
                        font-size:15px;
                        font-weight:500;
                        color:#111827;
                    ">
                        {{ $user->username }}
                    </td>
                </tr>

                <tr>
                    <td style="
                        width:120px;
                        padding:0;
                        font-size:14px;
                        color:#6b7280;
                        vertical-align:top;
                    ">
                        Password
                    </td>

                    <td style="
                        padding:0;
                        font-size:15px;
                        font-weight:500;
                        color:#111827;
                    ">
                        {{ $password }}
                    </td>
                </tr>

            </table>

            <!-- Button -->
            <a href="{{ url('login') }}" style="
                display:inline-block;
                padding:12px 18px;
                background:#111827;
                color:#ffffff;
                text-decoration:none;
                border-radius:8px;
                font-size:14px;
                font-weight:500;
            ">
                Login ke Sistem
            </a>

            <!-- Footer -->
            <p style="
                margin:32px 0 0;
                font-size:13px;
                line-height:24px;
                color:#6b7280;
            ">
                Demi keamanan akun, silakan ubah password setelah login pertama.
            </p>

        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>