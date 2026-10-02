<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|confirmed|min:8',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'user' => $user,
                'token' => $user->createToken('api_token')->plainTextToken
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Erro no banco de dados',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro inesperado',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if (Auth::attempt($credentials)) {
                $user = Auth::user();

                return response()->json([
                    'message' => 'Authorized',
                    'token' => $user->createToken('auth_token')->plainTextToken,
                    'user' => $user
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Credenciais inválidas',
                    'success' => false
                ], 403);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao tentar login',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado',
                    'success' => false
                ], 401);
            }
                                                                                                                                                                                                                                            
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Logout realizado com sucesso',
                'success' => true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao realizar logout',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado',
                    'success' => false
                ], 401);
            }

            return response()->json([
                'user' => $user,
                'success' => true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar dados do usuário',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function me(Request $request)
    {
        return $this->user($request);
    }

    // app/Http/Controllers/AuthController.php
    public function handleCallback(Request $request)
    {
        // Validação básica
        $request->validate([
            'code' => 'required',
            'state' => 'required'
        ]);

        // Verifica o state (CSRF protection)
        // if ($request->state !== session('oauth_state')) {
        //     abort(401, 'State inválido');
        // }

        // Faz a requisição para o SSO
        // dd(config('services.sso.url') . '/api/oauth/token');
        $response = Http::post(config('services.sso.url') . '/api/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.sso.client_id'),
            'client_secret' => config('services.sso.client_secret'),
            'redirect_uri' => config('services.sso.redirect_uri'),
            'code' => $request->code
        ]);
        // dd(  $response->json() ,"ola");


        // Se houve erro na troca
        if ($response->failed()) {
            // dd($response);
            Log::error('Falha na troca de código por token', [
                'errors' => $response->json()
            ]);
            return redirect('/login')->withErrors('Falha na autenticação');
        }

        // Salva o token e autentica o usuário
        $token = $response->json()['access_token'];
        session(['sso_token' => $token]);

        return redirect('/dashboard');
    }
}
