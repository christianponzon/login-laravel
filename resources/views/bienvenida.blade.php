<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenida | Mi Sistema</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #172554, #2563eb);
        }

        .card {
            background: white;
            padding: 45px;
            width: 90%;
            max-width: 450px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #172554;
            margin-bottom: 12px;
        }

        p {
            color: #64748b;
        }

        button {
            margin-top: 20px;
            padding: 13px 30px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>¡Hola, {{ $nombre }}!</h1>
        <p>Bienvenido a nuestro sistema.</p>

        <form method="POST" action="/logout">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</body>
</html>
