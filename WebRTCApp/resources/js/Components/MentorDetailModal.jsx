import React, { memo, useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import SolicitudMentoriaForm from '@/Components/SolicitudMentoriaForm';
import { router } from '@inertiajs/react';
import { toast } from 'react-toastify';

const MentorDetailModal = memo(function MentorDetailModal({ isOpen, onClose, mentor, aprendiz, solicitudesPendientes = [] }) {
    const [isSolicitudFormOpen, setIsSolicitudFormOpen] = useState(false);
    
    if (!mentor) return null;
    
    // (debug removido)

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
                                            {mentor.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <Dialog.Title as="h3" className="text-2xl font-bold text-gray-900">
                                                {mentor.name}
                                            </Dialog.Title>
                                            <div className="flex items-center mt-1">
                                                <span className="text-yellow-400 mr-1">★</span>
                                                <span className="text-sm font-medium text-gray-700 mr-3">
                                                    {mentor.mentor.calificacionPromedio ? Number(mentor.mentor.calificacionPromedio).toFixed(1) : '0.0'}/5
                                                </span>
                                                <span className="text-sm text-gray-600">
                                                    {mentor.mentor.años_experiencia} años de experiencia
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
                                            {mentor.mentor.areas_interes.map((area) => (
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
                                    {mentor.mentor.biografia && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-3">Sobre mí</h4>
                                            <p className="text-gray-700 leading-relaxed">
                                                {mentor.mentor.biografia}
                                            </p>
                                        </div>
                                    )}

                                    {/* Experiencia */}
                                    {mentor.mentor.experiencia && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-3">Experiencia Profesional</h4>
                                            <p className="text-gray-700 leading-relaxed">
                                                {mentor.mentor.experiencia}
                                            </p>
                                        </div>
                                    )}

                                    {/* Disponibilidad */}
                                    <div>
                                        <h4 className="text-lg font-semibold text-gray-900 mb-3">Disponibilidad</h4>
                                        {mentor.mentor.disponible_ahora == 1 ? (
                                            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                                <div className="flex items-center mb-2">
                                                    <div className="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
                                                    <span className="text-green-800 font-medium">Disponible para mentorías</span>
                                                </div>
                                                {mentor.mentor.disponibilidad && (
                                                    <p className="text-green-700 mb-2">
                                                        <strong>Horarios:</strong> {mentor.mentor.disponibilidad}
                                                    </p>
                                                )}
                                                {mentor.mentor.disponibilidad_detalle && (
                                                    <p className="text-green-700 text-sm">
                                                        <strong>Detalles:</strong> {mentor.mentor.disponibilidad_detalle}
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

                                    {/* Documentación - CV */}
                                    {mentor.mentor.cv_verified && mentor.mentor.has_public_cv && (
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
                                                        href={`/mentor/${mentor.id}/cv`}
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
                                <div className="mt-8 flex flex-col sm:flex-row gap-3">
                                    <button
                                        onClick={() => setIsSolicitudFormOpen(true)}
                                        disabled={mentor.mentor.disponible_ahora != 1}
                                        className={`flex-1 inline-flex justify-center items-center px-6 py-3 rounded-lg font-medium transition-colors ${
                                            mentor.mentor.disponible_ahora == 1
                                                ? 'bg-blue-600 text-white hover:bg-blue-700'
                                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                        }`}
                                    >
                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        {mentor.mentor.disponible_ahora == 1 ? 'Solicitar Mentoría' : 'No Disponible'}
                                    </button>
                                    <button
                                        onClick={onClose}
                                        className="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                                    >
                                        Cerrar
                                    </button>
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
        </Transition>
    );
});

export default MentorDetailModal;