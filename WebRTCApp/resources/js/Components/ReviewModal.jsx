import React, { memo, useState, useEffect } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'react-toastify';
import axios from 'axios';

const ReviewModal = memo(function ReviewModal({ isOpen, onClose, mentor, canReview, userReview, onReviewSubmitted }) {
    const [rating, setRating] = useState(userReview?.rating || 5);
    const [hoverRating, setHoverRating] = useState(0);
    const [comment, setComment] = useState(userReview?.comment || '');
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (userReview) {
            setRating(userReview.rating);
            setComment(userReview.comment || '');
        }
    }, [userReview]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsSubmitting(true);

        axios.post(route('mentors.reviews.store', mentor.id), { rating, comment })
            .then((response) => {
                toast.success(userReview ? '¡Reseña actualizada!' : '¡Gracias por tu valoración!');
                setIsSubmitting(false);
                // Notificar al componente padre que se guardó la reseña
                if (onReviewSubmitted) {
                    onReviewSubmitted({
                        rating,
                        comment,
                        created_at: new Date().toISOString()
                    });
                }
                onClose();
            })
            .catch((error) => {
                setIsSubmitting(false);
                if (error.response?.status === 422) {
                    const errors = error.response.data.errors;
                    if (errors?.rating) {
                        toast.error(errors.rating);
                    }
                } else {
                    toast.error('Error al guardar la reseña. Intenta de nuevo.');
                }
                console.error('Review error:', error);
            });
    };

    if (!mentor) return null;

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
                            <Dialog.Panel className="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                {/* Header */}
                                <div className="flex items-center justify-between mb-6">
                                    <div>
                                        <Dialog.Title as="h3" className="text-2xl font-bold text-gray-900">
                                            Reseña para {mentor.name}
                                        </Dialog.Title>
                                        <p className="text-sm text-gray-600 mt-1">
                                            {userReview ? 'Actualiza tu valoración' : 'Comparte tu experiencia'}
                                        </p>
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
                                {!canReview && !userReview && (
                                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                        <div className="flex items-start">
                                            <svg className="w-5 h-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            <div>
                                                <h5 className="font-semibold text-yellow-800">No puedes reseñar aún</h5>
                                                <p className="text-sm text-yellow-700 mt-1">
                                                    Debes completar al menos una mentoría con este mentor para poder dejar una reseña.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {(canReview || userReview) && (
                                    <form onSubmit={handleSubmit} className="space-y-4">
                                        {/* Estrellas */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                                Calificación
                                            </label>
                                            <div className="flex items-center gap-2">
                                                <div className="flex items-center gap-1">
                                                    {[1, 2, 3, 4, 5].map((star) => (
                                                        <button
                                                            key={star}
                                                            type="button"
                                                            onMouseEnter={() => setHoverRating(star)}
                                                            onMouseLeave={() => setHoverRating(0)}
                                                            onClick={() => setRating(star)}
                                                            disabled={!canReview && !userReview}
                                                            className="focus:outline-none transition-transform hover:scale-110"
                                                        >
                                                            <svg
                                                                className={`w-8 h-8 transition-colors ${
                                                                    (hoverRating || rating) >= star
                                                                        ? 'text-yellow-400'
                                                                        : 'text-gray-300'
                                                                }`}
                                                                fill="currentColor"
                                                                viewBox="0 0 20 20"
                                                            >
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.034a1 1 0 00-1.175 0l-2.802 2.034c-.784.57-1.838-.197-1.54-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </button>
                                    ))}
                                </div>
                                <span className="ml-3 text-lg font-semibold text-gray-700">{rating} / 5</span>
                            </div>
                        </div>

                        {/* Comentario */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Comentario (opcional)
                            </label>
                            <textarea
                                value={comment}
                                onChange={(e) => setComment(e.target.value)}
                                rows={4}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Cuéntanos tu experiencia con este mentor..."
                                disabled={!canReview && !userReview}
                            />
                            <p className="text-xs text-gray-500 mt-1">
                                {comment.length} / 2000 caracteres
                            </p>
                        </div>

                        {/* Botones */}
                        <div className="flex gap-3 pt-4">
                            <button
                                type="button"
                                onClick={onClose}
                                className="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                disabled={isSubmitting || (!canReview && !userReview)}
                                className={`flex-1 px-4 py-2 rounded-lg font-medium transition-colors ${
                                    canReview || userReview
                                        ? 'bg-blue-600 text-white hover:bg-blue-700 disabled:bg-gray-300'
                                        : 'bg-gray-300 text-gray-600 cursor-not-allowed'
                                }`}
                            >
                                {isSubmitting ? 'Enviando...' : (userReview ? 'Actualizar reseña' : 'Enviar reseña')}
                            </button>
                        </div>
                    </form>
                )}
            </Dialog.Panel>
        </Transition.Child>
    </div>
</div>
</Dialog>
</Transition>
);
});

export default ReviewModal;
