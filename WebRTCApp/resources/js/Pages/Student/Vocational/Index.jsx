import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import VocationalSurveySection from '@/Components/VocationalSurveySection';

export default function VocationalIndex({ vocationalSurveyLatest = null, vocationalSurveyHistory = [] }) {
    const history = Array.isArray(vocationalSurveyHistory) ? vocationalSurveyHistory : [];

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Autoevaluación vocacional
                </h2>
            }
        >
            <Head title="Autoevaluación vocacional" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    <VocationalSurveySection
                        latestSurvey={vocationalSurveyLatest}
                        history={history}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
