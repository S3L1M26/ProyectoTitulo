import React, { useMemo } from 'react';
import { useForm, router } from '@inertiajs/react';
import { toast } from 'react-toastify';

const scaleOptions = [1, 2, 3, 4, 5];

function getBadge(icv) {
    if (icv === null || icv === undefined) {
        return { label: 'Sin datos', className: 'bg-gray-100 text-gray-700' };
    }
    if (icv < 2.5) return { label: 'Claridad baja', className: 'bg-red-100 text-red-800' };
    if (icv <= 3.5) return { label: 'Claridad media', className: 'bg-yellow-100 text-yellow-800' };
    return { label: 'Claridad alta', className: 'bg-green-100 text-green-800' };
}

export default function VocationalSurveySection({ latestSurvey, history = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        clarity_interest: 3,
        confidence_area: 3,
        platform_usefulness: 3,
        mentorship_usefulness: 3,
        recent_change_reason: '',
    });

    const badge = useMemo(() => getBadge(latestSurvey?.icv), [latestSurvey]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('student.vocational.store'), {
            onSuccess: () => {
                toast.success('Autoevaluación guardada');
                reset('recent_change_reason');
            },
            onError: (formErrors) => {
                Object.values(formErrors || {}).forEach((err) => toast.error(err));
            },
        });
    };

    const renderSelect = (name, label) => (
        <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
            <select
                value={data[name]}
                onChange={(e) => setData(name, Number(e.target.value))}
                className="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                disabled={processing}
            >
                {scaleOptions.map((n) => (
                    <option key={n} value={n}>
                        {n}
                    </option>
                ))}
            </select>
            {errors[name] && <p className="text-xs text-red-600 mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <section id="vocacional" className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900">Autoevaluación vocacional</h3>
                        <p className="text-sm text-gray-600">
                            Responde en 1 minuto para medir tu claridad vocacional y ver tu progreso.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-gray-600">ICV actual</span>
                        <span className={`inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full ${badge.className}`}>
                            {latestSurvey?.icv ? latestSurvey.icv.toFixed(2) : '--'} · {badge.label}
                        </span>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {renderSelect('clarity_interest', '¿Qué tan claro tienes tu interés profesional dentro del área TI?')}
                    {renderSelect('confidence_area', '¿Qué tan seguro te sientes respecto al área TI que te gustaría explorar?')}
                    {renderSelect('platform_usefulness', '¿Qué tan útil ha sido la plataforma para entender tus opciones en TI?')}
                    {renderSelect('mentorship_usefulness', '¿Qué tan útil fue tu última mentoría para aclarar tus intereses?')}

                    <div className="md:col-span-2">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            ¿Has cambiado tus áreas de interés recientemente? ¿Por qué?
                        </label>
                        <input
                            type="text"
                            maxLength={200}
                            value={data.recent_change_reason}
                            onChange={(e) => setData('recent_change_reason', e.target.value)}
                            className="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Respuesta breve (máx. 200 caracteres)"
                            disabled={processing}
                        />
                        <div className="flex justify-between text-xs text-gray-500 mt-1">
                            <span>{errors.recent_change_reason}</span>
                            <span>{data.recent_change_reason.length}/200</span>
                        </div>
                    </div>

                    <div className="md:col-span-2 flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-60"
                        >
                            {processing ? 'Enviando...' : 'Enviar autoevaluación'}
                        </button>
                    </div>
                </form>

                <div>
                    <h4 className="text-sm font-semibold text-gray-800 mb-2">Historial</h4>
                    {history.length === 0 ? (
                        <p className="text-sm text-gray-500">Aún no hay respuestas. Completa tu primera autoevaluación.</p>
                    ) : (
                        <div className="space-y-2">
                            {history.map((item) => {
                                const itemBadge = getBadge(item.icv);
                                return (
                                    <div key={item.id} className="border rounded-lg p-3 bg-gray-50">
                                        <div className="flex items-center justify-between mb-1">
                                            <span className="text-sm text-gray-600">
                                                {new Date(item.created_at).toLocaleDateString()}
                                            </span>
                                            <span className={`inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full ${itemBadge.className}`}>
                                                {item.icv.toFixed(2)} · {itemBadge.label}
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-700">
                                            <div>Claridad: {item.clarity_interest}</div>
                                            <div>Seguridad: {item.confidence_area}</div>
                                            <div>Plataforma: {item.platform_usefulness}</div>
                                            <div>Mentoría: {item.mentorship_usefulness}</div>
                                        </div>
                                        {item.recent_change_reason && (
                                            <p className="mt-2 text-sm text-gray-700">
                                                <span className="font-semibold">Cambio reciente: </span>
                                                {item.recent_change_reason}
                                            </p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
