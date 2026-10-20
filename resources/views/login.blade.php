<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Mi Sistema</title>
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
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 410px;
            padding: 38px;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .2);
        }

        h1 {
            text-align: center;
            margin-bottom: 8px;
            color: #172554;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin: 16px 0 8px;
            font-weight: bold;
            color: #334155;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 18px 0;
            gap: 10px;
            font-size: 13px;
        }

        .options label {
            margin: 0;
            font-weight: normal;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        button {
            width: 100%;
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

        .register {
            text-align: center;
            margin-top: 24px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Bienvenido</h1>
        <p class="subtitle">Inicia sesión en tu cuenta</p>

        @if (session('error'))
            <div style="
            background: #ffe5e5;
            color: #b00020;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        ">
            {{ session('error') }}
        </div>
    @endif

        <form id="loginForm">
        @csrf
            
<div id="mensaje"
     role="alert"
     aria-live="polite"
     style="display:none; margin-bottom:15px; padding:12px; border-radius:8px; font-size:14px;">
</div>

            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo"
                   placeholder="ejemplo@correo.com" required>

            <label for="passwordd">Contraseña</label>
            <input type="password" id="passwordd"
                   placeholder="Ingresa tu contraseña" required>

            <div class="options">
                <label>
                    <input type="checkbox" id="recordarme">
                    Recordarme
                </label>
                <a href="/recuperar">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit">Iniciar sesión</button>
        </form>

        <!-- Inicio de sesión con Google -->
<div style="margin-top: 20px; text-align: center;">

    <p style="color: #777; margin-bottom: 15px;">
        O continúa con
    </p>

    <a href="{{ route('google.login') }}"
       style="
           display: block;
           padding: 12px;
           background: white;
           color: #333;
           border: 1px solid #ddd;
           border-radius: 8px;
           text-decoration: none;
           font-weight: 600;
       ">
        Continuar con Google
    </a>

</div>

        <p class="register">
            ¿No tienes una cuenta?
            <a href="/registro">Regístrate aquí</a>
        </p>
    </div>
    

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const correo = document.getElementById('correo').value;
    const passwordd = document.getElementById('passwordd').value;
    const mensaje = document.getElementById('mensaje');
    const token = document.querySelector('input[name="_token"]').value;

    mensaje.style.display = 'none';

    try {
        const respuesta = await fetch('/login', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            
            body: JSON.stringify({
                correo: correo,
                passwordd: passwordd,
                recordarme: document.getElementById('recordarme').checked
            })

        });

        
const datos = await respuesta.json();

mensaje.style.display = 'block';

// Comprobar si el usuario necesita verificar su correo
if (respuesta.status === 403 && datos.verificacion_pendiente) {

    // Guardar temporalmente el correo ingresado
    sessionStorage.setItem('correoRegistro', correo.trim());

    // Enviar a la pantalla de verificación
    window.location.href = '/verificar-correo';

    return;
}

if (respuesta.ok && datos.success) {

            mensaje.style.background = '#dcfce7';
            mensaje.style.color = '#166534';
            mensaje.textContent = 'Inicio de sesión correcto.';

            window.location.href = datos.redirect;
        } else {
            mensaje.style.background = '#fee2e2';
            mensaje.style.color = '#991b1b';
            mensaje.textContent = datos.message || 'No se pudo iniciar sesión.';
        }

    } catch (error) {
        mensaje.style.display = 'block';
        mensaje.style.background = '#fee2e2';
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Error de conexión con el servidor.';
    }
});
</script>

</body>
</html>
