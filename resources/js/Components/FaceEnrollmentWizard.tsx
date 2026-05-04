import { router } from '@inertiajs/react';
import * as faceapi from 'face-api.js';
import { ArrowUpRight, Camera, RotateCcw, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

/**
 * Face enrollment wizard (HR-only). Locked 2026-04-30 with Walid:
 * 3 photos, face-api.js client-side descriptor, 12-month cadence.
 *
 * Stages:
 *   loading-models → requesting-camera → capturing (×3) → review → submitting → done
 *
 * The user (HR) launches this from the employee detail page. The
 * descriptor is computed in the browser and POSTed alongside the 3
 * JPEGs; the server stores both and mirrors the descriptor onto
 * employees.face_descriptor for fast read at attendance check-in time.
 */

const MODELS_URL = '/face-models';
const PHOTO_COUNT = 3;
const MIN_QUALITY_SCORE = 0.7;

const PHOTO_GUIDANCE = [
    'Photo 1 of 3 — Front-facing, neutral expression.',
    'Photo 2 of 3 — Slight head turn LEFT (~15°).',
    'Photo 3 of 3 — Slight head turn RIGHT (~15°).',
];

type Stage =
    | 'loading-models'
    | 'requesting-camera'
    | 'capturing'
    | 'computing'
    | 'review'
    | 'submitting'
    | 'done'
    | 'error';

type Capture = {
    blob: Blob;
    dataUrl: string;
    descriptor: Float32Array;
    confidence: number;
};

type Props = {
    employeeId: number;
    employeeName: string;
    employeeCode: string;
    onClose: () => void;
};

export default function FaceEnrollmentWizard({
    employeeId,
    employeeName,
    employeeCode,
    onClose,
}: Props) {
    const [stage, setStage] = useState<Stage>('loading-models');
    const [errorMessage, setErrorMessage] = useState<string>('');
    const [captures, setCaptures] = useState<Capture[]>([]);
    const [serverErrors, setServerErrors] = useState<string[]>([]);
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);

    const captureIndex = captures.length; // next slot to capture

    useEffect(() => {
        let cancelled = false;
        async function loadModels() {
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL),
                ]);
                if (cancelled) return;
                setStage('requesting-camera');
                requestCamera();
            } catch {
                if (cancelled) return;
                setErrorMessage(
                    'Could not load face-recognition models. Make sure the weights live in /public/face-models/. See public/face-models/README.txt.',
                );
                setStage('error');
            }
        }
        loadModels();
        return () => {
            cancelled = true;
            stopCamera();
        };
    }, []);

    async function requestCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480, facingMode: 'user' },
                audio: false,
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }
            setStage('capturing');
        } catch {
            setErrorMessage(
                'Camera access denied. Click the camera icon in the address bar and allow access, then retry.',
            );
            setStage('error');
        }
    }

    function stopCamera() {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((t) => t.stop());
            streamRef.current = null;
        }
    }

    async function captureCurrent() {
        if (!videoRef.current) return;

        setStage('computing');
        try {
            const video = videoRef.current;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            if (!ctx) throw new Error('canvas ctx unavailable');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const detection = await faceapi
                .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                throw new Error(
                    'No face detected. Center your face in the frame and try again.',
                );
            }

            const blob: Blob = await new Promise((resolve, reject) => {
                canvas.toBlob(
                    (b) => (b ? resolve(b) : reject(new Error('blob fail'))),
                    'image/jpeg',
                    0.92,
                );
            });
            const dataUrl = canvas.toDataURL('image/jpeg', 0.7);

            const next: Capture = {
                blob,
                dataUrl,
                descriptor: detection.descriptor,
                confidence: detection.detection.score,
            };
            setCaptures((prev) => {
                const updated = [...prev, next];
                if (updated.length === PHOTO_COUNT) {
                    setStage('review');
                } else {
                    setStage('capturing');
                }
                return updated;
            });
        } catch (e) {
            setErrorMessage(
                e instanceof Error ? e.message : 'Capture failed.',
            );
            // Roll back to the capture stage so HR can retry without
            // losing prior photos.
            setStage('capturing');
        }
    }

    function retakeAll() {
        setCaptures([]);
        setErrorMessage('');
        setStage('capturing');
    }

    async function save() {
        if (captures.length !== PHOTO_COUNT) return;

        // Average the 3 descriptors element-wise → canonical 128-vector.
        const canonical = new Float32Array(128);
        for (const cap of captures) {
            for (let i = 0; i < 128; i++) canonical[i] += cap.descriptor[i];
        }
        for (let i = 0; i < 128; i++) canonical[i] /= captures.length;

        const meanConfidence =
            captures.reduce((acc, c) => acc + c.confidence, 0) /
            captures.length;

        if (meanConfidence < MIN_QUALITY_SCORE) {
            setErrorMessage(
                `Quality score ${meanConfidence.toFixed(2)} is below ${MIN_QUALITY_SCORE}. Improve lighting, remove glasses, and use a plain background — then retake.`,
            );
            return;
        }

        const formData = new FormData();
        captures.forEach((cap, i) => {
            formData.append(
                `photos[${i}]`,
                new File([cap.blob], `photo-${i + 1}.jpg`, {
                    type: 'image/jpeg',
                }),
            );
        });
        Array.from(canonical).forEach((value, i) => {
            formData.append(`descriptor[${i}]`, String(value));
        });
        formData.append('quality_score', meanConfidence.toFixed(4));

        setStage('submitting');
        setServerErrors([]);
        router.post(`/employees/${employeeId}/face-enrollment`, formData, {
            forceFormData: true,
            onSuccess: () => {
                stopCamera();
                setStage('done');
            },
            onError: (errors) => {
                setServerErrors(Object.values(errors).flat() as string[]);
                setStage('review');
            },
        });
    }

    const progressPercent =
        stage === 'done'
            ? 100
            : Math.round((captures.length / PHOTO_COUNT) * 100);

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-yzh-ink/70 p-4"
            role="dialog"
            aria-modal="true"
        >
            <div className="relative w-full max-w-3xl rounded bg-yzh-bone shadow-2xl">
                <button
                    type="button"
                    onClick={() => {
                        stopCamera();
                        onClose();
                    }}
                    className="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-slate hover:bg-yzh-bone-soft hover:text-yzh-ink"
                    aria-label="Close"
                >
                    <X className="h-5 w-5" aria-hidden="true" />
                </button>

                <div className="border-b border-yzh-bone-soft px-6 py-4">
                    <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold">
                        Face enrollment / {progressPercent}%
                    </p>
                    <h2 className="mt-1 text-xl font-semibold tracking-tight text-yzh-ink">
                        {employeeName}.
                    </h2>
                    <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                        {employeeCode}
                    </p>
                </div>

                <div className="px-6 py-6">
                    {stage === 'loading-models' && (
                        <p className="text-sm text-yzh-slate">
                            Loading face-recognition models…
                        </p>
                    )}
                    {stage === 'requesting-camera' && (
                        <p className="text-sm text-yzh-slate">
                            Awaiting camera permission. Allow in the browser
                            prompt to continue.
                        </p>
                    )}

                    {stage === 'error' && (
                        <div className="space-y-4">
                            <p className="text-sm text-red-700">
                                {errorMessage}
                            </p>
                            <button
                                type="button"
                                onClick={() => {
                                    setErrorMessage('');
                                    setStage('loading-models');
                                    requestCamera();
                                }}
                                className="inline-flex min-h-11 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink"
                            >
                                <RotateCcw
                                    className="h-3.5 w-3.5"
                                    aria-hidden="true"
                                />
                                Try again
                            </button>
                        </div>
                    )}

                    {(stage === 'capturing' || stage === 'computing') && (
                        <div className="space-y-4">
                            <div className="overflow-hidden rounded border border-yzh-bone-soft bg-yzh-ink">
                                <video
                                    ref={videoRef}
                                    className="aspect-video w-full"
                                    muted
                                    playsInline
                                />
                            </div>
                            <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                                {PHOTO_GUIDANCE[captureIndex] ?? ''}
                            </p>
                            {errorMessage && (
                                <p className="text-sm text-red-700">
                                    {errorMessage}
                                </p>
                            )}
                            <div className="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    onClick={captureCurrent}
                                    disabled={stage === 'computing'}
                                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <Camera
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                    <span>
                                        {stage === 'computing'
                                            ? 'Computing'
                                            : `Capture photo ${captureIndex + 1} of ${PHOTO_COUNT}`}
                                    </span>
                                </button>
                                {captures.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={retakeAll}
                                        className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate hover:text-yzh-gold"
                                    >
                                        Retake all
                                    </button>
                                )}
                            </div>
                            {captures.length > 0 && (
                                <Thumbnails captures={captures} />
                            )}
                        </div>
                    )}

                    {stage === 'review' && (
                        <div className="space-y-5">
                            <Thumbnails captures={captures} />
                            <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                                Quality score{' '}
                                {(
                                    captures.reduce(
                                        (acc, c) => acc + c.confidence,
                                        0,
                                    ) / captures.length
                                ).toFixed(2)}
                            </p>
                            {errorMessage && (
                                <p className="text-sm text-red-700">
                                    {errorMessage}
                                </p>
                            )}
                            {serverErrors.length > 0 && (
                                <ul className="space-y-1 text-sm text-red-700">
                                    {serverErrors.map((msg) => (
                                        <li key={msg}>{msg}</li>
                                    ))}
                                </ul>
                            )}
                            <div className="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    onClick={save}
                                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink"
                                >
                                    <span>Save enrollment</span>
                                    <ArrowUpRight
                                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                        aria-hidden="true"
                                    />
                                </button>
                                <button
                                    type="button"
                                    onClick={retakeAll}
                                    className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate hover:text-yzh-gold"
                                >
                                    Retake all
                                </button>
                            </div>
                        </div>
                    )}

                    {stage === 'submitting' && (
                        <p className="text-sm text-yzh-slate">
                            Saving enrollment…
                        </p>
                    )}

                    {stage === 'done' && (
                        <div className="space-y-4">
                            <p className="text-base font-semibold text-yzh-ink">
                                Enrollment complete.
                            </p>
                            <p className="text-sm text-yzh-slate">
                                {employeeName} can now check in via face
                                verification. Re-enrollment due in 12 months.
                            </p>
                            <button
                                type="button"
                                onClick={onClose}
                                className="inline-flex min-h-11 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink"
                            >
                                Done
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

function Thumbnails({ captures }: { captures: Capture[] }) {
    return (
        <ul className="grid grid-cols-3 gap-3">
            {captures.map((cap, i) => (
                <li
                    key={i}
                    className="overflow-hidden rounded border border-yzh-bone-soft"
                >
                    <img
                        src={cap.dataUrl}
                        alt={`Capture ${i + 1}`}
                        className="aspect-square w-full object-cover"
                    />
                    <p className="bg-yzh-bone-soft/40 px-2 py-1 text-center font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                        {(cap.confidence * 100).toFixed(0)}%
                    </p>
                </li>
            ))}
        </ul>
    );
}
