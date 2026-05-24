<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Pembiayaan Ditolak</title>
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
        <td style="height:4px; background:#dc2626;"></td>
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
                Pengajuan Belum Disetujui
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
                Pengajuan pembiayaan Anda dengan nomor <strong>{{ $nomorPengajuan }}</strong> belum dapat disetujui oleh pengurus koperasi.
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
                    margin:0 0 8px;
                    font-size:13px;
                    color:#6b7280;
                ">
                    Alasan Penolakan
                </p>

                <p style="
                    margin:0;
                    font-size:15px;
                    line-height:28px;
                    color:#111827;
                ">
                    {{ $alasanPenolakan }}
                </p>

            </div>

            <p style="
                margin:0;
                font-size:14px;
                line-height:26px;
                color:#6b7280;
            ">
                Anda dapat melakukan pengajuan ulang dengan data yang sesuai atau menghubungi bagian administrasi Koperasi Konsumen Incoe untuk rincian atau bantuan lebih lanjut.
            </p>

        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
