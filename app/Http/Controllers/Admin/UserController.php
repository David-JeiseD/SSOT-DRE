<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; // Para generar la contraseña aleatoria
use App\Notifications\NuevoUsuarioCreado; // Tu notificación



class UserController extends Controller
{
    /**
     * Muestra una lista paginada y con capacidad de búsqueda de usuarios.
     */
    public function index(Request $request)
{
    // --- Búsqueda y Paginación (para la tabla del CRUD) ---
    $searchQuery = $request->input('search');
    $baseQuery = User::whereHas('roles');

    $usuariosPaginados = $baseQuery->clone()->with('roles')
        ->when($searchQuery, function ($query, $search) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
        })
        ->orderBy('name', 'asc')
        ->paginate(10)
        ->withQueryString();

    // --- 🔥 Lógica para el Dashboard de Reportería 🔥 ---
    
    // Obtenemos TODOS los usuarios con los conteos de sus acciones.
    // withCount es extremadamente eficiente.
    $usuariosParaReporte = $baseQuery->clone()->with('roles')
        ->withCount([
            'acciones as total_subidas' => fn($q) => $q->where('tipo_accion', 'GENERACION_EXPEDIENTE'),
            'acciones as total_ediciones' => fn($q) => $q->where('tipo_accion', 'EDICION_DATO_CRUDO'),
            'acciones as total_eliminaciones' => fn($q) => $q->where('tipo_accion', 'ELIMINACION_DATO_CRUDO'),
        ])
        ->get();

    // Calculamos KPIs globales a partir de la colección ya cargada
    $stats = [
        'totalUsuarios' => $usuariosParaReporte->count(),
        'totalAcciones' => $usuariosParaReporte->sum(fn($u) => $u->total_subidas + $u->total_ediciones + $u->total_eliminaciones),
        'usuarioMasActivo' => $usuariosParaReporte->sortByDesc(fn($u) => $u->total_subidas + $u->total_ediciones + $u->total_eliminaciones)->first(),
        'ultimaActividad' => \App\Models\AccionUsuario::with('user')->latest()->first(),
    ];
    
    $roles = Role::pluck('name', 'name');

    return view('admin.users.index', [
        'usuarios' => $usuariosPaginados,      // Para la tabla CRUD
        'usuariosParaReporte' => $usuariosParaReporte, // Para el dashboard
        'roles' => $roles,
        'stats' => $stats,
    ]);
}

    /**
     * Guarda un nuevo usuario en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'dni' => 'nullable|string|max:8|unique:users,dni', // Añadido
            'rol' => 'required|exists:roles,name',
        ]);

        // Generamos una contraseña segura de 10 caracteres
        $password = Str::random(10);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'dni' => $validated['dni'] ?? null,
            'password' => Hash::make($password),
        ]);

        $user->assignRole($validated['rol']);

        // Enviamos la notificación al nuevo usuario con su contraseña
        $user->notify(new NuevoUsuarioCreado($user, $password));

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario '{$user->name}' creado exitosamente. Se han enviado sus credenciales por correo.");
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'dni' => 'nullable|string|max:8|unique:users,dni,' . $user->id,
            'rol' => 'required|exists:roles,name',
            'password' => 'nullable|min:8|confirmed', // Contraseña opcional
        ]);
        try {
            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'dni' => $validated['dni'] ?? null,
            ];

            // Solo actualizamos la contraseña si se proporcionó una nueva
            if (!empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }

            $user->update($data);
            $user->syncRoles([$validated['rol']]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            // Si la validación falla, volvemos a la página anterior,
            // pero añadimos información extra para saber qué modal reabrir.
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error_form_type', 'edit'); // <-- La clave
        }
    
        return redirect()->route('admin.users.index')
            ->with('success', "Usuario '{$user->name}' actualizado correctamente.");
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(User $user)
    {
        // Medida de seguridad: No permitir que un usuario se elimine a sí mismo
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario '{$userName}' ha sido eliminado.");
    }

    /**
     * (Función para la API que ya tenías, la mantenemos)
     */
    public function buscar(Request $request)
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $users = User::where('name', 'LIKE', "%{$query}%")
                ->orWhere('dni', 'LIKE', "%{$query}%")
                ->select('id', 'name', 'email', 'dni')
                ->take(10)
                ->get();
        
        return response()->json($users);
    }
    public function detalle(User $user)
    {
        // Cargamos el usuario con todas sus acciones, ordenadas por la más reciente
        $user->load(['acciones' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return view('admin.users.detalle', compact('user'));
    }
    public function storeMinimal(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|digits:8|unique:users,dni',
            'nombre' => 'required|string|max:255',
            'codigomodular' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['nombre'],
            'dni' => $validated['dni'],
            'codigomodular' => $validated['codigomodular'],
            'email' => $validated['dni'] . '@placeholder.com', // Email temporal
            'password' => bcrypt(Str::random(10))
        ]);

        // Redirigimos a la página de ingesta con un mensaje de éxito
        return redirect()->route('ingesta.create')
            ->with('success', "Usuario '{$user->name}' creado exitosamente. Ahora puedes buscarlo en el Módulo de Gestión de Datos para añadir sus registros.");
    }
}
