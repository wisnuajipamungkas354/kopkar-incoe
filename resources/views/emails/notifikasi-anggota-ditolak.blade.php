<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota Ditolak</title>
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
        <td style="height:4px; background:#dc2626;"></td>
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
                Pendaftaran belum dapat disetujui
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
                Setelah dilakukan peninjauan, pengajuan keanggotaan Anda belum dapat disetujui.
            </p>

            <p style="
                margin:0 0 24px;
                font-size:15px;
                line-height:28px;
                color:#374151;
            ">
                Alasan penolakan:
            </p>

            <!-- Reason -->
            <div style="
                padding:18px 20px;
                background:#f9fafb;
                border:1px solid #e5e7eb;
                border-radius:10px;
                margin-bottom:28px;
            ">
                <p style="
                    margin:0;
                    font-size:15px;
                    line-height:28px;
                    color:#111827;
                ">
                    {{ $alasanPenolakan }}
                </p>
            </div>

            <!-- Footer -->
            <p style="
                margin:0;
                font-size:13px;
                line-height:24px;
                color:#6b7280;
            ">
                Anda dapat melakukan pendaftaran ulang setelah data diperbaiki sesuai kebutuhan.
            </p>

        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>