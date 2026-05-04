face-api.js model weights (one-time setup)
============================================

The face-enrollment wizard needs three model bundles loaded at runtime
from this directory. They're ~6 MB total and not vendored into git.

Download these files from the face-api.js GitHub release weights folder
into THIS directory:

  https://github.com/justadudewhohacks/face-api.js/tree/master/weights

Required files:
  - tiny_face_detector_model-weights_manifest.json
  - tiny_face_detector_model-shard1
  - face_landmark_68_model-weights_manifest.json
  - face_landmark_68_model-shard1
  - face_recognition_model-weights_manifest.json
  - face_recognition_model-shard1
  - face_recognition_model-shard2

After placing them here, the wizard will load them automatically the
first time HR runs an enrollment. Browser caches the weights so the
fetch only happens once per machine.

If models are missing the wizard surfaces a clear error and skips the
descriptor compute — no biometrics get stored.
