<?php

namespace App\Support;

/**
 * The three kinds of seller and the documents each one uploads when
 * registering. Every type uploads a government ID; the business permit is
 * optional for all.
 */
class SellerTypes
{
    public const INDIVIDUAL = 'Individual';
    public const SOLE_PROPRIETORSHIP = 'Sole Proprietorship';
    public const CORPORATION = 'Corporation';

    public const TYPES = [self::INDIVIDUAL, self::SOLE_PROPRIETORSHIP, self::CORPORATION];

    /** Required uploads per seller type. */
    public const REQUIRED_DOCUMENTS = [
        self::INDIVIDUAL => ['national_id'],
        self::SOLE_PROPRIETORSHIP => ['national_id', 'dti_certificate', 'bir_2303'],
        self::CORPORATION => ['national_id', 'sec_certificate', 'bir_2303'],
    ];

    /** Every seller upload, in the order the form and the admin page show them. */
    public const DOCUMENTS = [
        'national_id' => 'Government-issued ID',
        'dti_certificate' => 'DTI Certificate',
        'sec_certificate' => 'SEC Certificate',
        'bir_2303' => 'BIR Form 2303',
        'business_permit' => "Mayor's / Business Permit",
    ];

    /** The uploads kept for this type: its required ones plus the optional business permit. */
    public static function documentsFor(string $type): array
    {
        return [...(self::REQUIRED_DOCUMENTS[$type] ?? ['national_id']), 'business_permit'];
    }
}
