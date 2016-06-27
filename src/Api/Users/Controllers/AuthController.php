<?php

namespace {{App\}}Http\Controllers\Api;

use {{App\}}Http\Controllers\Controller;
use {{App\}}User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Response as IlluminateResponse;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Login a user
     *
     * @param  Request $request
     * @return JSON
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            $this->validate($request, [
                'email' => 'required|email|max:255', 'password' => 'required',
            ]);
        } catch (HttpResponseException $e) {
            $payload = [
                'error' => [
                    'message'     => 'Invalid auth',
                    'status_code' => IlluminateResponse::HTTP_BAD_REQUEST
                ]
            ];

            return response()->json($payload, IlluminateResponse::HTTP_BAD_REQUEST, []);
        }

        $credentials = $this->getCredentials($request);

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token'));
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getCredentials(Request $request)
    {
        return $request->only('email', 'password');
    }

    /**
     * Refresh the token
     *
     * @return JSON
     */
    public function refresh()
    {
        $newToken = JWTAuth::parseToken()->refresh();
        return response()->json(compact('newToken'));
    }

    /**
     * Register a User
     *
     * @param  Request $request
     * @return JSON
     */
    public function register(Request $request)
    {
        $data = $this->getCredentials($request);

        try {
            return DB::transaction(function () use ($data) {
                $user = User::create([
                    'name' => $data['email'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),
                ]);

                $token = JWTAuth::fromUser($user);

                return response()->json(compact('token'));
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['error' => 'This email is already registered, please login.']);
        }
    }
}
