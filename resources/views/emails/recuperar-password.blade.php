<div style="font-family: Arial, sans-serif; max-width: 500px; margin: auto;">
    <h1 style="text-align:center;">RENTACARS "EL GUAYABO"</h1>
    <h2>Hola, {{ $user->nombre }}</h2>
    <p>Recibimos una solicitud para restablecer tu contraseña en RentaCars El Guayabo.</p>
    <p>
        <a href="{{ $url }}" style="background:#1a73e8;color:#fff;padding:12px 20px;text-decoration:none;border-radius:6px;">
            Restablecer contraseña
        </a>
    </p>
    <p>Este enlace vence en 60 minutos. Si no fuiste tú, ignora este correo.</p>
</div>
