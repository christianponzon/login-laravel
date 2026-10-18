
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse | Mi Sistema</title>

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
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #172554, #2563eb);
            padding: 25px;
        }

        .register-card {
            background: white;
            width: 100%;
            max-width: 470px;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        h1 {
            text-align: center;
            color: #172554;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 14px 0 7px;
            font-weight: bold;
            color: #334155;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .row {
            display: flex;
            gap: 12px;
        }

        .row > div {
            flex: 1;
            min-width: 0;
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 25px;
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

        .login-link {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 14px;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="register-card">

        <h1>Crear cuenta</h1>
        <p class="subtitle">Completa tus datos para registrarte</p>

        <form id="registroForm">

            @csrf

            <div class="row">
                <div>
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre"
                           placeholder="Tu nombre" required>
                </div>

                <div>
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido"
                           placeholder="Tu apellido" required>
                </div>
            </div>

            <label for="cumpleanos">Fecha de nacimiento</label>
            <input type="date" id="cumpleanos" required>

            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo"
                   placeholder="ejemplo@correo.com" required>

            <label for="passwordd">Contraseña</label>
            <input type="password" id="passwordd"
                   placeholder="Mínimo 8 caracteres"
                   minlength="8" required>

            <label for="confirmar">Confirmar contraseña</label>
            <input type="password" id="confirmar"
                   placeholder="Repite tu contraseña"
                   minlength="8" required>

            <div id="mensaje" role="alert" aria-live="polite"></div>

            <button type="submit">Registrarse</button>

        </form>

        <p class="login-link">
            ¿Ya tienes una cuenta?
            <a href="/login">Inicia sesión</a>
        </p>

    </div>

<script>
document.getElementById('registroForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const nombre = document.getElementById('nombre').value.trim();
    const apellido = document.getElementById('apellido').value.trim();
    const cumpleanos = document.getElementById('cumpleanos').value;
    const correo = document.getElementById('correo').value.trim();
    const passwordd = document.getElementById('passwordd').value;
    const confirmar = document.getElementById('confirmar').value;
    const mensaje = document.getElementById('mensaje');

    mensaje.textContent = '';
    mensaje.style.padding = '0';

    if (passwordd !== confirmar) {
        mensaje.textContent = 'Las contraseñas no coinciden.';
        mensaje.style.color = '#991b1b';
        return;
    }

    try {
        const respuesta = await fetch('/api/login/registrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                nombre: nombre,
                apellido: apellido,
                cumpleanos: cumpleanos,
                correo: correo,
                passwordd: passwordd,
                passwordd_confirmation: confirmar
            })
        });

        const datos = await respuesta.json();

        mensaje.style.padding = '12px';
        mensaje.style.borderRadius = '8px';
        mensaje.style.marginTop = '15px';

        
if (respuesta.ok && datos.success) {

    // Guardar el correo para la pantalla de verificación
    sessionStorage.setItem('correoRegistro', correo);

    mensaje.style.background = '#dcfce7';
    mensaje.style.color = '#166534';

    if (datos.correo_enviado) {
        mensaje.textContent =
            '¡Cuenta creada correctamente! Revisa tu Gmail para obtener el código de verificación.';
    } else {
        mensaje.textContent =
            'Cuenta creada, pero no se pudo enviar el código. Podrás solicitar uno nuevo.';
    }

    document.getElementById('registroForm').reset();

    // Redirigir a la verificación de correo
    setTimeout(() => {
        window.location.href = '/verificar-correo';
    }, 1500);

}
else {
            mensaje.style.background = '#fee2e2';
            mensaje.style.color = '#991b1b';

            if (respuesta.status === 422 && datos.errors) {
                mensaje.textContent = Object.values(datos.errors)
                    .flat()
                    .join(' ');
            } else {
                mensaje.textContent = datos.message ||
                    'No se pudo registrar el usuario.';
            }
        }

    } catch (error) {
        mensaje.style.background = '#fee2e2';
        mensaje.style.color = '#991b1b';
        mensaje.style.padding = '12px';
        mensaje.textContent = 'Error de conexión con el servidor.';
    }
});
</script>

</body>
</html>
