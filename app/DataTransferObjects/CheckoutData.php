<?php

namespace App\DataTransferObjects;

/**
 * Class CheckoutData
 *
 * Data Transfer Object for checkout operations.
 */
class CheckoutData
{
    /**
     * CheckoutData constructor.
     */
    public function __construct(
        public readonly string $brideFullName,
        public readonly string $groomFullName,
        public readonly string $brideNickname,
        public readonly string $groomNickname,
        public readonly string $brideParents,
        public readonly string $groomParents,
        public readonly string $akadDate,
        public readonly string $akadTime,
        public readonly string $akadLocation,
        public readonly string $receptionDate,
        public readonly string $receptionTime,
        public readonly string $receptionLocation,
        public readonly string $shippingAddress,
        public readonly string $shippingMethod,
        public readonly float $shippingCost,
        public readonly string $paymentOption,
        public readonly ?string $gmapsLink = null,
        public readonly ?string $preweddingPhotoPath = null,
        public readonly ?string $shippingService = null,
        public readonly ?string $courier = null,
    ) {}

    /**
     * Create from array (typically from validated request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            brideFullName: $data['bride_full_name'],
            groomFullName: $data['groom_full_name'],
            brideNickname: $data['bride_nickname'],
            groomNickname: $data['groom_nickname'],
            brideParents: $data['bride_parents'],
            groomParents: $data['groom_parents'],
            akadDate: $data['akad_date'],
            akadTime: $data['akad_time'],
            akadLocation: $data['akad_location'],
            receptionDate: $data['reception_date'],
            receptionTime: $data['reception_time'],
            receptionLocation: $data['reception_location'],
            shippingAddress: $data['shipping_address'],
            shippingMethod: $data['shipping_method'],
            shippingCost: (float) ($data['shipping_cost'] ?? 0),
            paymentOption: $data['payment_option'] ?? 'full',
            gmapsLink: $data['gmaps_link'] ?? null,
            preweddingPhotoPath: $data['prewedding_photo_path'] ?? null,
            shippingService: $data['shipping_service'] ?? null,
            courier: $data['courier'] ?? null,
        );
    }

    /**
     * Convert to invitation detail array.
     */
    public function toInvitationDetailArray(): array
    {
        return [
            'bride_full_name' => $this->brideFullName,
            'groom_full_name' => $this->groomFullName,
            'bride_nickname' => $this->brideNickname,
            'groom_nickname' => $this->groomNickname,
            'bride_parents' => $this->brideParents,
            'groom_parents' => $this->groomParents,
            'akad_date' => $this->akadDate,
            'akad_time' => $this->akadTime,
            'akad_location' => $this->akadLocation,
            'reception_date' => $this->receptionDate,
            'reception_time' => $this->receptionTime,
            'reception_location' => $this->receptionLocation,
            'gmaps_link' => $this->gmapsLink,
            'prewedding_photo_path' => $this->preweddingPhotoPath,
        ];
    }
}
