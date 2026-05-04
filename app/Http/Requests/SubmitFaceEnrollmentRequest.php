<?php

namespace App\Http\Requests;

use App\Services\FaceEnrollmentService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the 3-photo + 128-element descriptor + quality score payload
 * coming back from the client wizard. The actual face detection runs in
 * the browser via face-api.js; the server trusts the descriptor's shape
 * but enforces the quality threshold + photo count.
 */
class SubmitFaceEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('face.enroll.any') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'size:'.FaceEnrollmentService::PHOTO_COUNT],
            'photos.*' => [
                'required',
                'image',
                'max:5120', // 5 MB per photo
                'mimes:jpeg,jpg,png,webp',
            ],
            'descriptor' => ['required', 'array', 'size:128'],
            'descriptor.*' => ['required', 'numeric'],
            'quality_score' => ['required', 'numeric', 'min:0', 'max:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.size' => 'Exactly '.FaceEnrollmentService::PHOTO_COUNT.' photos are required.',
            'descriptor.size' => 'Descriptor must be exactly 128 numbers (face-api.js standard).',
        ];
    }
}
