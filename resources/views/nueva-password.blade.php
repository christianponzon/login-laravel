
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña | Mi Sistema</title>

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
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .icono {
            text-align: center;
            font-size: 45px;
            margin-bottom: 10px;
        }

        h1 {
            color: #172554;
            text-align: center;
            font-size: 27px;
            margin-bottom: 12px;
        }

        p {
            color: #64748b;
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 16px 0 8px;
            color: #334155;
            font-weight: bold;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .volver {
            display: block;
            margin-top: 22px;
            text-align: center;
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

        <div class="icono">🔒</div>

        <h1>Nueva contraseña</h1>

        <p>
            Ingresa y confirma tu nueva contraseña
            para recuperar el acceso a tu cuenta.
        </p>

        <form id="passwordForm">
            @csrf

            <label for="passwordd">Nueva contraseña</label>
            <input
                type="password"
                id="passwordd"
                placeholder="Mínimo 8 caracteres"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >

            <label for="confirmar">Confirmar contraseña</label>
            <input
                type="password"
                id="confirmar"
                placeholder="Repite tu nueva contraseña"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >

            <div id="mensaje" role="status" aria-live="polite"></div>

            <button type="submit">
                Cambiar contraseña
            </button>
        </form>

        <a href="/login" class="volver">
            ← Volver al inicio de sesión
        </a>

    </div>

<script>
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const passwordd = document.getElementById('passwordd').value;
    const confirmar = document.getElementById('confirmar').value;
    const mensaje = document.getElementById('mensaje');
    const boton = document.querySelector('#passwordForm button');

    const correo = sessionStorage.getItem('correoRecuperacion');
    const codigo = sessionStorage.getItem('codigoRecuperacion');

    mensaje.textContent = '';

    if (!correo || !codigo) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Primero debes verificar tu código de recuperación.';
        return;
    }

    if (passwordd.length < 8 || passwordd.length > 72) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'La contraseña debe tener entre 8 y 72 caracteres.';
        return;
    }

    if (passwordd !== confirmar) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Las contraseñas no coinciden.';
        return;
    }

    boton.disabled = true;
    boton.textContent = 'Guardando...';

    try {
        const respuesta = await fetch('/api/login/passwordd', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                correo: correo,
                codigo: codigo,
                passwordd: passwordd,
                passwordd_confirmation: confirmar
            })
        });

        const datos = await respuesta.json();

        if (respuesta.ok && datos.success) {
            sessionStorage.removeItem('correoRecuperacion');
            sessionStorage.removeItem('codigoRecuperacion');

            mensaje.style.color = '#166534';
            mensaje.textContent = 'Contraseña actualizada correctamente.';

            setTimeout(() => {
                window.location.href = '/login';
            }, 1500);

        } else {
            mensaje.style.color = '#991b1b';

            if (respuesta.status === 429) {
                mensaje.textContent = 'Demasiados intentos. Espera unos minutos.';
            } else {
                mensaje.textContent = datos.message || 'No se pudo cambiar la contraseña. Verifica el código.';
            }
        }

    } catch (error) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Error de conexión con el servidor.';
    } finally {
        boton.disabled = false;
        boton.textContent = 'Cambiar contraseña';
    }
});
</script>

</body>
</html>
