import axios from 'axios';
import * as faceapi from 'face-api.js';
import { AlertTriangle, Camera, CheckCircle, MapPin, Loader2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Attendance check-in / check-out widget.
 *
 * Pipeline:
 *   1. Load face-api.js model weights from /face-models/
 *   2. Fetch the user's enrolled face descriptor from /attendance/my-descriptor
 *   3. Request browser geolocation (block if denied)
 *   4. Request webcam stream (block if denied)
 *   5. On click: capture a frame, run face detection + descriptor extraction
 *   6. Compute cosine similarity vs enrolled descriptor → verdict_score (0-1)
 *   7. POST /attendance/check-in (or /check-out) with lat/long/score
 *   8. Show server verdict (verified / possibly_self / unverified) + redirect on success
 *
 * The verdict cutoffs themselves live on the server (AttendanceService::VERDICT_*).
 * This component is "dumb" — it only submits the raw similarity score and lat/long.
 *
 * Walid will exercise this in a browser at Checkpoint B and tune the thresholds
 * if needed via the AttendanceService constants (not via this component).
 */

const MODELS_URL = '/face-models';

type Mode = 'check_in' | 'check_out';

type Stage =
    | 'loading-models'
    | 'fetching-descriptor'
    | 'no-enrollment'
    | 'requesting-geo'
    | 'geo-denied'
    | 'requesting-camera'
    | 'camera-denied'
    | 'ready'
    | 'capturing'
    | 'submitting'
    | 'success'
    | 'error';

type Props = {
    mode: Mode;
    onSuccess?: () => void;
};

export default function CheckInWidget({ mode, onSuccess }: Props) {
    const [stage, setStage] = useState<Stage>('loading-models');
    const [error, setError] = useState<string>('');
    const [verdict, setVerdict] = useState<string>('');
    const [coords, setCoords] = useState<{ lat: number; lng: number; accuracy: number } | null>(null);
    const [enrolledDescriptor, setEnrolledDescriptor] = useState<Float32Array | null>(null);

    const videoRef = useRef<HTMLVideoElement | null>(null);
    const streamRef = useRef<MediaStream | null>(null);

    const label = mode === 'check_in' ? 'Sign in' : 'Sign out';
    const endpoint = mode === 'check_in' ? '/attendance/check-in' : '/attendance/check-out';

    // ── Step 1+2: load models, fetch enrolled descriptor ──────────────────────
    useEffect(() => {
        let cancelled = false;

        async function init() {
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL),
                ]);
                if (cancelled) return;
                setStage('fetching-descriptor');

                const { data } = await axios.get('/attendance/my-descriptor');
                if (cancelled) return;

                if (!data.enrolled || !data.descriptor) {
                    setStage('no-enrollment');
                    return;
                }

                setEnrolledDescriptor(new Float32Array(data.descriptor));
                setStage('requesting-geo');
                requestGeo();
            } catch (e) {
                if (cancelled) return;
                setError('Could not load face-recognition models. Verify /public/face-models/ contains the weights.');
                setStage('error');
            }
        }

        init();
        return () => {
            cancelled = true;
            stopStream();
        };
    }, []);

    // ── Step 3: geolocation ───────────────────────────────────────────────────
    function requestGeo() {
        if (!navigator.geolocation) {
            setError('Geolocation is not supported by this browser.');
            setStage('error');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                setCoords({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: pos.coords.accuracy,
                });
                setStage('requesting-camera');
                requestCamera();
            },
            () => setStage('geo-denied'),
            { enableHighAccuracy: true, timeout: 10_000, maximumAge: 0 },
        );
    }

    // ── Step 4: camera ────────────────────────────────────────────────────────
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
            setStage('ready');
        } catch {
            setStage('camera-denied');
        }
    }

    function stopStream() {
        streamRef.current?.getTracks().forEach((t) => t.stop());
        streamRef.current = null;
    }

    // ── Step 5+6+7: capture, compute, submit ──────────────────────────────────
    const submit = useCallback(async () => {
        if (!videoRef.current || !coords || !enrolledDescriptor) return;
        setStage('capturing');
        setError('');

        try {
            const detection = await faceapi
                .detectSingleFace(videoRef.current, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                setError('No face detected. Center your face in the frame and try again.');
                setStage('ready');
                return;
            }

            const score = cosineSimilarity(detection.descriptor, enrolledDescriptor);

            setStage('submitting');
            const { data } = await axios.post(endpoint, {
                latitude: coords.lat,
                longitude: coords.lng,
                accuracy_meters: coords.accuracy,
                verdict_score: clamp01(score),
            });

            setVerdict(data.record?.verdict ?? 'verified');
            stopStream();
            setStage('success');
            if (onSuccess) setTimeout(onSuccess, 1500);
        } catch (e: unknown) {
            const err = e as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } };
            if (err.response?.status === 422) {
                // Pull the first field error
                const errs = err.response.data?.errors ?? {};
                const firstKey = Object.keys(errs)[0];
                setError(firstKey ? errs[firstKey][0] : (err.response.data?.message ?? 'Validation failed.'));
            } else {
                setError('Submission failed. Try again or contact HR.');
            }
            setStage('ready');
        }
    }, [coords, enrolledDescriptor, endpoint, onSuccess]);

    // ── UI ────────────────────────────────────────────────────────────────────
    return (
        <div className="border border-yzh-bone-soft p-6 max-w-md">
            <div className="flex items-baseline gap-3 mb-4">
                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">{label}</span>
            </div>

            {stage === 'loading-models' && <Status icon={Loader2} spin>Loading face models…</Status>}
            {stage === 'fetching-descriptor' && <Status icon={Loader2} spin>Fetching your enrollment…</Status>}

            {stage === 'no-enrollment' && (
                <ErrorBox icon={AlertTriangle}
                    title="Not enrolled"
                    body="Your face hasn't been enrolled yet. Ask HR to capture your 3-photo enrollment before you can check in." />
            )}

            {stage === 'requesting-geo' && <Status icon={MapPin}>Requesting your location…</Status>}
            {stage === 'geo-denied' && (
                <ErrorBox icon={MapPin}
                    title="Location blocked"
                    body="Allow location access in your browser settings and reload to check in." />
            )}

            {stage === 'requesting-camera' && <Status icon={Camera}>Requesting camera access…</Status>}
            {stage === 'camera-denied' && (
                <ErrorBox icon={Camera}
                    title="Camera blocked"
                    body="Allow camera access in your browser settings and reload to check in." />
            )}

            {(stage === 'ready' || stage === 'capturing' || stage === 'submitting') && (
                <>
                    <video ref={videoRef} autoPlay playsInline muted
                        className="w-full rounded-sm border border-yzh-bone-soft mb-4 bg-yzh-ink-soft" />
                    {coords && (
                        <p className="font-mono text-[0.6875rem] text-yzh-slate mb-3">
                            Location captured (±{Math.round(coords.accuracy)}m)
                        </p>
                    )}
                    {error && (
                        <p className="mb-3 text-sm text-red-600">{error}</p>
                    )}
                    <button
                        onClick={submit}
                        disabled={stage !== 'ready'}
                        className="inline-flex min-h-11 w-full items-center justify-center gap-2 bg-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-ink hover:bg-yzh-gold/90 disabled:opacity-50"
                    >
                        {stage === 'capturing' && <Loader2 className="h-3.5 w-3.5 animate-spin" />}
                        {stage === 'submitting' && <Loader2 className="h-3.5 w-3.5 animate-spin" />}
                        {stage === 'ready' && <Camera className="h-3.5 w-3.5" />}
                        {stage === 'capturing' ? 'Capturing…' :
                         stage === 'submitting' ? 'Submitting…' :
                         `Capture & ${label.toLowerCase()}`}
                    </button>
                </>
            )}

            {stage === 'success' && (
                <div className="flex items-start gap-3 rounded-sm border border-green-200 bg-green-50 p-4">
                    <CheckCircle className="h-5 w-5 shrink-0 text-green-600 mt-0.5" />
                    <div>
                        <p className="text-sm font-semibold text-yzh-ink">
                            {label} recorded
                        </p>
                        <p className="mt-1 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-slate">
                            Verdict: {verdict.replace('_', ' ')}
                        </p>
                    </div>
                </div>
            )}

            {stage === 'error' && (
                <ErrorBox icon={AlertTriangle} title="Something went wrong" body={error} />
            )}
        </div>
    );
}

