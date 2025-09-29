<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Sip\SipAor;
use App\Models\Sip\SipAuth;
use App\Models\Sip\SipEndpoint;
use App\Models\Sip\SipAccount;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Aprendiz;
use App\Models\AreaInteres;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = Auth::user();
        $sip_account = SipAccount::with('user')->where('user_id', $user->id)->first();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'sip_account' => $sip_account,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    public function getAreasInteres()
    {
        $areas = AreaInteres::all(['id', 'nombre', 'descripcion']);
    
        return response()->json([
            'success' => true,
            'data' => $areas
        ]);
    }


    /**
     * Update the aprendiz profile information.
     */
    public function updateAprendizProfile(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'semestre' => 'required|integer|min:1|max:10',
            'objetivos' => 'nullable|string|max:1000',
            'areas_interes' => 'required|array|min:1',
            'areas_interes.*' => 'exists:areas_interes,id'
        ]);

        // Obtener o crear perfil de aprendiz
        $aprendiz = Auth::user()->aprendiz ?? new Aprendiz(['user_id' => Auth::id()]);
        
        // Actualizar datos
        $aprendiz->fill($validated);
        $aprendiz->save();
        
        // Sincronizar áreas de interés (many-to-many)
        $aprendiz->areasInteres()->sync($validated['areas_interes']);
        
        // Retornar perfil completo con relaciones
        return response()->json([
            'success' => true,
            'data' => $aprendiz->load(['user', 'areasInteres'])
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $sipUser = SipAccount::with('user')->where('user_id', $user->id)->first();

        if($sipUser){

            DB::connection('asterisk')->transaction(function() use ($sipUser) {
                SipAor::where('id', $sipUser->sip_user_id)->delete();
                SipAuth::where('id', $sipUser->sip_user_id)->delete();
                SipEndpoint::where('id', $sipUser->sip_user_id)->delete();
            });

            $sipUser->delete();
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
