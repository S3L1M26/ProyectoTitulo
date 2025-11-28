import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const StatCard = ({ label, value, helper }) => (
    <div className="bg-white shadow-sm sm:rounded-lg p-4">
        <p className="text-sm text-gray-500">{label}</p>
        <p className="text-2xl font-semibold text-gray-900">{value}</p>
        {helper && <p className="text-xs text-gray-500 mt-1">{helper}</p>}
    </div>
);

const RatingBar = ({ rating, total, maxTotal }) => {
    const percentage = maxTotal > 0 ? Math.round((total / maxTotal) * 100) : 0;
    return (
        <div className="flex items-center gap-3">
            <div className="w-10 text-sm font-semibold text-gray-700">{rating}★</div>
            <div className="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div
                    className="h-full bg-yellow-400"
                    style={{ width: `${percentage}%` }}
                />
            </div>
            <div className="w-12 text-right text-sm text-gray-600">{total}</div>
        </div>
    );
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
};

export default function AdminDashboard({ reviewStats = {}, surveyStats = {} }) {
    const distribution = reviewStats.rating_distribution || [];
    const maxDistribution = distribution.reduce((max, r) => Math.max(max, r.total), 0);
    const topMentors = reviewStats.top_mentors || [];
    const recentReviews = reviewStats.recent_reviews || [];
    const latestSurveys = surveyStats.latest_entries || [];
    const questionAverages = surveyStats.question_averages || {};

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Panel de Administración
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    {/* Reseñas de mentores */}
                    <section className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">Resumen de reseñas de mentores</h3>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <StatCard
                                label="Promedio global"
                                value={Number(reviewStats.average_rating ?? 0).toFixed(2)}
                                helper={`${reviewStats.total_reviews ?? 0} reseñas`}
                            />
                            <StatCard
                                label="Mejor mentor (promedio)"
                                value={topMentors[0]?.avg_rating ? `${topMentors[0].avg_rating.toFixed(2)} ★` : '—'}
                                helper={topMentors[0]?.name ?? 'Sin datos'}
                            />
                            <StatCard
                                label="Top reseñas"
                                value={topMentors[0]?.reviews_count ?? 0}
                                helper="Reseñas mentor líder"
                            />
                            <StatCard
                                label="Distribución 5★"
                                value={
                                    distribution.find((d) => d.rating === 5)?.total
                                        ? `${distribution.find((d) => d.rating === 5).total} reseñas`
                                        : '—'
                                }
                                helper="Cantidad con máxima calificación"
                            />
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div className="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                                <h4 className="text-sm font-semibold text-gray-800">Distribución de calificaciones</h4>
                                {distribution.length === 0 ? (
                                    <p className="text-sm text-gray-500">Aún no hay reseñas.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {distribution.map((row) => (
                                            <RatingBar
                                                key={row.rating}
                                                rating={row.rating}
                                                total={row.total}
                                                maxTotal={maxDistribution}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                                <div className="flex items-center justify-between">
                                    <h4 className="text-sm font-semibold text-gray-800">Top mentores</h4>
                                </div>
                                {topMentors.length === 0 ? (
                                    <p className="text-sm text-gray-500">Sin mentores reseñados aún.</p>
                                ) : (
                                    <ul className="divide-y divide-gray-200">
                                        {topMentors.map((mentor) => (
                                            <li key={mentor.id} className="py-3 flex items-center justify-between">
                                                <div>
                                                    <p className="text-sm font-semibold text-gray-900">{mentor.name ?? '—'}</p>
                                                    <p className="text-xs text-gray-500">{mentor.reviews_count} reseñas</p>
                                                </div>
                                                <div className="text-sm font-bold text-yellow-600">
                                                    {mentor.avg_rating ? mentor.avg_rating.toFixed(2) : '0.00'} ★
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>

                        <div className="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                            <div className="flex items-center justify-between">
                                <h4 className="text-sm font-semibold text-gray-800">Reseñas recientes</h4>
                                <p className="text-xs text-gray-500">Últimas 8 reseñas</p>
                            </div>
                            {recentReviews.length === 0 ? (
                                <p className="text-sm text-gray-500">No hay reseñas registradas aún.</p>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {recentReviews.map((review) => (
                                        <div key={review.id} className="border border-gray-200 rounded-lg p-4 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-yellow-500 font-semibold">{review.rating}★</span>
                                                    <span className="text-sm text-gray-700">{review.mentor?.name ?? 'Mentor'}</span>
                                                </div>
                                                <span className="text-xs text-gray-500">{formatDate(review.created_at)}</span>
                                            </div>
                                            {review.comment && (
                                                <p className="text-sm text-gray-700 leading-snug">"{review.comment}"</p>
                                            )}
                                            <div className="text-xs text-gray-500 flex flex-wrap gap-2">
                                                {review.student?.name && <span>Por: {review.student.name}</span>}
                                                {review.addressed_interests && <span>| Enfoque: {review.addressed_interests}</span>}
                                                {review.interests_clarity && <span>| Claridad: {review.interests_clarity}/5</span>}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </section>

                    {/* Encuestas vocacionales */}
                    <section className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">Resultados de encuestas vocacionales</h3>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <StatCard
                                label="Encuestas completadas"
                                value={surveyStats.total_surveys ?? 0}
                                helper="Total de respuestas"
                            />
                            <StatCard
                                label="ICV promedio"
                                value={Number(surveyStats.average_icv ?? 0).toFixed(2)}
                                helper="Índice de claridad vocacional"
                            />
                            <StatCard
                                label="Claridad de interés"
                                value={Number(questionAverages.clarity_interest ?? 0).toFixed(2)}
                                helper="Promedio (1-5)"
                            />
                            <StatCard
                                label="Confianza en área"
                                value={Number(questionAverages.confidence_area ?? 0).toFixed(2)}
                                helper="Promedio (1-5)"
                            />
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div className="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                                <h4 className="text-sm font-semibold text-gray-800">Promedios de preguntas</h4>
                                <div className="space-y-3">
                                    {[
                                        { key: 'platform_usefulness', label: 'Utilidad de la plataforma' },
                                        { key: 'mentorship_usefulness', label: 'Utilidad de la mentoría' },
                                    ].map(({ key, label }) => (
                                        <div key={key} className="space-y-1">
                                            <div className="flex items-center justify-between text-sm text-gray-700">
                                                <span>{label}</span>
                                                <span className="font-semibold">
                                                    {(questionAverages[key] ?? 0).toFixed(2)}
                                                </span>
                                            </div>
                                            <div className="h-2 rounded-full bg-gray-100 overflow-hidden">
                                                <div
                                                    className="h-full bg-indigo-500"
                                                    style={{ width: `${Math.min((questionAverages[key] ?? 0) * 20, 100)}%` }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                                <div className="flex items-center justify-between">
                                    <h4 className="text-sm font-semibold text-gray-800">Últimas respuestas</h4>
                                    <p className="text-xs text-gray-500">Últimas 8 encuestas</p>
                                </div>
                                {latestSurveys.length === 0 ? (
                                    <p className="text-sm text-gray-500">Aún no hay encuestas registradas.</p>
                                ) : (
                                    <div className="space-y-3">
                                        {latestSurveys.map((survey) => (
                                            <div key={survey.id} className="border border-gray-200 rounded-lg p-4">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="text-sm font-semibold text-gray-900">
                                                            {survey.student_name ?? 'Estudiante'}
                                                        </p>
                                                        <p className="text-xs text-gray-500">ICV: {survey.icv?.toFixed(2) ?? '0.00'}</p>
                                                    </div>
                                                    <span className="text-xs text-gray-500">{formatDate(survey.created_at)}</span>
                                                </div>
                                                <div className="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-600">
                                                    <span>Claridad: {survey.clarity_interest}/5</span>
                                                    <span>Confianza: {survey.confidence_area}/5</span>
                                                    <span>Plataforma: {survey.platform_usefulness}/5</span>
                                                    <span>Mentoría: {survey.mentorship_usefulness}/5</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
