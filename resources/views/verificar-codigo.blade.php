
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar código | Mi Sistema</title>

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
            padding: 20px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #172554, #2563eb);
        }

        .card {
            background: white;
            width: 100%;
            max-width: 430px;
            padding: 38px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .icono {
            font-size: 45px;
            margin-bottom: 10px;
        }

        h1 {
            color: #172554;
            font-size: 27px;
            margin-bottom: 12px;
        }

        p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        label {
            display: block;
            margin: 25px 0 10px;
            color: #334155;
            font-weight: bold;
            text-align: left;
        }

        input {
            width: 100%;
            padding: 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 22px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .volver {
            display: block;
            margin-top: 23px;
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
        }

        #mensaje {
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="card">

        <div class="icono">✉️</div>

        <h1>Verificar código</h1>

        <p>
            Ingresa el código de 6 dígitos
            generado para recuperar tu contraseña.
        </p>

        <form id="verificarForm">
            @csrf

            <label for="codigo">Código de verificación</label>

            <input
                type="text"
                id="codigo"
                placeholder="000000"
                maxlength="6"
                minlength="6"
                pattern="[0-9]{6}"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
            >

            <div id="mensaje" role="status" aria-live="polite"></div>

            <button type="submit">
                Verificar código
            </button>
        </form>

        <a href="/recuperar" class="volver">
            ← Volver a recuperación
        </a>

    </div>

<script>
document.getElementById('verificarForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const codigo = document.getElementById('codigo').value.trim();
    const correo = sessionStorage.getItem('correoRecuperacion');
    const mensaje = document.getElementById('mensaje');
    const boton = document.querySelector('#verificarForm button');

    mensaje.textContent = '';

    if (!correo) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Primero debes solicitar un código de recuperación.';
        return;
    }

    if (!/^[0-9]{6}$/.test(codigo)) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Ingresa un código válido de 6 dígitos.';
        return;
    }

    boton.disabled = true;
    boton.textContent = 'Verificando...';

    try {
        const respuesta = await fetch('/api/login/validar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                correo: correo,
                codigo: codigo
            })
        });

        const datos = await respuesta.json();

        if (respuesta.ok && datos.success) {
            mensaje.style.color = '#166534';
            mensaje.textContent = 'Código válido. Continuando...';

            // Se utilizará en el siguiente paso.
            // El servidor volverá a comprobarlo al cambiar la contraseña.
            sessionStorage.setItem('codigoRecuperacion', codigo);

            window.location.href = '/nueva-password';

        } else {
            mensaje.style.color = '#991b1b';

            if (respuesta.status === 429) {
                mensaje.textContent = 'Demasiados intentos. Espera unos minutos.';
            } else {
                mensaje.textContent = datos.message || 'Código incorrecto o expirado.';
            }
        }

    } catch (error) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Error de conexión con el servidor.';
    } finally {
        boton.disabled = false;
        boton.textContent = 'Verificar código';
    }
});
</script>

</body>
</html>
