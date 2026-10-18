
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Mi Sistema</title>

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

        h1 {
            text-align: center;
            color: #172554;
            font-size: 27px;
        }

        p {
            text-align: center;
            color: #64748b;
            line-height: 1.6;
            font-size: 14px;
        }

        label {
            display: block;
            margin: 25px 0 8px;
            color: #334155;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 22px;
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

        .volver {
            display: block;
            text-align: center;
            margin-top: 23px;
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="card">
        <h1>¿Olvidaste tu contraseña?</h1>

        <p>
            Ingresa tu correo electrónico para
            solicitar un código de recuperación.
        </p>

        <form id="recuperarForm">
            @csrf

            <label for="correo">Correo electrónico</label>

            <input
                type="email"
                id="correo"
                placeholder="ejemplo@correo.com"
                autocomplete="email"
                required
            >

            <button type="submit">
                Enviar código
            </button>
            <div id="mensaje" role="status" aria-live="polite"></div>
        </form>

        <a href="/login" class="volver">
            ← Volver al inicio de sesión
        </a>
    </div>

<script>
document.getElementById('recuperarForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const correo = document.getElementById('correo').value.trim();
    const mensaje = document.getElementById('mensaje');
    const boton = document.querySelector('#recuperarForm button');

    mensaje.textContent = '';
    boton.disabled = true;
    boton.textContent = 'Enviando...';

    try {
        const respuesta = await fetch('/api/login/recuperar', {
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

        mensaje.style.marginTop = '15px';
        mensaje.style.padding = '12px';
        mensaje.style.borderRadius = '8px';

        if (respuesta.ok && datos.success) {
            mensaje.style.background = '#dcfce7';
            mensaje.style.color = '#166534';
            mensaje.textContent = 'Solicitud procesada. Continuaremos con la verificación del código.';

            // Guardar temporalmente el correo, no el código
            sessionStorage.setItem('correoRecuperacion', correo);

            // La ruta se creará en el siguiente paso
            setTimeout(() => {
                window.location.href = '/verificar-codigo';
            }, 1500);

        } else {
            mensaje.style.background = '#fee2e2';
            mensaje.style.color = '#991b1b';

            if (respuesta.status === 429) {
                mensaje.textContent = 'Demasiadas solicitudes. Espera unos minutos.';
            } else {
                mensaje.textContent = datos.message ||
                    'No se pudo procesar la solicitud.';
            }
        }

    } catch (error) {
        mensaje.style.marginTop = '15px';
        mensaje.style.padding = '12px';
        mensaje.style.background = '#fee2e2';
        mensaje.style.color = '#991b1b';
        mensaje.textContent = 'Error de conexión con el servidor.';
    } finally {
        boton.disabled = false;
        boton.textContent = 'Enviar código';
    }
});
</script>

</body>
</html>
