import React, { useState, Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import { toast } from 'react-toastify';

export default function ContactarMentorModal({ isOpen, onClose, mentor }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    asunto: '',
    mensaje: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('student.mentores.contactar', mentor.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Mensaje enviado al mentor.');
        reset();
        onClose();
      },
      onError: (errs) => {
        const msg = errs.contacto || errs.asunto || errs.mensaje || 'No se pudo enviar el mensaje.';
        toast.error(msg);
      },
    });
  };

  if (!mentor) return null;

  return (
    <Transition show={isOpen} as={Fragment} appear>
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
              <Dialog.Panel className="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                <div className="flex items-start justify-between mb-4">
                  <Dialog.Title className="text-xl font-bold text-gray-900">Contactar Mentor</Dialog.Title>
                  <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <p className="text-sm text-gray-600 mb-4">Este mensaje será enviado directamente al correo de <strong>{mentor.name}</strong>. Sólo úsalo para aclarar dudas previas a la mentoría.</p>
                <form onSubmit={handleSubmit} className="space-y-5">
                  <div>
                    <label htmlFor="asunto" className="block text-sm font-medium text-gray-700 mb-1">Asunto</label>
                    <input
                      id="asunto"
                      type="text"
                      value={data.asunto}
                      onChange={e => setData('asunto', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      maxLength={150}
                      required
                    />
                    <InputError message={errors.asunto} className="mt-2" />
                  </div>
                  <div>
                    <label htmlFor="mensaje" className="block text-sm font-medium text-gray-700 mb-1">Mensaje</label>
                    <textarea
                      id="mensaje"
                      rows={6}
                      value={data.mensaje}
                      onChange={e => setData('mensaje', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      maxLength={2000}
                      required
                      placeholder="Describe tus dudas u objetivos que deseas aclarar antes de la sesión..."
                    />
                    <InputError message={errors.mensaje} className="mt-2" />
                    <p className="mt-1 text-xs text-gray-500">Máx. 2000 caracteres</p>
                  </div>
                  {(errors.contacto) && (
                    <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{errors.contacto}</div>
                  )}
                  <div className="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" onClick={onClose}>Cancelar</SecondaryButton>
                    <PrimaryButton disabled={processing}>{processing ? 'Enviando...' : 'Enviar Mensaje'}</PrimaryButton>
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
