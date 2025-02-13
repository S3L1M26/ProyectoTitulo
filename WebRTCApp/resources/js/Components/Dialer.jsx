import { useState, useEffect } from "react";
import JsSIP from "jssip";

export default function Dialer() {
    const [status, setStatus] = useState("Esperando acción...");
    const [dialedNumber, setDialedNumber] = useState("");
    const [ua, setUa] = useState(null);
    const [currentSession, setCurrentSession] = useState(null);

    useEffect(() => {
        const socket = new JsSIP.WebSocketInterface("wss://webrtc.connect360.cl:8089/ws");

        const configuration = {
            sockets: [socket],
            uri: "sip:39001@webrtc.connect360.cl",
            authorizationUser: "39001",
            password: "P@ssw0rd39001!",
            register: true,
        };

        const userAgent = new JsSIP.UA(configuration);

        userAgent.on("registered", () => setStatus("Usuario 39001 registrado en Asterisk."));
        userAgent.on("registrationFailed", (ev) => setStatus("Error al registrar: " + ev.cause));
        userAgent.on("disconnected", () => setStatus("Desconectado."));
        userAgent.on("newRTCSession", (data) => {
            if (data.originator === "remote") {
                const session = data.session;
                session.answer({ mediaConstraints: { audio: true, video: false } });
                setCurrentSession(session);
                setStatus("Llamada entrante aceptada");
            }
        });

        setUa(userAgent);

        return () => {
            userAgent.stop();
        };
    }, []);

    function registerSip() {
        if (ua) ua.start();
    }

    function appendDigit(digit) {
        setDialedNumber((prev) => prev + digit);
    }

    function deleteLastDigit() {
        setDialedNumber((prev) => prev.slice(0, -1));
    }

    function makeCall() {
        if (!dialedNumber.trim()) return setStatus("No hay número para llamar.");
        if (currentSession) return setStatus("Ya tienes una llamada en curso.");

        const options = {
            mediaConstraints: { audio: true, video: false },
            rtcOfferConstraints: { offerToReceiveAudio: true, offerToReceiveVideo: false },
        };

        const session = ua.call(`sip:${dialedNumber}@186.64.123.211`, options);
        setCurrentSession(session);

        session.on("connecting", () => setStatus(`Llamando a ${dialedNumber}...`));
        session.on("confirmed", () => setStatus("Llamada conectada"));
        session.on("ended", () => {
            setStatus("Llamada finalizada");
            setCurrentSession(null);
        });
        session.on("failed", (data) => {
            setStatus("Llamada fallida: " + data.cause);
            setCurrentSession(null);
        });
    }

    function hangUp() {
        if (currentSession) {
            currentSession.terminate();
            setCurrentSession(null);
            setStatus("Llamada finalizada");
        } else {
            setStatus("No hay llamada en curso.");
        }
    }

    return (
        <div className="text-center bg-gray-100 p-4 rounded-lg shadow-md">
            <h2 className="text-xl font-semibold mb-4">WebRTC con Asterisk (Usuario 39001)</h2>
            <div className="text-green-500 font-bold text-lg mb-2">{status}</div>

            <input
                type="text"
                className="w-full text-lg text-center border p-2 rounded mb-4"
                value={dialedNumber}
                readOnly
            />

            <div className="grid grid-cols-3 gap-2 mb-4">
                {["1", "2", "3", "4", "5", "6", "7", "8", "9", "*", "0", "#"].map((num) => (
                    <button
                        key={num}
                        className="bg-gray-200 p-3 rounded hover:bg-blue-500 hover:text-white"
                        onClick={() => appendDigit(num)}
                    >
                        {num}
                    </button>
                ))}
            </div>

            <button
                className="bg-orange-500 text-white px-4 py-2 rounded mb-4"
                onClick={deleteLastDigit}
            >
                🡠 Borrar
            </button>

            <div className="flex justify-center gap-2">
                <button className="bg-green-500 text-white px-4 py-2 rounded" onClick={registerSip}>
                    Registrar
                </button>
                <button className="bg-blue-500 text-white px-4 py-2 rounded" onClick={makeCall}>
                    Llamar
                </button>
                <button className="bg-red-500 text-white px-4 py-2 rounded" onClick={hangUp}>
                    Colgar
                </button>
            </div>
        </div>
    );
}
