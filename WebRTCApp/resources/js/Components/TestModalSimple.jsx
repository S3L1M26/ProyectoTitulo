import React, { useState } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import InputLabel from '@/Components/InputLabel';

/**
 * Modal de prueba SIN react-hook-form para diagnosticar problemas con inputs nativos
 */
export default function TestModalSimple({ isOpen, onClose }) {
    const [fecha, setFecha] = useState('');
    const [hora, setHora] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('Fecha:', fecha, 'Hora:', hora);
        alert(`Fecha: ${fecha}, Hora: ${hora}`);
    };

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                    Test de Inputs Nativos
                </h3>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <InputLabel htmlFor="test-fecha" value="Fecha (native input)" />
                        <input
                            id="test-fecha"
                            type="date"
                            value={fecha}
                            onChange={(e) => setFecha(e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p className="text-xs text-gray-500 mt-1">Valor: {fecha || 'vacío'}</p>
                    </div>

                    <div>
                        <InputLabel htmlFor="test-hora" value="Hora (native input)" />
                        <input
                            id="test-hora"
                            type="time"
                            value={hora}
                            onChange={(e) => setHora(e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p className="text-xs text-gray-500 mt-1">Valor: {hora || 'vacío'}</p>
                    </div>

                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-sm text-gray-700 hover:text-gray-900"
                        >
                            Cancelar
                        </button>
                        <PrimaryButton type="submit">
                            Probar Submit
                        </PrimaryButton>
                    </div>
                </form>

                <div className="mt-4 p-3 bg-blue-50 rounded text-xs">
                    <p><strong>Diagnóstico:</strong></p>
                    <p>• Si estos pickers funcionan = el problema es react-hook-form</p>
                    <p>• Si NO funcionan = el problema es CSS/Modal/Browser</p>
                </div>
            </div>
        </Modal>
    );
}