function Status({ icon: Icon, spin, children }: { icon: typeof Loader2; spin?: boolean; children: React.ReactNode }) {
    return (
        <div className="flex items-center gap-2 text-sm text-yzh-slate">
            <Icon className={`h-4 w-4 text-yzh-gold ${spin ? 'animate-spin' : ''}`} />
            {children}
        </div>
    );
}

function ErrorBox({ icon: Icon, title, body }: { icon: typeof AlertTriangle; title: string; body: string }) {
    return (
        <div className="flex items-start gap-3 rounded-sm border border-amber-200 bg-amber-50 p-4">
            <Icon className="h-5 w-5 shrink-0 text-amber-500 mt-0.5" />
            <div>
                <p className="text-sm font-semibold text-yzh-ink">{title}</p>
                <p className="mt-1 text-sm text-yzh-slate">{body}</p>
            </div>
        </div>
    );
}

/** Cosine similarity between two equal-length Float32Array descriptors. Returns [-1, 1]. */
function cosineSimilarity(a: Float32Array, b: Float32Array): number {
    if (a.length !== b.length) return 0;
    let dot = 0, normA = 0, normB = 0;
    for (let i = 0; i < a.length; i++) {
        dot += a[i] * b[i];
        normA += a[i] * a[i];
        normB += b[i] * b[i];
    }
    const denom = Math.sqrt(normA) * Math.sqrt(normB);
    return denom === 0 ? 0 : dot / denom;
}

/** Squash cosine similarity to [0, 1] for the verdict_score contract. */
function clamp01(score: number): number {
    if (Number.isNaN(score)) return 0;
    return Math.max(0, Math.min(1, score));
}
