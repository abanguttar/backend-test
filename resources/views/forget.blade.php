<!DOCTYPE html>
<html>

<head>
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #414393;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>{{ $subject }}</h2>
        </div>
        <p>Hi, {{ $name }},</p>
        <p>Klik link dibawah ini untuk memperbarui kata sandi kamu:</p>
        <p style="text-align: center;">
            <a href="{{ $url }}" class="button"><span style="color: white;"><b>Ubah Kata Sandi</b></span></a>
        </p>
        <p>Salam Hangat,<br><span style="font-weight: 700;">Paketur</span></p>
        <div class="footer">
            <p>Jika link diatas tidak bekerja, salin link dibawah ini ke dalam browser:</p>
            <p><a href="{{ $url }}">{{ $url }}</a></p>
        </div>
    </div>
</body>

</html>
