<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mentor;
use App\Models\MentorReview;
use App\Models\VocationalSurvey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard/Index', [
            'reviewStats' => $this->getReviewStats(),
            'surveyStats' => $this->getSurveyStats(),
        ]);
    }

    public function index()
    {
        // Eager loading optimizado con solo campos necesarios
        $users = User::with([
            'aprendiz:id,user_id,certificate_verified',
            'mentor:id,user_id,cv_verified,disponible_ahora'
        ])
        ->select('id', 'name', 'email', 'role', 'created_at', 'is_active')
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            
            // Lazy prop: Estadísticas solo si se solicitan
            'stats' => fn () => Cache::remember('admin_stats', 600, function() {
                return [
                    'total_users' => User::count(),
                    'total_students' => User::where('role', 'student')->count(),
                    'total_mentors' => User::where('role', 'mentor')->count(),
                    'verified_students' => \App\Models\Aprendiz::where('certificate_verified', true)->count(),
                    'verified_mentors' => \App\Models\Mentor::where('cv_verified', true)->count(),
                ];
            }),
        ]);
    }

    public function show(User $user)
    {
        // Eager loading solo de relaciones necesarias con campos específicos
        $user->load([
            'aprendiz.areasInteres:id,nombre',
            'mentor.areasInteres:id,nombre'
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user
        ]);
    }

    public function edit(User $user)
    {
        // Eager loading solo de relaciones necesarias
        $user->load([
            'aprendiz.areasInteres:id,nombre',
            'mentor.areasInteres:id,nombre'
        ]);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'is_active' => 'boolean',
        ]);

        $user->update($validated);
        
        // Invalidar caché de estadísticas
        Cache::forget('admin_stats');

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        
        // Invalidar caché de estadísticas
        Cache::forget('admin_stats');

        return redirect()->route('admin.users')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    private function getReviewStats(): array
    {
        $averageRating = (float) MentorReview::avg('rating');

        $ratingDistribution = MentorReview::select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->orderByDesc('rating')
            ->get()
            ->map(fn ($row) => [
                'rating' => (int) $row->rating,
                'total' => (int) $row->total,
            ]);

        $topMentors = Mentor::select('id', 'user_id', 'calificacionPromedio')
            ->with('user:id,name')
            ->withCount('reviews')
            ->orderByDesc('reviews_count')
            ->orderByDesc('calificacionPromedio')
            ->take(5)
            ->get()
            ->map(fn ($mentor) => [
                'id' => $mentor->id,
                'name' => $mentor->user?->name,
                'avg_rating' => $mentor->calificacionPromedio,
                'reviews_count' => $mentor->reviews_count,
            ]);

        $recentReviews = MentorReview::with([
            'mentor.user:id,name',
            'user:id,name',
        ])
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'addressed_interests' => $review->addressed_interests,
                'interests_clarity' => $review->interests_clarity,
                'created_at' => $review->created_at,
                'mentor' => [
                    'id' => $review->mentor?->id,
                    'name' => $review->mentor?->user?->name,
                ],
                'student' => [
                    'id' => $review->user?->id,
                    'name' => $review->user?->name,
                ],
            ]);

        return [
            'total_reviews' => MentorReview::count(),
            'average_rating' => (float) number_format($averageRating, 2, '.', ''),
            'rating_distribution' => $ratingDistribution,
            'top_mentors' => $topMentors,
            'recent_reviews' => $recentReviews,
        ];
    }

    private function getSurveyStats(): array
    {
        $questionAverages = VocationalSurvey::selectRaw('AVG(clarity_interest) as clarity_interest')
            ->selectRaw('AVG(confidence_area) as confidence_area')
            ->selectRaw('AVG(platform_usefulness) as platform_usefulness')
            ->selectRaw('AVG(mentorship_usefulness) as mentorship_usefulness')
            ->first();

        $latestEntries = VocationalSurvey::with('student:id,name')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($survey) => [
                'id' => $survey->id,
                'student_name' => $survey->student?->name,
                'icv' => (float) $survey->icv,
                'clarity_interest' => (int) $survey->clarity_interest,
                'confidence_area' => (int) $survey->confidence_area,
                'platform_usefulness' => (int) $survey->platform_usefulness,
                'mentorship_usefulness' => (int) $survey->mentorship_usefulness,
                'created_at' => $survey->created_at,
            ]);

        return [
            'total_surveys' => VocationalSurvey::count(),
            'average_icv' => round((float) VocationalSurvey::avg('icv'), 2),
            'question_averages' => [
                'clarity_interest' => round((float) ($questionAverages?->clarity_interest ?? 0), 2),
                'confidence_area' => round((float) ($questionAverages?->confidence_area ?? 0), 2),
                'platform_usefulness' => round((float) ($questionAverages?->platform_usefulness ?? 0), 2),
                'mentorship_usefulness' => round((float) ($questionAverages?->mentorship_usefulness ?? 0), 2),
            ],
            'latest_entries' => $latestEntries,
        ];
    }
}
