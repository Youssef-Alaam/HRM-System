<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Egyptian + expat document matrix locked 2026-04-30 with Walid (copied
 * straight from his HR list). Egyptians = 7 required; expats add 3 more.
 * Optional types are also seeded so HR has a head start.
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        $types = [
            // Egyptian — required (7 total)
            [
                'name' => 'National ID copy',
                'name_ar' => 'صورة البطاقة',
                'description' => 'Copy of the 14-digit Egyptian National ID, both sides.',
                'applies_to' => DocumentType::APPLIES_EGYPTIAN,
                'is_required' => true,
                'default_expiry_months' => 84, // 7 years
            ],
            [
                'name' => 'Birth certificate (original on file)',
                'name_ar' => 'أصل شهادة الميلاد',
                'description' => 'Original birth certificate held in the employee folder.',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => true,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Degree certificate (original on file)',
                'name_ar' => 'أصل شهادة المؤهل',
                'description' => 'Original degree / qualification certificate.',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => true,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Social insurance status (NOSI)',
                'name_ar' => 'برنت تأميني',
                'description' => 'Print from the Egyptian National Organization for Social Insurance.',
                'applies_to' => DocumentType::APPLIES_EGYPTIAN,
                'is_required' => true,
                'default_expiry_months' => 12,
            ],
            [
                'name' => 'Criminal record clearance',
                'name_ar' => 'صحيفة الحالة الجنائية',
                'description' => 'Issued addressed to YZH Solutions. One-time per Walid 2026-04-30.',
                'applies_to' => DocumentType::APPLIES_EGYPTIAN,
                'is_required' => true,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Employment record / experience stub',
                'name_ar' => 'كعب العمل',
                'description' => 'Last-employer experience certificate or NOSI extract.',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => true,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Health insurance Form 111',
                'name_ar' => 'نموذج 111 تأمين صحي',
                'description' => 'Form 111 for the public health-insurance enrollment.',
                'applies_to' => DocumentType::APPLIES_EGYPTIAN,
                'is_required' => true,
                'default_expiry_months' => 12,
            ],

            // Expat — required (3 additional)
            [
                'name' => 'Passport copy',
                'name_ar' => null,
                'description' => 'Photo page + Egypt entry-stamp page.',
                'applies_to' => DocumentType::APPLIES_EXPAT,
                'is_required' => true,
                'default_expiry_months' => null, // Per passport
            ],
            [
                'name' => 'Work permit',
                'name_ar' => null,
                'description' => 'Egyptian work permit — 12-month renewal cycle.',
                'applies_to' => DocumentType::APPLIES_EXPAT,
                'is_required' => true,
                'default_expiry_months' => 12,
            ],
            [
                'name' => 'Residency permit',
                'name_ar' => null,
                'description' => 'Egyptian residency permit (إقامة).',
                'applies_to' => DocumentType::APPLIES_EXPAT,
                'is_required' => true,
                'default_expiry_months' => 12,
            ],

            // Optional — seeded but not required
            [
                'name' => 'Driving license',
                'name_ar' => 'رخصة القيادة',
                'description' => 'Required for drivers / sales reps; optional otherwise.',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => false,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Marriage certificate',
                'name_ar' => 'قسيمة الزواج',
                'description' => 'For benefits eligibility (married employees).',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => false,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Birth certificate of dependent',
                'name_ar' => 'شهادة ميلاد المعال',
                'description' => 'For dependent benefits — uploaded once per dependent.',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => false,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Performance review',
                'name_ar' => null,
                'description' => 'HR-internal performance review record.',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => false,
                'default_expiry_months' => null,
            ],
            [
                'name' => 'Disciplinary record',
                'name_ar' => null,
                'description' => 'HR-internal disciplinary action record (sensitive).',
                'applies_to' => DocumentType::APPLIES_ALL,
                'is_required' => false,
                'default_expiry_months' => null,
            ],
        ];

        foreach ($types as $i => $type) {
            DocumentType::firstOrCreate(
                ['org_id' => $org->id, 'name' => $type['name']],
                array_merge($type, [
                    'order_index' => $i,
                    'is_active' => true,
                ]),
            );
        }
    }
}
