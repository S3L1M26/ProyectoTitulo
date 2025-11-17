import React, { memo } from 'react';

const MentorSuggestions = memo(function MentorSuggestions({ 
    mentorList = [], 
    onOpenMentorModal, 
    onOpenReviewModal,
    isLoadingSuggestions,
    requiresVerification,
    mentorSuggestions 
}) {
    if (isLoadingSuggestions) {
        return (
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <div className="animate-pulse space-y-4">
                        <div className="h-8 bg-gray-200 rounded w-1/3"></div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {[1, 2, 3].map((i) => (
                                <div key={i} className="border rounded-lg p-4 space-y-3">
                                    <div className="h-4 bg-gray-200 rounded"></div>
                                    <div className="h-4 bg-gray-200 rounded w-2/3"></div>
                                    <div className="h-20 bg-gray-200 rounded"></div>
                                    <div className="h-10 bg-gray-200 rounded"></div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (requiresVerification) {
        return (
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <div className="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div className="flex items-start">
                            <svg className="w-5 h-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                            <div>
                                <h5 className="font-semibold text-yellow-800 mb-1">Se requiere verificación</h5>
                                <p className="text-sm text-yellow-700 mb-3">
                                    {mentorSuggestions.message || 'Debes verificar tu certificado de alumno regular para ver mentores.'}
                                </p>
                                <a
                                    href={mentorSuggestions.upload_url || '/profile#certificate'}
                                    className="inline-flex items-center px-3 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition-colors text-sm font-medium"
                                >
                                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Subir Certificado
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (mentorList.length === 0) {
        return (
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div className="p-6 text-center text-gray-500">
                    <p>No hay mentores disponibles en este momento.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                    Mentores Sugeridos para Ti
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {mentorList.map((mentorUser) => (
                        <div key={mentorUser.id} className="border rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="font-medium text-gray-900">{mentorUser.name}</h4>
                                <button
                                    onClick={() => onOpenReviewModal(mentorUser)}
                                    className={`flex items-center gap-1 px-2 py-1 rounded transition-colors ${
                                        mentorUser.mentor.can_review || mentorUser.mentor.user_review
                                            ? 'bg-yellow-100 hover:bg-yellow-200'
                                            : 'hover:bg-gray-100'
                                    }`}
                                    title={mentorUser.mentor.can_review ? 'Puedes reseñar este mentor' : (mentorUser.mentor.user_review ? 'Editar tu reseña' : 'Completa una mentoría para reseñar')}
                                >
                                    <span className={mentorUser.mentor.can_review || mentorUser.mentor.user_review ? 'text-yellow-400' : 'text-gray-300'}>★</span>
                                    <span className="text-sm text-gray-600">
                                        {mentorUser.mentor.calificacionPromedio ? Number(mentorUser.mentor.calificacionPromedio).toFixed(1) : '0.0'}/5
                                    </span>
                                </button>
                            </div>
                            <p className="text-sm text-gray-600 mb-2">
                                {mentorUser.mentor.años_experiencia} años de experiencia
                            </p>
                            <p className="text-sm text-gray-700 mb-3 line-clamp-3">
                                {mentorUser.mentor.biografia || mentorUser.mentor.experiencia}
                            </p>
                            <div className="flex flex-wrap gap-1 mb-3">
                                {(mentorUser.mentor.areas_interes ?? mentorUser.mentor.areasInteres ?? []).slice(0, 3).map((area) => (
                                    <span 
                                        key={area.id} 
                                        className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
                                    >
                                        {area.nombre}
                                    </span>
                                ))}
                                {(mentorUser.mentor.areas_interes ?? mentorUser.mentor.areasInteres ?? []).length > 3 && (
                                    <span className="text-xs text-gray-500">
                                        +{(mentorUser.mentor.areas_interes ?? mentorUser.mentor.areasInteres ?? []).length - 3} más
                                    </span>
                                )}
                            </div>
                            <div className="space-y-2">
                                <button 
                                    onClick={() => onOpenMentorModal(mentorUser)}
                                    className="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm font-medium"
                                >
                                    Ver Perfil Completo
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
});

export default MentorSuggestions;
