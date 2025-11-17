@component('mail::message')
# ¡Bienvenido al Sistema de Expedientes!

Hola **{{ $user->name }}**,
Se ha creado una cuenta para ti en nuestro sistema.

Tus credenciales de acceso son:

- **Correo:** {{ $user->email }}
- **Contraseña:** `{{ $password }}`

@component('mail::button', ['url' => url('/login')])
Iniciar Sesión
@endcomponent

> Por tu seguridad, te recomendamos cambiar tu contraseña después de tu primer inicio de sesión desde tu perfil.

Gracias,<br>
El Equipo de Administración
@endcomponent