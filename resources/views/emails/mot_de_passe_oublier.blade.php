<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; }
        .container { background:#fff; padding:30px; border-radius:8px; max-width:500px; margin:auto; }
        .btn { 
            display:inline-block; 
            padding:12px 24px; 
            background:#007bff; 
            color:#ffffff !important; 
            text-decoration:none; 
            border-radius:5px; 
            margin-top:20px;
            font-weight:bold;
        }
        .footer { margin-top:20px; font-size:12px; color:#999; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Réinitialisation de mot de passe</h2>
        <p>Bonjour <strong>{{ $user->prenom }}</strong>,</p>
        <p>Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe (valable <strong>60 minutes</strong>) :</p>

        {{-- ✅ Lien cliquable --}}
        <a href="{{ $url }}" class="btn">Réinitialiser mon mot de passe</a>

        {{-- ✅ URL visible en texte brut aussi --}}
        <p style="margin-top:20px; word-break:break-all; color:#555; font-size:13px;">
            Ou copiez ce lien : {{ $url }}
        </p>

        <div class="footer">
            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            <p>— L'équipe FontSanté</p>
        </div>
    </div>
</body>
</html>