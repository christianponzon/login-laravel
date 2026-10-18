
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar correo | Login Laravel</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #172554, #2563eb);
        }

        .card {
            width: 100%;
            max-width: 430px;
            padding: 38px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .icono {
            font-size: 45px;
            text-align: center;
            margin-bottom: 12px;
        }

        h1 {
            color: #172554;
            font-size: 26px;
            text-align: center;
            margin-bottom: 12px;
        }

        .descripcion {
            color: #64748b;
            font-size: 14px;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 5px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
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

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .reenviar {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .reenviar a,
        .volver {
            color: #2563eb;
            text-decoration: none;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
        }

        #mensaje {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="card">

        <div class="icono">📧</div>

        <h1>Verificar correo</h1>

        <p class="descripcion">
            Hemos preparado un código de verificación
            de 6 dígitos para tu correo electrónico.
            Revisa tu bandeja de entrada e ingrésalo aquí.
        </p>

        <form id="verificarCorreoForm">
            @csrf

            <label for="codigo">
                Código de verificación
            </label>

            <input
                type="text"
                id="codigo"
                name="codigo"
                placeholder="000000"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                autocomplete="one-time-code"
                required
            >

            <div id="mensaje" role="status" aria-live="polite"></div>

            <button type="submit">
                Verificar mi correo
            </button>

        </form>

        <div class="reenviar">
            ¿No recibiste el código?
            <a href="#" id="reenviarCodigo">Reenviar código</a>
        </div>

        <a href="/login" class="volver">
            ← Volver al inicio de sesión
        </a>

    </div>

<script>
document.getElementById('verificarCorreoForm')
    .addEventListener('submit', async function(e) {

    e.preventDefault();

    const codigo = document.getElementById('codigo').value.trim();
    const correo = sessionStorage.getItem('correoRegistro');
    const mensaje = document.getElementById('mensaje');
    const boton = document.querySelector('#verificarCorreoForm button');

    mensaje.textContent = '';

    // Comprobar que tenemos el correo del registro
    if (!correo) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent =
            'No encontramos tu correo. Vuelve a la pantalla de registro.';
        return;
    }

    // Validar los seis dígitos
    if (!/^[0-9]{6}$/.test(codigo)) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Ingresa un código válido de 6 dígitos.';
        return;
    }

    boton.disabled = true;
    boton.textContent = 'Verificando...';

    try {
        const respuesta = await fetch('/api/login/verificar-correo', {
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
            mensaje.textContent =
                '¡Correo verificado correctamente!';

            // Eliminar el correo temporal del registro
            sessionStorage.removeItem('correoRegistro');

            // Regresar al Login
            setTimeout(() => {
                window.location.href = '/login';
            }, 1500);

        } else {
            mensaje.style.color = '#991b1b';

            if (respuesta.status === 429) {
                mensaje.textContent =
                    'Demasiados intentos. Espera unos minutos.';
            } else {
                mensaje.textContent =
                    datos.message || 'Código incorrecto o expirado.';
            }
        }

    } catch (error) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent =
            'No se pudo conectar con el servidor.';
    } finally {
        boton.disabled = false;
        boton.textContent = 'Verificar mi correo';
    }
});
</script>

<script>
document.getElementById('reenviarCodigo')
    .addEventListener('click', async function(e) {

    e.preventDefault();

    const correo = sessionStorage.getItem('correoRegistro');
    const mensaje = document.getElementById('mensaje');
    const enlace = document.getElementById('reenviarCodigo');

    if (!correo) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent =
            'No encontramos tu correo. Vuelve al registro.';
        return;
    }

    // Evitar varios clics mientras se procesa
    enlace.style.pointerEvents = 'none';
    enlace.style.opacity = '0.5';

    mensaje.style.color = '#334155';
    mensaje.textContent = 'Enviando nuevo código...';

    try {
        const respuesta = await fetch('/api/login/reenviar-codigo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                correo: correo
            })
        });

        const datos = await respuesta.json();

        if (respuesta.ok && datos.success) {
            mensaje.style.color = '#166534';
            mensaje.textContent = datos.message;
        } else {
            mensaje.style.color = '#991b1b';

            if (respuesta.status === 429) {
                mensaje.textContent =
                    'Has solicitado demasiados códigos. Espera 10 minutos.';
            } else {
                mensaje.textContent =
                    datos.message || 'No se pudo reenviar el código.';
            }
        }

    } catch (error) {
        mensaje.style.color = '#991b1b';
        mensaje.textContent =
            'Error de conexión con el servidor.';
    } finally {
        enlace.style.pointerEvents = 'auto';
        enlace.style.opacity = '1';
    }
});
</script>

</body>
</html>
