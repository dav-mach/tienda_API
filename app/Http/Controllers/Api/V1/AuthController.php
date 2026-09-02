            <?php

            namespace App\Http\Controllers\Api\V1;

            use App\Http\Controllers\Controller;
            use App\Http\Requests\LoginRequest;
            use App\Http\Requests\RegisterRequest;
            use App\Http\Resources\UsuarioResource;
            use App\Models\User;
            use Illuminate\Http\JsonResponse;
            use Illuminate\Support\Facades\Auth;

            /**
             * Autenticación con JWT (Entrega 4).
             *
             * register / login devuelven un JWT que el cliente tiene que mandar en
             * las siguientes peticiones en el header:
             *
             *     Authorization: Bearer <token>
             *
             * Las rutas de este controlador son públicas (no exigen token), salvo
             * "me" y "logout", que sí necesitan un token válido (ver routes/api.php).
             */
            class AuthController extends Controller
            {
                /**
                 * POST /api/v1/auth/register — crea un usuario y ya le devuelve un JWT.
                 * La contraseña se guarda hasheada con bcrypt automáticamente (el cast
                 * 'hashed' del modelo User se encarga).
                 */
                public function register(RegisterRequest $request): JsonResponse
                {
                    $user = User::create($request->validated());

                    $token = Auth::guard('api')->login($user);

                    return $this->respuestaConToken($token, $user, 'Usuario registrado.', 201);
                }

                /**
                 * POST /api/v1/auth/login — valida email + contraseña y devuelve un JWT.
                 * Si las credenciales son incorrectas, responde 401 (no revela si lo
                 * que falló fue el email o la contraseña, a propósito).
                 */
                public function login(LoginRequest $request): JsonResponse
                {
                    $credenciales = $request->validated();

                    $token = Auth::guard('api')->attempt($credenciales);

                    if (!$token) {
                        return $this->error('Credenciales inválidas.', [], 401);
                    }

                    return $this->respuestaConToken($token, Auth::guard('api')->user(), 'Sesión iniciada.');
                }

                /**
                 * GET /api/v1/auth/me — devuelve el usuario dueño del token actual.
                 * Sirve para que el cliente sepa "quién soy" con el token que tiene.
                 */
                public function me(): JsonResponse
                {
                    return $this->exito(new UsuarioResource(Auth::guard('api')->user()));
                }

                /**
                 * POST /api/v1/auth/logout — invalida el token actual (lo agrega a una
                 * blacklist para que no se pueda volver a usar aunque no haya expirado).
                 */
                public function logout(): JsonResponse
                {
                    Auth::guard('api')->logout();

                    return $this->exitoDatos(null, 'Sesión cerrada.');
                }

                /**
                 * Arma la respuesta estándar cuando emitimos un token: el usuario, el
                 * token, el tipo (Bearer) y en cuántos segundos expira.
                 */
                private function respuestaConToken(string $token, User $user, string $mensaje, int $status = 200): JsonResponse
                {
                    return $this->exitoDatos([
                        'usuario' => new UsuarioResource($user),
                        'token' => $token,
                        'token_type' => 'bearer',
                        // ttl viene en minutos; lo pasamos a segundos para el cliente.
                        'expira_en' => Auth::guard('api')->factory()->getTTL() * 60,
                    ], $mensaje, $status);
                }
            }
