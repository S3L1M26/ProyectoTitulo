import React, { memo, useState, useEffect } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import SolicitudMentoriaForm from '@/Components/SolicitudMentoriaForm';
import ReviewModal from '@/Components/ReviewModal';
import { router } from '@inertiajs/react';
import { toast } from 'react-toastify';
import axios from 'axios';

const MentorDetailModal = memo(function MentorDetailModal({ isOpen, onClose, mentor, aprendiz, solicitudesPendientes = [] }) {
    const [isSolicitudFormOpen, setIsSolicitudFormOpen] = useState(false);
    const [isReviewModalOpen, setIsReviewModalOpen] = useState(false);
    const [hasActiveMentoria, setHasActiveMentoria] = useState(false);
    const [checkingActiveMentoria, setCheckingActiveMentoria] = useState(false);
    const [localMentor, setLocalMentor] = useState(mentor);
    const canReview = Boolean(localMentor?.mentor?.can_review);

    // Actualizar localMentor cuando el prop mentor cambia (por ej, después de reload)
    useEffect(() => {
        if (isOpen && mentor && localMentor?.id !== mentor.id) {
            // Es un mentor diferente
            setLocalMentor(mentor);
        } else if (isOpen && mentor && mentor.mentor?.calificacionPromedio !== localMentor?.mentor?.calificacionPromedio) {
            // El mismo mentor pero con datos actualizados (después de reload)
            setLocalMentor(mentor);
        }
    }, [mentor, isOpen]);

    // Verificar si el estudiante tiene una mentoría activa con este mentor
    useEffect(() => {
        if (isOpen && localMentor && aprendiz) {
            setCheckingActiveMentoria(true);
            axios.get(`/api/student/mentores/${localMentor.id}/has-active-mentoria`)
                .then(response => {
                    setHasActiveMentoria(response.data.hasActiveMentoria);
                })
                .catch(error => {
                    console.error('Error checking active mentoria:', error);
                    setHasActiveMentoria(false);
                })
                .finally(() => {
                    setCheckingActiveMentoria(false);
                });
        }
    }, [isOpen, localMentor, aprendiz]);

    // Manejar actualización de reseña
    const handleReviewSubmitted = (reviewData) => {
        setLocalMentor(prev => {
            const hadPreviousReview = Boolean(prev.mentor.user_review);
            const allReviews = [];
            
            // Agregar la nueva reseña del usuario
            allReviews.push(reviewData.rating);
            
            // Agregar calificaciones de otras reseñas anónimas (excluyendo la anterior del usuario si existía)
            if (prev.mentor.anonymized_reviews && Array.isArray(prev.mentor.anonymized_reviews)) {
                prev.mentor.anonymized_reviews.forEach(review => {
                    if (review.id !== prev.mentor.user_review?.id) {
                        allReviews.push(review.rating);
                    }
                });
            }
            
            // Calcular nuevo promedio
            const newAverage = allReviews.length > 0 
                ? (allReviews.reduce((a, b) => a + b, 0) / allReviews.length).toFixed(2)
                : reviewData.rating.toFixed(2);
            
            return {
                ...prev,
                mentor: {
                    ...prev.mentor,
                    calificacionPromedio: parseFloat(newAverage),
                    can_review: false, // Después de primera reseña, no se puede volver a reseñar a menos que haya nueva mentoría
                    anonymized_reviews: [{
                        rating: reviewData.rating,
                        comment: reviewData.comment,
                        created_at: reviewData.created_at
                    }],
                    user_review: {
                        rating: reviewData.rating,
                        comment: reviewData.comment,
                        created_at: reviewData.created_at
                    }
                }
            };
        });
        
        // Esperar a que Redis procese la invalidación, luego recargar
        // Aumentado a 800ms para mentoras rápidas con múltiples actualizaciones en sucesión
        setTimeout(() => {
            router.reload({ only: ['mentorSuggestions'], preserveScroll: true });
        }, 800);
    };
    
    if (!localMentor) return null;

    return (
        <Transition appear show={isOpen} as={Fragment}>
            <Dialog as="div" className="relative z-50" onClose={onClose}>
                <Transition.Child
                    as={Fragment}
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-black bg-opacity-25" />
                </Transition.Child>

                <div className="fixed inset-0 overflow-y-auto">
                    <div className="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <Dialog.Panel className="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                {/* Header */}
                                <div className="flex items-start justify-between mb-6">
                                    <div className="flex items-center space-x-4">
                                        <div className="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                            {localMentor.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <Dialog.Title as="h3" className="text-2xl font-bold text-gray-900">
                                                {localMentor.name}
                                            </Dialog.Title>
                                            <div className="flex items-center mt-1">
                                                <span className="text-yellow-400 mr-1">★</span>
                                                <span className="text-sm font-medium text-gray-700 mr-3">
                                                    {localMentor.mentor.calificacionPromedio ? Number(localMentor.mentor.calificacionPromedio).toFixed(1) : '0.0'}/5
                                                </span>
                                                <span className="text-sm text-gray-600">
                                                    {localMentor.mentor.años_experiencia} años de experiencia
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        onClick={onClose}
                                        className="text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                {/* Content */}
                                <div className="space-y-6">
                                    {/* Áreas de Interés */}
                                    <div>
                                        <h4 className="text-lg font-semibold text-gray-900 mb-3">Áreas de Especialización</h4>
                                        <div className="flex flex-wrap gap-2">
                                            {localMentor.mentor.areas_interes.map((area) => (
                                                <span 
                                                    key={area.id} 
                                                    className="inline-block bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full"
                                                >
                                                    {area.nombre}
                                                </span>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Biografía */}
                                    {localMentor.mentor.biografia && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-3">Sobre mí</h4>
                                            <p className="text-gray-700 leading-relaxed">
                                                {localMentor.mentor.biografia}
                                            </p>
                                        </div>
                                    )}

                                    {/* Experiencia */}
                                    {localMentor.mentor.experiencia && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-3">Experiencia Profesional</h4>
                                            <p className="text-gray-700 leading-relaxed">
                                                {localMentor.mentor.experiencia}
                                            </p>
                                        </div>
                                    )}

                                    {/* Disponibilidad */}
                                    <div>
                                        <h4 className="text-lg font-semibold text-gray-900 mb-3">Disponibilidad</h4>
                                        {localMentor.mentor.disponible_ahora == 1 ? (
                                            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                                <div className="flex items-center mb-2">
                                                    <div className="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
                                                    <span className="text-green-800 font-medium">Disponible para mentorías</span>
                                                </div>
                                                {localMentor.mentor.disponibilidad && (
                                                    <p className="text-green-700 mb-2">
                                                        <strong>Horarios:</strong> {localMentor.mentor.disponibilidad}
                                                    </p>
                                                )}
                                                {localMentor.mentor.disponibilidad_detalle && (
                                                    <p className="text-green-700 text-sm">
                                                        <strong>Detalles:</strong> {localMentor.mentor.disponibilidad_detalle}
                                                    </p>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                                <div className="flex items-center">
                                                    <div className="w-3 h-3 bg-gray-400 rounded-full mr-2"></div>
                                                    <span className="text-gray-700">Actualmente no disponible para nuevas mentorías</span>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Reseñas y valoración - Botón para abrir modal */}
                                    <div>
                                        <h4 className="text-lg font-semibold text-gray-900 mb-3">Valoraciones</h4>
                                        <div className="flex items-center justify-between mb-4">
                                            <div className="flex items-center">
                                                <span className="text-yellow-400 mr-1">★</span>
                                                <span className="text-sm font-medium text-gray-700">
                                                    Promedio: {localMentor.mentor.calificacionPromedio ? Number(localMentor.mentor.calificacionPromedio).toFixed(1) : '0.0'} / 5
                                                </span>
                                            </div>
                                            <button
                                                onClick={() => setIsReviewModalOpen(true)}
                                                className={`px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2 ${
                                                    canReview || localMentor?.mentor?.user_review
                                                        ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
                                                        : 'bg-gray-100 text-gray-600'
                                                }`}
                                            >
                                                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.034a1 1 0 00-1.175 0l-2.802 2.034c-.784.57-1.838-.197-1.54-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                {localMentor?.mentor?.user_review ? 'Editar reseña' : 'Dejar reseña'}
                                            </button>
                                        </div>
                                        {/* Reseña más reciente */}
                                        <div className="space-y-3">
                                            {/* Mostrar primero tu reseña si existe, luego otras anónimas */}
                                            {localMentor?.mentor?.user_review && (
                                                <div className="border rounded p-3 bg-yellow-50 border-yellow-200">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <div className="text-xs text-yellow-700 font-semibold">Tu reseña</div>
                                                        <div className="text-sm font-semibold text-yellow-600">{localMentor.mentor.user_review.rating} / 5</div>
                                                    </div>
                                                    {localMentor.mentor.user_review.comment && (
                                                        <p className="mt-2 text-sm text-gray-700 italic">
                                                            "{localMentor.mentor.user_review.comment}"
                                                        </p>
                                                    )}
                                                    <div className="text-xs text-gray-400 mt-2">
                                                        {new Date(localMentor.mentor.user_review.created_at).toLocaleDateString()}
                                                    </div>
                                                </div>
                                            )}
                                            
                                            {/* Mostrar otras reseñas anónimas si existen */}
                                            {(localMentor.mentor.anonymized_reviews || []).filter(r => r.id !== localMentor?.mentor?.user_review?.id).length > 0 && (
                                                <>
                                                    <p className="text-xs text-gray-500 mt-4">Otras reseñas</p>
                                                    {(localMentor.mentor.anonymized_reviews || []).filter(r => r.id !== localMentor?.mentor?.user_review?.id).slice(0, 1).map((review, idx) => (
                                                        <div key={idx} className="border rounded p-3 bg-gray-50">
                                                            <div className="flex items-center justify-between mb-2">
                                                                <div className="text-xs text-gray-500">Anónimo</div>
                                                                <div className="text-sm font-semibold text-yellow-600">{review.rating} / 5</div>
                                                            </div>
                                                            {review.comment && (
                                                                <p className="mt-2 text-sm text-gray-700 italic">
                                                                    "{review.comment}"
                                                                </p>
                                                            )}
                                                            <div className="text-xs text-gray-400 mt-2">
                                                                {new Date(review.created_at).toLocaleDateString()}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </>
                                            )}
                                            
                                            {!localMentor?.mentor?.user_review && (localMentor.mentor.anonymized_reviews || []).length === 0 && (
                                                <p className="text-sm text-gray-500">Aún no hay reseñas.</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Documentación - CV */}
                                    {localMentor.mentor.cv_verified && localMentor.mentor.has_public_cv && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-3">Documentación</h4>
                                            <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center">
                                                        <svg className="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        <div>
                                                            <p className="font-medium text-gray-900">Curriculum Vitae</p>
                                                            <p className="text-sm text-gray-600">CV verificado y público</p>
                                                        </div>
                                                    </div>
                                                    <a
                                                        href={`/mentor/${localMentor.id}/cv`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm"
                                                    >
                                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        Ver CV
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Actions */}
                                <div className="mt-8">
                                    {/* Mensaje informativo si hay mentoría activa */}
                                    {hasActiveMentoria && (
                                        <div className="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                            <div className="flex items-start">
                                                <svg className="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                <div>
                                                    <h5 className="font-semibold text-yellow-800 mb-1">Ya tienes una mentoría activa con este mentor</h5>
                                                    <p className="text-sm text-yellow-700">
                                                        Solo puedes tener una mentoría activa por mentor. Una vez que el mentor marque la mentoría actual como concluida, podrás solicitar una nueva sesión.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex flex-col sm:flex-row gap-3">
                                        <button
                                            onClick={() => setIsSolicitudFormOpen(true)}
                                            disabled={localMentor.mentor.disponible_ahora != 1 || hasActiveMentoria || checkingActiveMentoria}
                                            className={`flex-1 inline-flex justify-center items-center px-6 py-3 rounded-lg font-medium transition-colors ${
                                                localMentor.mentor.disponible_ahora == 1 && !hasActiveMentoria && !checkingActiveMentoria
                                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                            }`}
                                            title={hasActiveMentoria ? 'Ya tienes una mentoría activa con este mentor' : (localMentor.mentor.disponible_ahora != 1 ? 'Mentor no disponible' : '')}
                                        >
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            {checkingActiveMentoria ? 'Verificando...' : (hasActiveMentoria ? 'Mentoría Activa' : (localMentor.mentor.disponible_ahora == 1 ? 'Solicitar Mentoría' : 'No Disponible'))}
                                        </button>
                                        <button
                                            onClick={onClose}
                                            className="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                                        >
                                            Cerrar
                                        </button>
                                    </div>
                                </div>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </div>
            </Dialog>
            
            {/* Modal de solicitud de mentoría */}
            <SolicitudMentoriaForm
                isOpen={isSolicitudFormOpen}
                onClose={() => setIsSolicitudFormOpen(false)}
                mentor={mentor}
                aprendiz={aprendiz}
                solicitudesPendientes={solicitudesPendientes}
            />

            {/* Modal de reseña */}
            <ReviewModal
                isOpen={isReviewModalOpen}
                onClose={() => setIsReviewModalOpen(false)}
                mentor={localMentor}
                canReview={canReview}
                userReview={localMentor?.mentor?.user_review}
                onReviewSubmitted={handleReviewSubmitted}
            />
        </Transition>
    );
});

export default MentorDetailModal;