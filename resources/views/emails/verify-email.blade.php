<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Email Anggota</title>
</head>
<body style="font-family: Arial; background:#f5f5f5; padding:20px;">

    <div style="
        max-width:600px;
        margin:auto;
        background:white;
        padding:40px;
        border-radius:12px;
    ">

        <h1>Verifikasi Email Anggota Baru</h1>

        <p>
            Halo {{ $user->nama_anggota }},
        </p>

        <p>
            Terima kasih telah mendaftar sebagai anggota koperasi.
            Silakan verifikasi email Anda dengan menekan tombol berikut untuk melanjutkan proses pendaftaran:
        </p>

        <a href="{{ $url }}"
           style="
            display:inline-block;
            background:#16a34a;
            color:white;
            padding:14px 24px;
            border-radius:8px;
            text-decoration:none;
            margin-top:20px;
           ">
            Verifikasi Email
        </a>

    </div>

</body>
</html>