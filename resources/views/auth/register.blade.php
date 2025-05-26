<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        label {
            display: block;
            font-size: 14px;
            margin-bottom: 6px;
            color: #555;
        }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="password"]:focus, input[type="text"]:focus, input[type="email"]:focus, select:focus {
            border-color: #4c9fff;
            outline: none;
        }
        button {
            background-color: #4c9fff;
            color: #fff;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #357ae8;
        }
        .form-footer {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Inscription</h2>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <label for="nom">Nom</label>
            <input type="text" name="nom" id="nom" required>

            <label for="prenom">Prénom</label>
            <input type="text" name="prenom" id="prenom" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" required>

            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required>

            <label for="role">Rôle</label>
            <select name="role" id="role" required>
                <option value="administrateur_it">Administrateur IT</option>
                <option value="administrateur">Administrateur</option>
                <option value="manager">Manager</option>
                <option value="secretaire">Secrétaire</option>
                <option value="agent paie">Agent Paie</option>
            </select>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <button type="submit">S'inscrire</button>
        </form>
        <div class="form-footer">
            <p>Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
        </div>
    </div>
</body>
</html>