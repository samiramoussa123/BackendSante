<!DOCTYPE html>
<html>
<body>
    <h2>Bonjour {{ $user->prenom }},</h2>
    <p>Cliquez sur le lien ci-dessous pour vérifier votre email :</p>
    <a href="{{ $url }}">Vérifier mon email</a>
</body>
</html>