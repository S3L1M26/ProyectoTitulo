import { Link, Head } from '@inertiajs/react';

export default function Welcome({ canLogin, canRegister }) {
    return (
        <>
            <Head title="Bienvenido - Plataforma de Mentorías" />
            
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
                {/* Navigation */}
                <nav className="fixed w-full bg-white/80 backdrop-blur-md shadow-sm z-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-16">
                            <div className="flex items-center">
                                <h1 className="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    MentorMatch
                                </h1>
                            </div>
                            {canLogin && (
                                <div className="flex items-center gap-4">
                                    <div className="relative inline-block text-left">
                                        <div className="group">
                                            <button type="button" className="text-gray-700 hover:text-blue-600 font-medium transition px-3 py-2">
                                                Iniciar Sesión
                                            </button>
                                            <div className="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 invisible group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100 transform scale-95 group-hover:scale-100 transition-all">
                                                <div className="py-1">
                                                    <Link
                                                        href={route('login', { role: 'mentor' })}
                                                        className="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                                                    >
                                                        Como mentor
                                                    </Link>
                                                    <Link
                                                        href={route('login', { role: 'student' })}
                                                        className="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                                                    >
                                                        Como estudiante
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {canRegister && (
                                        <div className="group">
                                            <button
                                                className="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2 rounded-lg font-medium hover:shadow-lg transition transform hover:scale-105"
                                            >
                                                Registrarse
                                            </button>
                                            <div className="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 invisible group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100 transform scale-95 group-hover:scale-100 transition-all">
                                                <div className="py-1">
                                                    <Link
                                                        href={route('register', { role: 'mentor' })}
                                                        className="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                                                    >
                                                        Como mentor
                                                    </Link>
                                                    <Link
                                                        href={route('register', { role: 'student' })}
                                                        className="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                                                    >
                                                        Como estudiante
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <section className="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-7xl mx-auto">
                        <div className="grid lg:grid-cols-2 gap-12 items-center">
                            <div>
                                <h1 className="text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                                    Descubre la{' '}
                                    <span className="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                        realidad laboral en TI
                                    </span>
                                </h1>
                                <p className="text-xl text-gray-600 mb-8 leading-relaxed">
                                    Habla con egresados profesionales de distintas áreas de TI. 
                                    Conoce sus experiencias, gustos personales y el día a día en el mundo laboral.
                                </p>
                                <div className="flex flex-col sm:flex-row gap-4">
                                    <Link
                                        href={route('register')}
                                        className="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:shadow-xl transition transform hover:scale-105 text-center"
                                    >
                                        Comenzar Gratis
                                    </Link>
                                    <a
                                        href="#como-funciona"
                                        className="bg-white border-2 border-gray-300 text-gray-700 px-8 py-4 rounded-lg font-semibold text-lg hover:border-blue-600 hover:text-blue-600 transition text-center"
                                    >
                                        Ver Más
                                    </a>
                                </div>
                            </div>
                            
                            <div className="relative">
                                <div className="relative z-10 bg-white rounded-2xl shadow-2xl p-8">
                                    <div className="space-y-4">
                                        <div className="flex items-center gap-4">
                                            <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                                1
                                            </div>
                                            <div>
                                                <h3 className="font-semibold text-gray-900">Crea tu perfil</h3>
                                                <p className="text-gray-600 text-sm">Define qué áreas de TI te interesan conocer</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                                2
                                            </div>
                                            <div>
                                                <h3 className="font-semibold text-gray-900">Encuentra egresados</h3>
                                                <p className="text-gray-600 text-sm">Explora profesionales de distintas áreas</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                                3
                                            </div>
                                            <div>
                                                <h3 className="font-semibold text-gray-900">Conversa con ellos</h3>
                                                <p className="text-gray-600 text-sm">Sesiones informales por videollamada</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="absolute -top-4 -right-4 w-72 h-72 bg-gradient-to-r from-blue-400 to-purple-400 rounded-full blur-3xl opacity-20"></div>
                                <div className="absolute -bottom-4 -left-4 w-72 h-72 bg-gradient-to-r from-purple-400 to-pink-400 rounded-full blur-3xl opacity-20"></div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Cómo Funciona */}
                <section id="como-funciona" className="py-20 bg-white">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-4xl font-bold text-gray-900 mb-4">
                                ¿Cómo funciona?
                            </h2>
                            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
                                Tres simples pasos para conocer la realidad laboral en TI
                            </p>
                        </div>

                        <div className="grid md:grid-cols-3 gap-8">
                            <div className="bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition">
                                <div className="text-5xl mb-4">👤</div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">
                                    Regístrate y completa tu perfil
                                </h3>
                                <p className="text-gray-600 leading-relaxed">
                                    Crea tu cuenta como estudiante. Indica qué áreas de TI te gustaría explorar y qué te gustaría saber sobre el mundo laboral.
                                </p>
                            </div>
                            <div className="bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition">
                                <div className="text-5xl mb-4">🔍</div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">
                                    Explora perfiles de egresados
                                </h3>
                                <p className="text-gray-600 leading-relaxed">
                                    Descubre profesionales trabajando en diferentes áreas: desarrollo web, data science, seguridad, UX/UI y más. Lee sobre sus experiencias y rutinas diarias.
                                </p>
                            </div>
                            <div className="bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition">
                                <div className="text-5xl mb-4">📅</div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">
                                    Agenda conversaciones informales
                                </h3>
                                <p className="text-gray-600 leading-relaxed">
                                    Envía solicitudes para conversar, coordina horarios y recibe enlaces de Zoom automáticamente. Pregunta sobre su trabajo, desafíos y consejos.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Beneficios */}
                <section className="py-20 bg-gradient-to-br from-blue-50 to-purple-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-4xl font-bold text-gray-900 mb-4">
                                ¿Por qué elegir MentorMatch?
                            </h2>
                            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
                                La plataforma que conecta estudiantes con la realidad del mundo laboral en TI
                            </p>
                        </div>

                        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">✅</div>
                                <h3 className="font-bold text-gray-900 mb-2">Egresados Verificados</h3>
                                <p className="text-gray-600 text-sm">
                                    Todos los profesionales pasan por verificación de CV y experiencia laboral
                                </p>
                            </div>
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">🎯</div>
                                <h3 className="font-bold text-gray-900 mb-2">Diversidad de Áreas</h3>
                                <p className="text-gray-600 text-sm">
                                    Profesionales de desarrollo, data, seguridad, DevOps, UX/UI y más áreas de TI
                                </p>
                            </div>
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">📧</div>
                                <h3 className="font-bold text-gray-900 mb-2">Notificaciones en Tiempo Real</h3>
                                <p className="text-gray-600 text-sm">
                                    Mantente al día con emails y notificaciones sobre tus solicitudes
                                </p>
                            </div>
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">🎥</div>
                                <h3 className="font-bold text-gray-900 mb-2">Conversaciones Reales</h3>
                                <p className="text-gray-600 text-sm">
                                    Videollamadas directas con profesionales para conocer su día a día y experiencias
                                </p>
                            </div>
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">⏰</div>
                                <h3 className="font-bold text-gray-900 mb-2">Fácil Programación</h3>
                                <p className="text-gray-600 text-sm">
                                    Agenda conversaciones según tu disponibilidad con recordatorios automáticos
                                </p>
                            </div>
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">📊</div>
                                <h3 className="font-bold text-gray-900 mb-2">Conoce sus Gustos</h3>
                                <p className="text-gray-600 text-sm">
                                    Descubre qué les gusta y qué no de su trabajo, proyectos favoritos y desafíos
                                </p>
                            </div>
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">🔒</div>
                                <h3 className="font-bold text-gray-900 mb-2">Seguro y Confiable</h3>
                                <p className="text-gray-600 text-sm">
                                    Plataforma segura con protección de datos personales
                                </p>
                            </div>
                            <div className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                                <div className="text-4xl mb-3">💬</div>
                                <h3 className="font-bold text-gray-900 mb-2">Orientación Profesional</h3>
                                <p className="text-gray-600 text-sm">
                                    Obtén consejos reales sobre qué esperar y cómo prepararte para el mundo laboral
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
                    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <div>
                            <h2 className="text-4xl font-bold text-white mb-6">
                                ¿Listo para conocer el mundo laboral en TI?
                            </h2>
                            <p className="text-xl text-blue-100 mb-8">
                                Únete a estudiantes que están descubriendo la realidad de trabajar en tecnología
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                <Link
                                    href={route('register')}
                                    className="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold text-lg hover:shadow-xl transition transform hover:scale-105"
                                >
                                    Comenzar a Explorar
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-blue-600 transition"
                                >
                                    Ser Mentor (Egresado)
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-900 text-white py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid md:grid-cols-3 gap-8">
                            <div>
                                <h3 className="text-2xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                                    MentorMatch
                                </h3>
                                <p className="text-gray-400">
                                    Conectando estudiantes con egresados profesionales para descubrir la realidad laboral en TI.
                                </p>
                            </div>
                            <div>
                                <h4 className="font-semibold mb-4">Enlaces</h4>
                                <ul className="space-y-2 text-gray-400">
                                    <li>
                                        <a href="#como-funciona" className="hover:text-white transition">
                                            Cómo funciona
                                        </a>
                                    </li>
                                    <li>
                                        <Link href={route('login')} className="hover:text-white transition">
                                            Iniciar Sesión
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('register')} className="hover:text-white transition">
                                            Registrarse
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-semibold mb-4">Contacto</h4>
                                <p className="text-gray-400">
                                    ¿Tienes preguntas? Estamos aquí para ayudarte.
                                </p>
                                <p className="text-gray-400 mt-2">
                                    contacto@mentormatch.com
                                </p>
                            </div>
                        </div>
                        <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                            <p>&copy; {new Date().getFullYear()} MentorMatch. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
