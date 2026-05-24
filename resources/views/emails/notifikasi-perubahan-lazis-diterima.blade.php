<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Perubahan LAZIS Disetujui</title>
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

    <!-- Accent -->
    <tr>
        <td style="height:4px; background:#10b981;"></td>
    </tr>

    <!-- Content -->
    <tr>
        <td style="padding:40px 36px;">

            <p style="
                margin:0 0 32px;
                font-size:14px;
                font-weight:600;
                color:#111827;
            ">
                {{ $namaKoperasi }}
            </p>

            <h1 style="
                margin:0 0 24px;
                font-size:28px;
                line-height:36px;
                font-weight:600;
                color:#111827;
            ">
                Pengajuan Perubahan LAZIS Disetujui
            </h1>

            <p style="
                margin:0 0 18px;
                font-size:15px;
                line-height:28px;
                color:#374151;
            ">
                Halo {{ $namaAnggota }},
            </p>

            <p style="
                margin:0 0 24px;
                font-size:15px;
                line-height:28px;
                color:#374151;
            ">
                Pengajuan perubahan setoran rutin bulanan <strong>{{ $jenisLazis }}</strong> Anda telah disetujui oleh pengurus koperasi.
            </p>

            <!-- Detail -->
            <table width="100%" cellpadding="0" cellspacing="0" style="
                border-collapse:collapse;
                margin-bottom:32px;
            ">

                <tr>
                    <td style="
                        width:180px;
                        padding:0 0 16px;
                        font-size:14px;
                        color:#6b7280;
                        vertical-align:top;
                    ">
                        Program LAZIS
                    </td>

                    <td style="
                        padding:0 0 16px;
                        font-size:15px;
                        font-weight:600;
                        color:#111827;
                    ">
                        {{ $jenisLazis }}
                    </td>
                </tr>

                <tr>
                    <td style="
                        width:180px;
                        padding:0 0 16px;
                        font-size:14px;
                        color:#6b7280;
                        vertical-align:top;
                    ">
                        Setoran Sebelumnya
                    </td>

                    <td style="
                        padding:0 0 16px;
                        font-size:15px;
                        font-weight:500;
                        color:#111827;
                    ">
                        Rp {{ number_format($nominalSebelum, 0, ',', '.') }}
                    </td>
                </tr>

                <tr>
                    <td style="
                        width:180px;
                        padding:0;
                        font-size:14px;
                        color:#6b7280;
                        vertical-align:top;
                    ">
                        Setoran Baru
                    </td>

                    <td style="
                        padding:0;
                        font-size:15px;
                        font-weight:700;
                        color:#10b981;
                    ">
                        Rp {{ number_format($nominalSesudah, 0, ',', '.') }}
                    </td>
                </tr>

            </table>

            <p style="
                margin:0;
                font-size:14px;
                line-height:26px;
                color:#6b7280;
            ">
                Perubahan potongan payroll untuk program {{ $jenisLazis }} ini akan diterapkan mulai bulan berikutnya sesuai dengan siklus payroll koperasi. Terima kasih atas partisipasi dan kepedulian Anda.
            </p>

        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
