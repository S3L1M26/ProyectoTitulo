import React, { useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { toast } from 'react-toastify';

export default function SolicitudMentoriaForm({ isOpen, onClose, mentor, aprendiz, solicitudesPendientes = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        mentor_id: mentor?.id || '',
        mensaje: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        post(route('solicitud-mentoria.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('¡Solicitud de mentoría enviada! El mentor será notificado.');
                reset();
                onClose();
            },
            onError: (errors) => {
                if (errors.perfil) {
                    toast.error(errors.perfil);
                } else if (errors.certificado) {
                    toast.error(errors.certificado);
                } else if (errors.mensaje) {
                    toast.error('Por favor escribe un mensaje para el mentor.');
                } else {
                    toast.error('No se pudo enviar la solicitud. Inténtalo nuevamente.');
                }
            },
        });
    };

    // Verificar si el perfil está completo
    const isProfileIncomplete = !aprendiz?.certificate_verified;
    const isMentorUnavailable = !mentor?.mentor?.disponible_ahora;
    
    // Verificar si ya hay una solicitud pendiente
    const hasPendingRequest = solicitudesPendientes.some(
        s => s.mentor_id === mentor?.id && s.estado === 'pendiente'
    );

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
                                <div className="flex items-start justify-between mb-6">
                                    <Dialog.Title as="h3" className="text-xl font-bold text-gray-900">
                                        Solicitar Mentoría
                                    </Dialog.Title>
                                    <button
                                        onClick={onClose}
                                        className="text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                {/* Mentor Info */}
                                <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                                    <div className="flex items-center space-x-3">
                                        <div className="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                            {mentor.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <h4 className="font-semibold text-gray-900">{mentor.name}</h4>
                                            <p className="text-sm text-gray-600">
                                                {mentor.mentor.años_experiencia} años de experiencia
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Validaciones y alertas */}
                                {isProfileIncomplete ? (
                                    <div className="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                                        <div className="flex">
                                            <div className="flex-shrink-0">
                                                <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                            <div className="ml-3">
                                                <p className="text-sm text-yellow-700">
                                                    Debes tener tu certificado verificado antes de solicitar mentoría.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ) : hasPendingRequest ? (
                                    <div className="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 rounded">
                                        <div className="flex">
                                            <div className="flex-shrink-0">
                                                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                            <div className="ml-3">
                                                <p className="text-sm text-blue-700">
                                                    Ya tienes una solicitud pendiente con este mentor. Espera su respuesta.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ) : isMentorUnavailable ? (
                                    <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-400 rounded">
                                        <div className="flex">
                                            <div className="flex-shrink-0">
                                                <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                            <div className="ml-3">
                                                <p className="text-sm text-red-700">
                                                    Este mentor no está disponible en este momento.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ) : null}

                                {/* Form */}
                                <form onSubmit={handleSubmit}>
                                    <div className="mb-6">
                                        <label htmlFor="mensaje" className="block text-sm font-medium text-gray-700 mb-2">
                                            Mensaje personalizado (opcional)
                                        </label>
                                        <textarea
                                            id="mensaje"
                                            rows="4"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Cuéntale al mentor sobre tus objetivos y por qué te gustaría trabajar con él/ella..."
                                            value={data.mensaje}
                                            onChange={(e) => setData('mensaje', e.target.value)}
                                            disabled={isProfileIncomplete || isMentorUnavailable || hasPendingRequest}
                                        />
                                        <InputError message={errors.mensaje} className="mt-2" />
                                        <p className="mt-1 text-xs text-gray-500">
                                            Máximo 1000 caracteres
                                        </p>
                                    </div>

                                    {/* Error general */}
                                    {(errors.perfil || errors.certificado || errors.mentor || errors.disponibilidad || errors.solicitud) && (
                                        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <p className="text-sm text-red-600">
                                                {errors.perfil || errors.certificado || errors.mentor || errors.disponibilidad || errors.solicitud}
                                            </p>
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex justify-end space-x-3">
                                        <SecondaryButton onClick={onClose} type="button">
                                            Cancelar
                                        </SecondaryButton>
                                        <PrimaryButton
                                            disabled={processing || isProfileIncomplete || isMentorUnavailable || hasPendingRequest}
                                        >
                                            {processing ? 'Enviando...' : 'Enviar Solicitud'}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
