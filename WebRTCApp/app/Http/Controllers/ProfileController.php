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
use App\Models\Mentor;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        // Refrescar el usuario desde la base de datos para obtener los datos más actualizados
        $user = Auth::user()->fresh();
        $sip_account = SipAccount::with('user')->where('user_id', $user->id)->first();

        // Cargar datos del perfil según el rol
        if ($user->role === 'student') {
            $user->load(['aprendiz.areasInteres']);
        } elseif ($user->role === 'mentor') {
            $user->load(['mentor.areasInteres']);
        }

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
    
        return response()->json($areas);
    }


    /**
     * Update the aprendiz profile information.
     */
    public function updateAprendizProfile(Request $request): RedirectResponse
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
        
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the mentor profile information.
     */
    public function updateMentorProfile(Request $request): RedirectResponse
    {
        // Validación compleja según criterios de aceptación
        $validated = $request->validate([
            'experiencia' => [
                'required',
                'string',
                'min:50',
                'max:2000',
                function ($attribute, $value, $fail) {
                    if (str_word_count($value) < 10) {
                        $fail('La experiencia debe contener al menos 10 palabras descriptivas.');
                    }
                }
            ],
            'biografia' => [
                'required',
                'string',
                'min:100',
                'max:1000',
            ],
            'años_experiencia' => [
                'required',
                'integer',
                'min:1',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    // Validar coherencia entre años y descripción
                    $experienciaText = $request->input('experiencia', '');
                    if ($value >= 10 && !preg_match('/senior|líder|lead|manager|director/i', $experienciaText)) {
                        $fail('Con ' . $value . ' años de experiencia se esperan roles senior o de liderazgo en la descripción.');
                    }
                }
            ],
            'disponibilidad' => 'required|string|min:10|max:200',
            'disponibilidad_detalle' => 'nullable|string|max:500',
            'areas_especialidad' => [
                'required',
                'array',
                'min:1',
                'max:5'
            ],
            'areas_especialidad.*' => 'exists:areas_interes,id'
        ], [
            // Mensajes personalizados
            'experiencia.min' => 'La descripción de experiencia debe ser más detallada (mínimo 50 caracteres).',
            'biografia.min' => 'La biografía debe ser más completa (mínimo 100 caracteres).',
            'años_experiencia.min' => 'Debe tener al menos 1 año de experiencia.',
            'años_experiencia.max' => 'El máximo permitido es 50 años de experiencia.',
            'areas_especialidad.min' => 'Debe seleccionar al menos 1 área de especialidad.',
            'areas_especialidad.max' => 'Máximo 5 áreas de especialidad permitidas.',
        ]);

        // Obtener o crear perfil de mentor
        $mentor = Auth::user()->mentor ?? new \App\Models\Mentor(['user_id' => Auth::id()]);
        
        // Actualizar datos (excluyendo areas_especialidad que se maneja por separado)
        $mentorData = collect($validated)->except('areas_especialidad')->toArray();
        $mentor->fill($mentorData);
        $mentor->save();
        
        // Sincronizar áreas de especialidad (many-to-many)
        $mentor->areasInteres()->sync($validated['areas_especialidad']);
        
        return Redirect::route('profile.edit')->with('status', 'mentor-profile-updated');
    }

    /**
     * Toggle mentor availability status.
     */
    public function toggleMentorDisponibilidad(Request $request): RedirectResponse
    {
        $mentor = Auth::user()->mentor;
        
        if (!$mentor) {
            return Redirect::route('profile.edit')->withErrors([
                'mentor' => 'Perfil de mentor no encontrado.'
            ]);
        }

        // Validar que tiene información mínima para estar disponible
        if ($request->input('disponible', true)) {
            $missingFields = [];
            
            if (!$mentor->experiencia || strlen(trim($mentor->experiencia)) < 50) {
                $missingFields[] = 'experiencia detallada';
            }
            if (!$mentor->biografia || strlen(trim($mentor->biografia)) < 100) {
                $missingFields[] = 'biografía completa';
            }
            if (!$mentor->años_experiencia || $mentor->años_experiencia < 1) {
                $missingFields[] = 'años de experiencia';
            }
            if (!$mentor->areasInteres || $mentor->areasInteres->count() === 0) {
                $missingFields[] = 'áreas de especialidad';
            }

            if (!empty($missingFields)) {
                return Redirect::route('profile.edit')->withErrors([
                    'disponibilidad' => 'Para estar disponible debe completar: ' . implode(', ', $missingFields) . '.'
                ]);
            }
        }

        // Toggle del estado de disponibilidad  
        $disponible = $request->input('disponible', false);
        
        // Actualizar el estado de disponibilidad
        $mentor->disponible_ahora = $disponible;
        
        // Si se activa pero no tiene horarios básicos, establecer mensaje
        if ($disponible && !$mentor->disponibilidad) {
            $mentor->disponibilidad = 'Horarios por coordinar';
        }

        $mentor->save();

        $message = $disponible ? 'Ahora estás disponible para mentoría.' : 'Has pausado tu disponibilidad.';
        
        return Redirect::route('profile.edit')->with('status', $message);
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
